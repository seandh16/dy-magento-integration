<?php

namespace DynamicYield\Integration\Model;

use Aws\CloudFront\Exception\Exception;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use DynamicYield\Integration\Model\Config\Source\UsedProductAttribute;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Framework\ObjectManagerInterface;
use DynamicYield\Integration\Helper\Feed\Proxy as FeedHelper;
use Magento\Framework\App\State;
use Magento\Store\Model\Website;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Catalog\Model\Product\Type;
use DynamicYield\Integration\Model\Logger\Handler;
use Magento\Framework\App\ResourceConnection as Resource;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use \Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use DynamicYield\Integration\Api\Data\ProductFeedInterface;


class Export
{
    const LOCALE_CODE = 'general/locale/code';
    const PRODUCT_GROUPED = "grouped";
    const PRODUCT_CONFIGURABLE = "configurable";

    /**
     * @var array
     */
    protected $_baseAttributes = [
        'name',
        'url',
        'sku',
        'group_id',
        'price',
        'in_stock',
        'categories',
        'image_url',
        'keywords'
    ];

    /**
     * @var array
     */
    protected $_globalAttributes = [
        'sku',
        'group_id',
        'in_stock',
        'image_url'
    ];

    /**
     * @var array
     */
    protected $_excludedAttributes = [
        'name',
        'url_path',
        'sku',
        'price',
        'image'
    ];

    /**
     * @var array
     */
    protected $_excludedHeader = [
        'url_path',
        'image'
    ];

    protected $customAttributes = [
        'categories',
        'url',
        'keywords',
        ProductFeedInterface::FINAL_PRICE
    ];

    /**
     * @var array
     */
    protected $_header = [];

    /**
     * @var array
     */
    protected $_stores = [];

    /**
     * @var StoreManager
     */
    protected $_storeManager;

    /**
     * @var FeedHelper
     */
    protected $_feedHelper;

    /**
     * @var UsedProductAttribute
     */
    protected $_usedProductAttribute;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var ProductResource
     */
    protected $_productResource;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var State
     */
    protected $_state;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var StockRegistry
     */
    protected $_stockRegistry;

    /**
     * @var Handler
     */
    protected $_handler;

    /**
     * @var array
     */
    protected $_uniqueStores;


    protected $_resource;

    /**
     * @var UrlInterface
     */
    protected $_urlModel;

    /**
     * @var UrlFinderInterface
     */
    protected $urlFinder;

    /**
     * @var
     */
    protected $_excludedCategories;

    /**
     * @var CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * Export constructor
     *
     * @param State $state
     * @param ObjectManagerInterface $objectManager
     * @param StoreManager $storeManager
     * @param Product $product
     * @param ProductResource $productResource
     * @param ProductFactory $productFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StockRegistry $stockRegistry
     * @param Handler $handler
     * @param LoggerInterface $logger
     * @param FeedHelper $feedHelper
     * @param Resource $resource
     * @param UrlInterface $urlModel
     * @param UrlFinderInterface $urlFinder
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        State $state,
        ObjectManagerInterface $objectManager,
        StoreManager $storeManager,
        Product $product,
        ProductResource $productResource,
        ProductFactory $productFactory,
        ProductCollectionFactory $productCollectionFactory,
        StockRegistry $stockRegistry,
        Handler $handler,
        LoggerInterface $logger,
        FeedHelper $feedHelper,
        Resource $resource,
        UrlInterface $urlModel,
        UrlFinderInterface $urlFinder,
        CollectionFactory $categoryCollectionFactory
    )
    {
        $this->_state = $state;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_productResource = $productResource;
        $this->_productFactory = $productFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->_handler = $handler;
        $this->_logger = $logger->setHandlers([$this->_handler]);
        $this->_feedHelper = $feedHelper;
        $this->_resource = $resource;
        $this->_urlModel = $urlModel;
        $this->_urlFinder = $urlFinder;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array
     */
    public function setStores()
    {
        $locales = [];
        /** @var Website $website */
        $website = $this->_storeManager->getWebsite(true);
        /** @var Group $group */
        $group = $website->getDefaultGroup();
        $stores = $group->getStores();

        /** @var Store $store */
        foreach ($stores as $store) {
            $locales[$store->getId()] = $this->_feedHelper->getStoreLocale($store->getId());
            $this->_stores[$store->getId()] = $store;
        }

        $locales = array_unique($locales);

        foreach ($locales as $key => $value) {
            $this->_uniqueStores[] = $key;
        }

        return $locales;
    }

    /**
     * Upload exported file to Amazon
     */
    public function upload() {

        $s3 = new S3Client([
            'region'  => $this->_feedHelper->getRegion(),
            'version' => $this->_feedHelper->getVersion(),
            'credentials' => [
                'key'    => $this->_feedHelper->getAccessKeyId(),
                'secret' => $this->_feedHelper->getAccessKey(),
            ]
        ]);

        try {
            return $s3->upload(
                $this->_feedHelper->getBucket(),
                $this->_feedHelper->getSectionId()."/".$this->_feedHelper->getExportFilename(),
                fopen($this->_feedHelper->getExportFile(), 'r')
            );
        } catch (S3Exception $e) {
            $this->_logger->error("DYI: There was an error uploading the file " . $e->getMessage());
        }
    }

    /**
     * Export csv file
     */
    public function export()
    {
        $this->_usedProductAttribute = $this->_objectManager->get(UsedProductAttribute::class);
        $this->_excludedCategories = $this->_feedHelper->getExcludedCategories();

        $this->setStores();

        $path = $this->_feedHelper->getExportPath();

        if (!is_dir($path)) {
            mkdir($path);
        }

        $this->clearSkippedProductsLog();
        $file = fopen($this->_feedHelper->getExportFile(), 'w+');

        $additionalAttributes = [];
        $translatableAttributes = [];

        $usedAttributes = $this->_usedProductAttribute->getAttributes();

        /** @var Attribute $attribute */
        foreach ($usedAttributes as $attribute) {
            if (!$attribute->getIsGlobal()) {
                $translatableAttributes[] = $attribute->getAttributeCode();
            }

            $additionalAttributes[] = $attribute->getAttributeCode();
        }

        $header = array_unique(array_merge($this->_baseAttributes, $additionalAttributes));
        $header = array_diff($header, $this->_excludedHeader);
        $header = array_unique(array_merge($header, $this->customAttributes));

        if(!$this->_feedHelper->isFinalPriceSelected()) {
            if (($key = array_search(ProductFeedInterface::FINAL_PRICE, $header)) !== false) {
                unset($header[$key]);
            }
        }

        if($this->_feedHelper->isMultiLanguage()) {
            foreach ($header as $code) {
                if (!in_array($code, $this->_globalAttributes)
                    && in_array($code, $translatableAttributes) || in_array($code,$this->customAttributes)) {
                    /** @var Store $store */
                    foreach ($this->_uniqueStores as $storeId) {
                        $header[] = $this->getLngKey($this->_feedHelper->getStoreLocale($storeId), $code);
                    }
                }
            }
        }

        $this->_header = $header;

        fputcsv($file, $header, ',');

        $offset = 0;
        $limit = $selected = 100;

        while($limit === $selected) {
            $result = $this->chunkProductExport($file, $limit, $offset, $usedAttributes);
            $selected = $result['count'];
            $offset = $result['last'];
        }

        return $this->upload();
    }

    /**
     * @param $file
     * @param int $limit
     * @param int $offset
     * @param mixed $additionalAttributes
     *
     * @return array
     */
    public function chunkProductExport($file, $limit = 100, $offset = 0, $additionalAttributes)
    {
        if($this->_feedHelper->getIsDebugMode()){
            $time_start = microtime(true);
        }
        /** @var Collection $collection */
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter(Product::STATUS, ['eq' => Status::STATUS_ENABLED])
            ->addAttributeToFilter('type_id', ['nin' => [
                Type::TYPE_BUNDLE, static::PRODUCT_GROUPED, static::PRODUCT_CONFIGURABLE
            ]]);
        $collection->addUrlRewrite();
        $collection->addFieldToFilter("entity_id",["gt" => $offset]);
        $collection->getSelect()->limit($limit, 0);
        $collection->getSelect()->joinLeft(array('super' => $this->_resource->getTableName('catalog_product_super_link')),'`e`.`entity_id` = `super`.`product_id`',array('parent_id'));
        $collection->getSelect()->group('e.entity_id');

        $storeCollection = [];

        if($this->_feedHelper->isMultiLanguage()) {
            $ids = [];

            /**
             * Collect selected product IDs
             */
            foreach ($collection as $product) {
                $ids[] = $product->getId();
            }

            foreach ($this->_stores as $store) {
                if(!in_array($store->getId(),$this->_uniqueStores)) continue;
                $this->_productCollectionFactory->create()->setStore($store);
                $storeCollection[$store->getId()] = $this->_productCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addUrlRewrite();
                $storeCollection[$store->getId()]->setStore($store);
                $storeCollection[$store->getId()]->addFieldToFilter("entity_id",["in" => $ids]);
                $storeCollection[$store->getId()]->getSelect()->limit($limit, 0);
                $storeCollection[$store->getId()]->addAttributeToFilter(Product::STATUS, ['eq' => Status::STATUS_ENABLED])
                    ->addAttributeToFilter('type_id', ['nin' => [
                        Type::TYPE_BUNDLE, static::PRODUCT_GROUPED,static::PRODUCT_CONFIGURABLE
                    ]]);
                $storeCollection[$store->getId()]->load();
            }
        }

        $parentIds = array();
        /**
         * Collect selected parent IDs
         */
        foreach ($collection as $product) {
            $parentIds[] = $product->getParentId();
        }

        $parentProductCollection = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addUrlRewrite();
        $parentProductCollection->addFieldToFilter("entity_id", ["in" => $parentIds]);
        $parentProductCollection->getSelect()->limit($limit, 0);

        /** @var Product $item */
        foreach ($collection as $item) {
            $line = $this->readLine($item, $storeCollection,$parentProductCollection, $additionalAttributes);
            if($line) fputcsv($file, $this->fillLine($line), ',');
        }

        if($this->_feedHelper->getIsDebugMode()) {
            $memory = memory_get_usage();
            $this->_logger->debug('DYI: MEMORY USED ' . $memory . '. Chunk export execution time in seconds: ' . (microtime(true) - $time_start));
        }

        return [
            'count' => $collection->count(),
            'last' => $collection->getLastItem()->getEntityId()
        ];
    }

    /**
     * Get final price of variation
     *
     * @param $simpleProduct
     * @param $parentCollection
     * @return mixed
     */
    public function getFinalPrice($simpleProduct,$parentCollection)
    {
        foreach ($parentCollection as $parent) {
            if ($parent->getId() == $simpleProduct->getParentId()) {
                $values   = array();
                $attributeCodes = array();

                /**
                 * Custom query to fetch configurable attributes
                 */
                $connectionReader = $this->_resource->getConnection('core_read');


                $query =
                    "SELECT eav.attribute_code, eav.attribute_id FROM "
                    . $this->_resource->getTableName('eav_attribute') .
                    " as eav LEFT JOIN "
                    . $this->_resource->getTableName('catalog_product_super_attribute') .
                    " as super ON eav.attribute_id = super.attribute_id WHERE (product_id = " . $parent->getId() . ");";

                $result = $connectionReader->query($query);

                while ($row = $result->fetch()) {
                    $attributeCodes[$row['attribute_id']] = $row['attribute_code'];
                };

                foreach ($attributeCodes as $id => $code) {
                    $values[$id] = $simpleProduct->getData($code);
                }

                $parent->addCustomOption('attributes', serialize($values));
                $parent->addCustomOption('simple_product', $simpleProduct->getId(), $simpleProduct);

                $price = $parent->getPriceModel()->getFinalPrice(null,$parent);
                return $price ?: $simpleProduct->getPrice();
            }
        }

        return $simpleProduct->getPrice();
    }

    /**
     * @param Product $_product
     * @param mixed $storeCollection
     * @param mixed $parentProductCollection
     * @param mixed $additionalAttributes
     *
     * @return array
     */
    public function readLine(Product $_product, $storeCollection,$parentProductCollection = null, $additionalAttributes)
    {
        $rowData = [
            'name' => $_product->getName(),
            'url' => $this->getProductUrl($_product),
            'sku' => $_product->getSku(),
            'group_id' => $this->getGroupId($_product),
            'price' => $_product->getParentId() ? $this->getFinalPrice($_product,$parentProductCollection) : $_product->getPrice(),
            'in_stock' => $this->_stockRegistry->getStockItem($_product->getId())->getIsInStock() ? "true" : "false",
            'categories' => $this->buildCategories($_product),
            'image_url' => $_product->getImage() ? $_product->getMediaConfig()->getMediaUrl($_product->getImage()) : null
        ];

        if(count($rowData) != count(array_diff($rowData,array('')))) {
            $this->logSkippedProducts(json_encode($rowData).PHP_EOL);
            return false;
        }

        $rowData['keywords'] = $this->buildCategories($_product,true);
        $rowData[ProductFeedInterface::FINAL_PRICE] = $_product->getFinalPrice();

        $currentStore = $_product->getStore();

        /** @var Attribute $attribute */
        foreach ($additionalAttributes as $attribute) {
            if(!in_array($attribute->getAttributeCode(),$this->_excludedAttributes)) {
                $rowData[$attribute->getAttributeCode()] = $this->buildAttributeData($_product, $attribute);
            }
        }

        if(!empty($storeCollection)) {
            foreach ($this->_stores as $store) {
                if(!in_array($store->getId(), $this->_uniqueStores)) continue;

                /** @var Store $store */
                $this->_storeManager->setCurrentStore($store);
                $langCode = $this->_feedHelper->getStoreLocale($store->getId());

                $continue = false;

                foreach ($storeCollection[$store->getId()] as $loadedProduct) {
                    if($_product->getId() == $loadedProduct->getId()) {
                        $continue = true;
                        /** @var Product $storeProduct */
                        $storeProduct = clone $loadedProduct;
                        break;
                    }
                }

                if(!$continue) continue;

                /**
                 * Translate non-standard attributes
                 */
                $rowData[$this->getLngKey($langCode, 'categories')] = $this->buildCategories($storeProduct);
                $rowData[$this->getLngKey($langCode, 'keywords')] = $this->buildCategories($storeProduct,true);
                $rowData[$this->getLngKey($langCode, 'url')] = $this->getProductUrl($storeProduct,true);
                $rowData[$this->getLngKey($langCode, ProductFeedInterface::FINAL_PRICE)] = $storeProduct->getFinalPrice();

                /** @var Attribute $attribute */
                foreach ($additionalAttributes as $attribute) {
                    $field = $this->getLngKey($langCode, $attribute->getAttributeCode());
                    if(in_array($field,$this->_header)){
                        $rowData[$field] = $this->buildAttributeData($storeProduct, $attribute);
                    }
                }
            }
        }

        $this->_storeManager->setCurrentStore($currentStore);

        return $rowData;
    }

    /**
     * @param $line
     * @return array
     */
    protected function fillLine($line)
    {
        $out = [];

        foreach ($this->_header as $key) {
            if (isset($line[$key])) {
                $out[$key] = $line[$key];
            } else {
                $out[$key] = null;
            }
        }

        return $out;
    }

    /**
     * @param $langCode
     * @param $code
     * @return string
     */
    protected function getLngKey($langCode, $code)
    {
        return sprintf('lng:%s:%s', $langCode, $code);
    }

    /**
     * Get product url
     * Return parent url if child product is not visible
     * @param $product
     *
     * @return string
     */
    protected function getProductUrl($product,$store = null)
    {
        return $this->getRewrittenUrl($product,$store);
    }

    /**
     * Get rewritten product url
     *
     * @param $product
     * @return mixed
     */
    protected function getRewrittenUrl($product,$store = null)
    {
        $productId = $product->isVisibleInSiteVisibility() ? $product->getId() : $product->getParentId();

        $filterData = [
            UrlRewrite::ENTITY_ID => $productId,
            UrlRewrite::ENTITY_TYPE => "product"
        ];

        if($store){
            $filterData[UrlRewrite::STORE_ID] = $product->getStoreId();
        }
        $urlRewrite = $this->_urlFinder->findOneByData($filterData);

        if($urlRewrite) {
            return $this->_urlModel->getUrl($urlRewrite->getRequestPath());
        }

        return $product->getProductUrl();
    }

    /**
     * Return Group Id
     *
     * @param $product
     * @return array|bool|string
     */
    protected function getGroupId($product)
    {
        return $product->getParentId() ? $this->_productResource->getAttributeRawValue($product->getParentId(), 'sku', 0)['sku'] : $product->getSku();
    }

    /**
     * Get collection of product categories or keywords
     *
     * @param $product
     * @param $keywords
     *
     * @return $collection
     */
    public function getCategoryCollection($product,$keywords = false)
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->joinField(
            'product_id',
            'catalog_category_product',
            'product_id',
            'category_id = entity_id',
            null
        )->addFieldToFilter(
            'product_id',
            (int)$product->getEntityId()
        );
        if($keywords) {
            $collection->addFieldToFilter('entity_id', array('in' => $this->_excludedCategories));
        } else {
            $collection->addFieldToFilter('entity_id', array('nin' => $this->_excludedCategories));
        }

        return $collection;
    }

    /**
     * @param Product $product
     * @param $keywords
     *
     * @return string
     */
    protected function buildCategories(Product $product, $keywords = false)
    {
        $categories = $this->getCategoryCollection($product,$keywords)->getItems();

        return join('|', array_map(function ($category) {
            /** @var Category $category */
            return $category->getName();
        }, $categories));
    }

    /**
     * @param Product $product
     * @param EavAttribute $attribute
     *
     * @return array
     */
    protected function buildAttributeData(Product $product, EavAttribute $attribute)
    {
        $attributeData = $product->getData($attribute->getAttributeCode());

        if (!is_array($attributeData) && $attribute->getOptions()) {
            $attributeData = $attribute->getFrontend()->getValue($product);
        }

        if (is_array($attributeData)) {
            $attributeData = join("|", $attributeData);
        }

        return $attributeData;
    }

    /**
     * @param Mixed $products
     *
     * Writes skipped products in json format to a log file
     */
    public function logSkippedProducts($products)
    {
        try {
            file_put_contents($this->_feedHelper->getFeedLogFile(), $products,FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            $this->_logger->error("Error logging skipped products: ".$e->getMessage());
        }
    }

    /**
     *  Clears log file
     */
    public function clearSkippedProductsLog()
    {
        try{
            if($this->_feedHelper->isSkippedProducts()) {
                file_put_contents($this->_feedHelper->getFeedLogFile(), "");
            }
        } catch (\Exception $e) {
            $this->_logger->error("Error clearing log file: ".$e->getMessage());
        }
    }

    /**
     * Get configurable product child price
     * Return 0 if not found
     *
     * @param $configProduct
     * @return int
     */
    public function getChildPrice($configProduct){
        if($configProduct->getTypeId() == "configurable"){
            $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
            foreach ($_children as $child){
                return $child->getPrice();
            }
        }
        return 0;
    }
}
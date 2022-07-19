<?php

namespace DynamicYield\Integration\Model;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use DynamicYield\Integration\Helper\Feed\Proxy as FeedHelper;
use DynamicYield\Integration\Model\Config\Source\UsedProductAttribute;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Framework\App\ResourceConnection as Resource;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\Website;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Psr\Log\LoggerInterface;

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
        ProductFeedInterface::FINAL_PRICE,
        ProductFeedInterface::BASE_PRICE
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
    protected $logger;

    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var StockRegistry
     */
    protected $_stockRegistry;

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
        LoggerInterface $logger,
        FeedHelper $feedHelper,
        Resource $resource,
        UrlInterface $urlModel,
        UrlFinderInterface $urlFinder,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->_state = $state;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_productResource = $productResource;
        $this->_productFactory = $productFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->logger = $logger;
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
    public function upload()
    {
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
                $this->_feedHelper->getSectionId() . "/" . $this->_feedHelper->getExportFilename(),
                fopen($this->_feedHelper->getExportFile(), 'r')
            );
        } catch (S3Exception $e) {
            $this->logger->error("DYI: There was an error uploading the file " . $e->getMessage());
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

        foreach ($this->_feedHelper->getCustomProductAttributes() as $customAttribute) {
            if (!$this->_feedHelper->isAttributeSelected($customAttribute)) {
                if (($key = array_search($customAttribute, $header)) !== false) {
                    unset($header[$key]);
                }
            } else {
                $header[] = $customAttribute;
            }
        }
        $header = array_unique($header);

        if ($this->_feedHelper->isMultiLanguage()) {
            foreach ($header as $code) {
                if (!in_array($code, $this->_globalAttributes)
                    && in_array($code, $translatableAttributes) || in_array($code, $this->customAttributes)) {
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

        while ($limit === $selected) {
            $result = $this->chunkProductExport($file, $usedAttributes, $limit, $offset);
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
    public function chunkProductExport($file, $additionalAttributes, $limit = 100, $offset = 0)
    {
        if ($this->_feedHelper->getIsDebugMode()) {
            $time_start = microtime(true);
        }

        if ($defaultStore = $this->_storeManager->getDefaultStoreView()) {
            $this->_storeManager->setCurrentStore($defaultStore->getStoreId());
        }

        /** @var Collection $collection */
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter(ProductInterface::STATUS, ['eq' => Status::STATUS_ENABLED])
            ->addAttributeToFilter('type_id', ['nin' => [
                Type::TYPE_BUNDLE, static::PRODUCT_GROUPED, static::PRODUCT_CONFIGURABLE
            ]]);
        $collection->addUrlRewrite();
        $collection->addFieldToFilter("entity_id", ["gt" => $offset]);
        $collection->getSelect()->limit($limit, 0);
        if ($this->_feedHelper->isEnterpriseEdition()) {
            $collection->getSelect()->joinLeft(['super' => $this->_resource->getTableName('catalog_product_super_link')], '`e`.`entity_id` = `super`.`product_id`', ['parent_row_id' => 'parent_id']);
            $collection->getSelect()->joinLeft(['self' => $this->_resource->getTableName('catalog_product_entity')], '`super`.`parent_id` = `self`.`row_id`', ['parent_id' => 'entity_id']);
        } else {
            $collection->getSelect()->joinLeft(['super' => $this->_resource->getTableName('catalog_product_super_link')], '`e`.`entity_id` = `super`.`product_id`', ['parent_id']);
        }
        $collection->getSelect()->group('e.entity_id');

        $storeCollection = [];

        if ($this->_feedHelper->isMultiLanguage()) {
            $ids = [];

            /**
             * Collect selected product IDs
             */
            foreach ($collection as $product) {
                $ids[] = $product->getId();
            }

            foreach ($this->_stores as $store) {
                if (!in_array($store->getId(), $this->_uniqueStores)) {
                    continue;
                }
                $this->_productCollectionFactory->create()->setStore($store);
                $storeCollection[$store->getId()] = $this->_productCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addUrlRewrite();
                $storeCollection[$store->getId()]->setStore($store);
                $storeCollection[$store->getId()]->addFieldToFilter("entity_id", ["in" => $ids]);
                $storeCollection[$store->getId()]->getSelect()->limit($limit, 0);
                $storeCollection[$store->getId()]->addAttributeToFilter(Product::STATUS, ['eq' => Status::STATUS_ENABLED])
                    ->addAttributeToFilter('type_id', ['nin' => [
                        Type::TYPE_BUNDLE, static::PRODUCT_GROUPED,static::PRODUCT_CONFIGURABLE
                    ]]);
                if ($this->_feedHelper->isEnterpriseEdition()) {
                    $storeCollection[$store->getId()]->getSelect()->joinLeft(['super' => $this->_resource->getTableName('catalog_product_super_link')], '`e`.`entity_id` = `super`.`product_id`', ['parent_row_id' => 'parent_id']);
                    $storeCollection[$store->getId()]->getSelect()->joinLeft(['self' => $this->_resource->getTableName('catalog_product_entity')], '`super`.`parent_id` = `self`.`row_id`', ['parent_id' => 'entity_id']);
                } else {
                    $storeCollection[$store->getId()]->getSelect()->joinLeft(['super' => $this->_resource->getTableName('catalog_product_super_link')], '`e`.`entity_id` = `super`.`product_id`', ['parent_id']);
                }
                $storeCollection[$store->getId()]->getSelect()->group('e.entity_id');
                $storeCollection[$store->getId()]->load();
            }
        }

        $parentIds = [];
        /**
         * Collect selected parent IDs
         */
        foreach ($collection as $product) {
            $parentIds[] = $product->getParentId();
        }

        $parentProductCollection = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addUrlRewrite();
        $parentProductCollection->addFieldToFilter('entity_id', ['in' => $parentIds]);
        $parentProductCollection->getSelect()->limit($limit, 0);

        /** @var Product $item */
        foreach ($collection as $item) {
            $line = $this->readLine($item, $storeCollection, $additionalAttributes, $parentProductCollection);
            if ($line) {
                fputcsv($file, $this->fillLine($line), ',');
            }
        }

        if ($this->_feedHelper->getIsDebugMode()) {
            $memory = memory_get_usage();
            $this->logger->debug('DYI: MEMORY USED ' . $memory . '. Chunk export execution time in seconds: ' . (microtime(true) - $time_start));
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
    public function getFinalPrice($simpleProduct, $parentCollection)
    {
        foreach ($parentCollection as $parent) {
            if ($parent->getId() == $simpleProduct->getParentId()) {
                $values   = [];
                $attributeCodes = [];

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
                }

                foreach ($attributeCodes as $id => $code) {
                    $values[$id] = $simpleProduct->getData($code);
                }

                $parent->addCustomOption('attributes', serialize($values));
                $parent->addCustomOption('simple_product', $simpleProduct->getId(), $simpleProduct);

                $price = $parent->getPriceModel()->getFinalPrice(null, $parent);
                return $price ?: $simpleProduct->getPrice();
            }
        }

        return $simpleProduct->getPrice();
    }

    /**
     * Get Product Image Url
     * Fallback to parent product image
     *
     * @param $product
     * @return mixed
     */
    public function getProductImageUrl($product)
    {
        $image = null;

        if (!in_array($product->getImage(), ['no_selection',''])) {
            $image = $product->getImage();
        } elseif ($product->getParentId()) {
            $image = $this->_productResource->getAttributeRawValue($product->getParentId(), 'image', $product->getStore()->getId());
        }

        return $image ? $product->getMediaConfig()->getMediaUrl($image) : '';
    }

    /**
     * @param Product $_product
     * @param mixed $storeCollection
     * @param mixed $parentProductCollection
     * @param mixed $additionalAttributes
     *
     * @return array
     */
    public function readLine(Product $_product, $storeCollection, $additionalAttributes, $parentProductCollection = null)
    {
        if ($defaultStore = $this->_storeManager->getDefaultStoreView()) {
            $this->_storeManager->setCurrentStore($defaultStore->getStoreId());
        }

        if ($_product->getParentId()) {
            if ($this->_productResource->getAttributeRawValue($_product->getParentId(), 'status', $_product->getStore()->getId()) != Status::STATUS_ENABLED) {
                return false;
            }
        }

        $rowData = [
            'name' => $this->getProductName($_product, $_product->getStore()->getId()),
            'url' => $this->getProductUrl($_product),
            'sku' => $this->_feedHelper->replaceSpaces($_product->getSku()),
            'group_id' => $this->_feedHelper->replaceSpaces($this->getGroupId($_product)),
            'price' => $_product->getParentId() ? $this->getFinalPrice($_product, $parentProductCollection) : $_product->getPrice(),
            'in_stock' => $this->_stockRegistry->getStockItem($_product->getId())->getIsInStock() ? "true" : "false",
            'categories' => $this->buildCategories($_product),
            'image_url' => $this->getProductImageUrl($_product)
        ];

        if (count($rowData) != count(array_diff($rowData, ['']))) {
            $this->logSkippedProducts(json_encode($rowData) . PHP_EOL);
            return false;
        }

        $rowData['keywords'] = $this->buildCategories($_product, true);
        $rowData[ProductFeedInterface::FINAL_PRICE] = $_product->getFinalPrice();
        $rowData[ProductFeedInterface::BASE_PRICE] = $_product->getPrice();
        $rowData[ProductFeedInterface::PRODUCT_ID] = $_product->getId();

        $currentStore = $_product->getStore();

        /** @var Attribute $attribute */
        foreach ($additionalAttributes as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $this->_excludedAttributes)) {
                $rowData[$attribute->getAttributeCode()] = $this->buildAttributeData($_product, $attribute);
            }
        }

        if (!empty($storeCollection)) {
            foreach ($this->_stores as $store) {
                if (!in_array($store->getId(), $this->_uniqueStores)) {
                    continue;
                }

                /** @var Store $store */
                $this->_storeManager->setCurrentStore($store);
                $langCode = $this->_feedHelper->getStoreLocale($store->getId());

                $continue = false;

                foreach ($storeCollection[$store->getId()] as $loadedProduct) {
                    if ($_product->getId() == $loadedProduct->getId()) {
                        $continue = true;
                        /** @var Product $storeProduct */
                        $storeProduct = clone $loadedProduct;
                        break;
                    }
                }

                if (!$continue) {
                    continue;
                }

                /**
                 * Translate non-standard attributes
                 */
                $rowData[$this->getLngKey($langCode, 'categories')] = $this->buildCategories($storeProduct);
                $rowData[$this->getLngKey($langCode, 'keywords')] = $this->buildCategories($storeProduct, true);
                $rowData[$this->getLngKey($langCode, 'url')] = $this->getProductUrl($storeProduct, true);
                $rowData[$this->getLngKey($langCode, ProductFeedInterface::FINAL_PRICE)] = $storeProduct->getFinalPrice();
                $rowData[$this->getLngKey($langCode, ProductFeedInterface::BASE_PRICE)] = $storeProduct->getPrice();

                /** @var Attribute $attribute */
                foreach ($additionalAttributes as $attribute) {
                    $field = $this->getLngKey($langCode, $attribute->getAttributeCode());
                    if (in_array($field, $this->_header)) {
                        $rowData[$field] = $this->buildAttributeData($storeProduct, $attribute);
                    }
                }
                $rowData[$this->getLngKey($langCode, 'name')] = $this->getProductName($storeProduct, $store->getId());
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
    protected function getProductUrl($product, $store = null)
    {
        return $this->getRewrittenUrl($product, $store);
    }

    /**
     * Get Product Name (parent if exists)
     *
     * @param $product
     * @param $storeId
     * @return mixed
     */
    public function getProductName($product, $storeId = 0)
    {
        return $product->getParentId() ? $this->_productResource->getAttributeRawValue($product->getParentId(), 'name', $storeId) : $product->getName();
    }

    /**
     * Get rewritten product url
     *
     * @param $product
     * @return mixed
     */
    protected function getRewrittenUrl($product, $store = null)
    {
        $productId = $product->isVisibleInSiteVisibility() ? $product->getId() : $product->getParentId();

        $filterData = [
            UrlRewrite::ENTITY_ID => $productId,
            UrlRewrite::ENTITY_TYPE => "product"
        ];

        if ($store) {
            $filterData[UrlRewrite::STORE_ID] = $product->getStoreId();
        }
        $urlRewrite = $this->_urlFinder->findOneByData($filterData);

        if ($urlRewrite) {
            return $this->_storeManager->getStore($product->getStoreId())->getBaseUrl(UrlInterface::URL_TYPE_LINK) . $urlRewrite->getRequestPath();
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
     * @param $productId
     * @param $storeId
     * @param bool $keywords
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection $collection
     * @throws LocalizedException
     */
    public function getCategoryCollection($productId, $storeId, $keywords = false)
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
            $productId
        );
        if ($keywords) {
            $collection->addFieldToFilter('entity_id', ['in' => $this->_excludedCategories]);
        } else {
            $collection->addFieldToFilter('entity_id', ['nin' => $this->_excludedCategories]);
        }

        if (!empty($this->_feedHelper->getCategoryTree($storeId))) {
            $conditions = [];
            foreach ($this->_feedHelper->getCategoryTree($storeId) as $tree) {
                $conditions[] = ['attribute' => 'path', 'like' => '%/' . $tree . '/%'];
            }
            $collection->addFieldToFilter($conditions);
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
        $categories = $this->getCategoryCollection($product->getId(), $product->getStore()->getId(), $keywords)->getItems() ?:
            $this->getCategoryCollection($product->getParentId(), $product->getStore()->getId(), $keywords)->getItems();

        return join('|', array_map(function ($category) {
            /** @var Category $category */
            return trim($category->getName());
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

        if (!$attributeData || $attributeData = '') {
            $attributeData = $this->_productResource->getAttributeRawValue($product->getParentId(), $attribute->getAttributeCode(), $product->getStore()->getId());
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
            file_put_contents($this->_feedHelper->getFeedLogFile(), $products, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            $this->logger->error("Error logging skipped products: " . $e->getMessage());
        }
    }

    /**
     *  Clears log file
     */
    public function clearSkippedProductsLog()
    {
        try {
            if ($this->_feedHelper->isSkippedProducts()) {
                file_put_contents($this->_feedHelper->getFeedLogFile(), "");
            }
        } catch (\Exception $e) {
            $this->logger->error("Error clearing log file: " . $e->getMessage());
        }
    }

    /**
     * Get configurable product child price
     * Return 0 if not found
     *
     * @param $configProduct
     * @return int
     */
    public function getChildPrice($configProduct)
    {
        if ($configProduct->getTypeId() == "configurable") {
            $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
            foreach ($_children as $child) {
                return $child->getPrice();
            }
        }
        return 0;
    }
}

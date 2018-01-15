<?php

namespace DynamicYield\Integration\Model;

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
use DynamicYield\Integration\Helper\Feed as FeedHelper;
use Magento\Framework\App\State;
use Magento\Store\Model\Website;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use \Magento\Framework\UrlInterface;

class Export
{
    const LOCALE_CODE = 'general/locale/code';

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
        'image_url'
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
     * @var StockItemRepository
     */
    protected $_stockItemRepository;

    /**
     * Export constructor
     *
     * @param State $state
     * @param ObjectManagerInterface $objectManager
     * @param StoreManager $storeManager
     * @param Product $product
     * @param ProductResource $productResource
     * @param ProductFactory $productFactory
     * @param LoggerInterface $logger
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StockItemRepository $stockItemRepository
     */
    public function __construct(
        State $state,
        ObjectManagerInterface $objectManager,
        StoreManager $storeManager,
        Product $product,
        ProductResource $productResource,
        ProductFactory $productFactory,
        LoggerInterface $logger,
        ProductCollectionFactory $productCollectionFactory,
        StockItemRepository $stockItemRepository
    )
    {
        $this->_state = $state;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_productResource = $productResource;
        $this->_productFactory = $productFactory;
        $this->_logger = $logger;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockItemRepository = $stockItemRepository;
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
        $defaultLocale = $group->getDefaultStore()->getConfig(self::LOCALE_CODE);

        /** @var Store $store */
        foreach ($stores as $store) {
            if ($defaultLocale != $store->getConfig(self::LOCALE_CODE)) {
                $locales[$store->getId()] = $store->getConfig(self::LOCALE_CODE);
                $this->_stores[$store->getId()] = $store;
            }
        }

        $locales = array_unique($locales);

        return $locales;
    }

    /**
     * Upload exported file to Amazon
     */
    public function upload() {
        $s3 = S3Client::factory([
            'credentials' => [
                'key'    => $this->_feedHelper->getAccessKeyId(),
                'secret' => $this->_feedHelper->getAccessKey(),
            ]
        ]);

        try {
            return $s3->upload(
                $this->_feedHelper->getBucket(),
                $this->_feedHelper->getExportFilename(),
                fopen($this->_feedHelper->getExportFile(), 'r')
            );
        } catch (S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
    }

    /**
     * Return product stock item by product Id
     *
     * @param $productId
     * @return mixed
     */
    public function getStockItem($productId)
    {
        return $this->_stockItemRepository->get($productId);
    }

    /**
     * Export csv file
     */
    public function export()
    {
        $this->_usedProductAttribute = $this->_objectManager->get(UsedProductAttribute::class);
        $this->_feedHelper = $this->_objectManager->get(FeedHelper::class);

        $this->setStores();

        $path = $this->_feedHelper->getExportPath();

        if (!is_dir($path)) {
            mkdir($path);
        }

        $file = fopen($this->_feedHelper->getExportFile(), 'w+');

        $additionalAttributes = [];
        $translatableAttributes = [];

        $usedAttributes = $this->_usedProductAttribute->getAttributes();

        /** @var Attribute $attribute */
        foreach ($usedAttributes as $attribute) {
            if ($attribute->getIsGlobal()) {
                $translatableAttributes[] = $attribute->getAttributeCode();
            }

            $additionalAttributes[] = $attribute->getAttributeCode();
        }

        $header = array_unique(array_merge($this->_baseAttributes, $additionalAttributes));
        $header = array_diff($header, $this->_excludedHeader);

        foreach ($header as $code) {
            if (!in_array($code, $this->_globalAttributes)
                && in_array($code, $translatableAttributes)) {
                /** @var Store $store */
                foreach ($this->_stores as $store) {
                    $header[] = $this->getLngKey($store->getConfig(self::LOCALE_CODE), $code);
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

            echo "Saved " . $selected . " from line " . $offset . "\n";
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

        $time_start = microtime(true);
        /** @var Collection $collection */
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter(Product::STATUS, ['eq' => Status::STATUS_ENABLED])
            ->addAttributeToFilter(Product::VISIBILITY, ['in' => [
                Visibility::VISIBILITY_BOTH,
                Visibility::VISIBILITY_IN_CATALOG
            ]]);
        $collection->addUrlRewrite();
        $collection->addFieldToFilter("entity_id",array("gt" => $offset));
        $collection->getSelect()->limit($limit, 0);

        /** @var Product $item */
        foreach ($collection as $item) {
            $line = $this->readLine($item, $additionalAttributes);
            fputcsv($file, $this->fillLine($line), ',');
        }

        $memory = memory_get_usage();
        $this->_logger->debug('MEMORY USED '.$memory.'. Full export execution time in seconds: '.(microtime(true) - $time_start));

        return [
            'count' => $collection->count(),
            'last' => $collection->getLastItem()->getEntityId()
        ];
    }

    /**
     * @param Product $_product
     * @param mixed $additionalAttributes
     *
     * @return array
     */
    public function readLine(Product $_product, $additionalAttributes)
    {
        $rowData = [
            'name' => $_product->getName(),
            'url' => $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $_product->getData('url_key') . ".html",
            'sku' => $_product->getData('sku'),
            'group_id' => $_product->getData('sku'),
            'price' => $_product->getData('price') ?: 0,
            'in_stock' => $this->getStockItem($_product->getId())->getIsInStock() ? "true" : "false",
            'categories' => $this->buildCategories($_product),
            'image_url' => $_product->getImage() ? $_product->getMediaConfig()->getMediaUrl($_product->getImage()) : null
        ];

        $storeIds = $_product->getStoreIds();
        $currentStore = $_product->getStore();


        /** @var Attribute $attribute */
        foreach ($additionalAttributes as $attribute) {
            $rowData[$attribute->getAttributeCode()] = $this->buildAttributeData($_product, $attribute);
        }

        foreach ($storeIds as $storeId) {
            /** @var Store $store */
            if (isset($this->_stores[$storeId])) {
                $store = $this->_stores[$storeId];
                $this->_storeManager->setCurrentStore($store);
                $langCode = $store->getConfig(self::LOCALE_CODE);

                /** @var Product $storeProduct */
                $storeProduct = $this->_productFactory->create();
                $this->_productResource->load($storeProduct, $_product->getId());

                $rowData[$this->getLngKey($langCode, 'categories')] = $this->buildCategories($storeProduct);
                $rowData[$this->getLngKey($langCode, 'url')] = $storeProduct->getUrlInStore([
                    '_store' => $store
                ]);

                /** @var Attribute $attribute */
                foreach ($additionalAttributes as $attribute) {
                    $field = $this->getLngKey($langCode, $attribute->getAttributeCode());
                    $rowData[$field] = $this->buildAttributeData($_product, $attribute);
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
     * @param Product $product
     * @return string
     */
    protected function buildCategories(Product $product)
    {
        $collection = $product->getCategoryCollection()
            ->addNameToResult()
            ->getItems();

        return join('|', array_map(function ($category) {
            /** @var Category $category */
            return $category->getName();
        }, $collection));
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

        if ($attribute->getOptions() && !is_array($attributeData)) {
            $attributeData = $attribute->getFrontend()->getValue($product);
        }

        if (is_array($attributeData)) {
            $attributeData = join("|", $attributeData);
        }

        return $attributeData;
    }
}
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
     * Export constructor
     *
     * @param State $state
     * @param ObjectManagerInterface $objectManager
     * @param StoreManager $storeManager
     * @param Product $product
     * @param ProductResource $productResource
     * @param ProductFactory $productFactory
     */
    public function __construct(
        State $state,
        ObjectManagerInterface $objectManager,
        StoreManager $storeManager,
        Product $product,
        ProductResource $productResource,
        ProductFactory $productFactory
    )
    {
        $this->_state = $state;
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_productResource = $productResource;
        $this->_productFactory = $productFactory;
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
            $s3->upload(
                $this->_feedHelper->getBucket(),
                $this->_feedHelper->getExportFilename(),
                fopen($this->_feedHelper->getExportFile(), 'r')
            );
        } catch (S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
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

        /** @var Attribute $attribute */
        foreach ($this->_usedProductAttribute->getAttributes() as $attribute) {
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
            $selected = $this->chunkProductExport($file, $limit, $offset);

            $offset += $selected;

            echo "Saved " . $selected . " from line " . $offset . "\n";
        }

        return $this->upload();
    }

    /**
     * @param $file
     * @param int $limit
     * @param int $offset
     * @return int
     */
    public function chunkProductExport($file, $limit = 100, $offset = 0)
    {
        /** @var Collection $collection */
        $collection = $this->_objectManager->create(Collection::class);
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter(Product::STATUS, ['eq' => Status::STATUS_ENABLED])
            ->addAttributeToFilter(Product::VISIBILITY, ['in' => [
                Visibility::VISIBILITY_BOTH,
                Visibility::VISIBILITY_IN_CATALOG
            ]]);

        $collection->getSelect()->limit($limit, $offset);

        /** @var Product $item */
        foreach ($collection as $item) {
            $line = $this->readLine($item);
            fputcsv($file, $this->fillLine($line), ',');
        }

        return $collection->getSize();
    }

    /**
     * @param Product $product
     * @return array
     */
    public function readLine(Product $product)
    {
        /**
         * Fixes issue with not loading product attributes properly
         *
         * @var Product $_product
         */
        $this->_productResource->load($product, $product->getId());
        $_product = $product;

        $rowData = [
            'name' => $_product->getName(),
            'url' => $_product->getProductUrl(),
            'sku' => $_product->getData('sku'),
            'group_id' => $_product->getData('sku'),
            'price' => $_product->getPrice(),
            'in_stock' => $_product->isSaleable() ? "true" : "false",
            'categories' => $this->buildCategories($_product),
            'image_url' => $_product->getImage() ? $_product->getMediaConfig()->getMediaUrl($_product->getImage()) : null
        ];

        $storeIds = $_product->getStoreIds();
        $currentStore = $_product->getStore();
        $attributes = $this->_usedProductAttribute->getAttributes();

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $this->_excludedAttributes)) {
                $rowData = array_merge($rowData, $this->buildAttributeData($_product, $attribute, $attribute->getAttributeCode()));
            }
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
                foreach ($attributes as $attribute) {
                    if (!in_array($attribute->getAttributeCode(), $this->_excludedAttributes)) {
                        $field = $this->getLngKey($langCode, $attribute->getAttributeCode());

                        $rowData = array_merge($rowData, $this->buildAttributeData($storeProduct, $attribute, $field));
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
     * @param $field
     * @return array
     */
    protected function buildAttributeData(Product $product, EavAttribute $attribute, $field)
    {
        $attributeData = $product->getData($attribute->getAttributeCode());

        if ($attribute->getOptions() && !is_array($attributeData)) {
            foreach ($attribute->getOptions() as $option) {
                if ($attributeData == $option->getValue()) {
                    $attributeData = $option->getLabel();
                }
            }
        }

        if (is_array($attributeData)) {
            $attributeData = join("|", $attributeData);
        }

        return [
            $field => $attributeData
        ];
    }
}
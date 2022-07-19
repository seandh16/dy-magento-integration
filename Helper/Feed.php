<?php

namespace DynamicYield\Integration\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use Magento\Framework\App\Helper\Context;
use DynamicYield\Integration\Helper\Data as DataHelper;
use Magento\Framework\Filesystem\Io\File;

class Feed extends AbstractHelper implements ProductFeedInterface
{
    const FEED_SKIPPED_PRODUCTS = 'dyi_skipped_products.log';
    const S3_BUCKET_REGION = 'us-east-1';
    const S3_BUCKET_REGION_EUROPE = 'eu-central-1';
    const AWS_SDK_VERSION = '2006-03-01';
    const S3_FILE_NAME = 'productfeed.csv';
    const BUCKET = "com.dynamicyield.feeds";
    const EU_BUCKET = "dy-datafeeds-eu";

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var File
     */
    protected $_file;

    /**
     * Base attributes
     *
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

    protected $_customProductAttributes = [
        ProductFeedInterface::FINAL_PRICE,
        ProductFeedInterface::BASE_PRICE,
        ProductFeedInterface::PRODUCT_ID
    ];

    /**
     * Feed constructor
     *
     * @param Context $context
     * @param Data $dataHelper
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        DirectoryList $directoryList,
        File $file
    )
    {
        parent::__construct($context);

        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_dataHelper->isEnabled();
    }

    /**
     * @return mixed
     */
    public function getProductAttributes()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTES);
    }

    /**
     * @return mixed
     */
    public function getUsedProductAttributes()
    {
        return $this->scopeConfig->getValue(self::USED_ATTRIBUTES);
    }

    /**
     * @return mixed
     */
    public function getFeedAttributes()
    {
        return $this->scopeConfig->getValue(self::FEED_ATTRIBUTES);
    }

    /**
     * @return array
     */
    public function getCustomProductAttributes() {
        return $this->_customProductAttributes;
    }

    /**
     * Check if additional attribute is selected
     *
     * @param $attributeCode
     * @return bool
     */
    public function isAttributeSelected($attributeCode)
    {
        $attributeIds = explode(',', $this->getFeedAttributes() ?? '');
        if($attributeIds && in_array($attributeCode,$attributeIds)) {
            return true;
        }
        return false;
    }

    /**
     * Check if enterprise edition
     *
     * @return bool
     */
    public function isEnterpriseEdition()
    {
        return $this->_dataHelper->getMagentoEdition() != ProductFeedInterface::EDITION_COMMUNITY;
    }

    /**
     * Get Base Attributes
     *
     * @return array
     */
    public function getBaseAttributes()
    {
        return $this->_baseAttributes;
    }

    /**
     * @return mixed
     */
    public function getAccessKey()
    {
        return $this->scopeConfig->getValue(self::ACCESS_KEY);
    }

    /**
     * @return mixed
     */
    public function getAccessKeyId()
    {
        return $this->scopeConfig->getValue(self::ACCESS_KEY_ID);
    }

    /**
     * Returns the S3 bucket
     *
     * @return mixed
     */
    public function getBucket()
    {
        return ($this->_dataHelper->isEuropeAccount() || $this->_dataHelper->isEuropeCDNIntegration()) ? self::EU_BUCKET : self::BUCKET;
    }

    /**
     * @return string
     */
    public function getExportPath()
    {
        return $this->_directoryList->getPath(DirectoryList::VAR_DIR) . '/dyi_export/';
    }

    /**
     * Get excluded categories
     *
     * @return array
     */
    public function getExcludedCategories()
    {
        return explode(',', $this->_dataHelper->getExcludedCategories() ?? '');
    }

    /**
     * Get Excluded Category IDs
     *
     * @param $storeId
     * @return array
     */
    public function getCategoryTree($storeId)
    {
        return  $this->_dataHelper->getCategoryTree($storeId) != '' ?  explode(',', $this->_dataHelper->getCategoryTree($storeId) ?? '') : null;
    }


    /**
     * @return string
     */
    public function getExportFilename()
    {
        return self::S3_FILE_NAME;
    }

    /**
     * @return string
     */
    public function getExportFile()
    {
        return $this->getExportPath() . $this->getExportFilename();
    }

    /**
     * @return bool
     */
    public function getIsDebugMode()
    {
        return $this->scopeConfig->getValue(self::DEBUG_MODE);
    }


    /**
     * @return string
     */
    public function getFeedLogFile()
    {
        $file = $this->getExportPath().static::FEED_SKIPPED_PRODUCTS;
        return $file;
    }

    /**
     * @return bool
     */
    public function isSkippedProducts(){
        return $this->_file->fileExists($this->getFeedLogFile()) && filesize($this->getFeedLogFile()) != 0;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->_dataHelper->isEuropeAccount() ? static::S3_BUCKET_REGION_EUROPE : static::S3_BUCKET_REGION;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return static::AWS_SDK_VERSION;
    }

    /**
     * @return mixed
     */
    public function getSectionId()
    {
        return $this->scopeConfig->getValue(self::SECTION_ID);
    }

    /**
     * Check if website has multiple active locales
     *
     * @return bool
     */
    public function isMultiLanguage()
    {
        return $this->_dataHelper->isMultiLanguage();
    }

    /**
     * Return store locale for a store view
     *
     * @param $storeId
     * @return mixed
     */
    public function getStoreLocale($storeId)
    {
        return $this->_dataHelper->getStoreLocale($storeId);
    }

    /**
     * @return mixed
     */
    public function isFeedSyncEnabled() {
        return $this->_dataHelper->isFeedSyncEnabled();
    }

    /**
     * @param $string
     * @return string|string[]|null
     */
    public function replaceSpaces($string)
    {
        return preg_replace('/\s+/', '_', $string);
    }

}
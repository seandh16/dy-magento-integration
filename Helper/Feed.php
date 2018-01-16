<?php

namespace DynamicYield\Integration\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use Magento\Framework\App\Helper\Context;
use DynamicYield\Integration\Helper\Data as DataHelper;

class Feed extends AbstractHelper implements ProductFeedInterface
{
    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * Feed constructor
     *
     * @param Context $context
     * @param Data $dataHelper
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        DirectoryList $directoryList
    )
    {
        parent::__construct($context);

        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
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
     * @return mixed
     */
    public function getBucket()
    {
        return 'com.dynamicyield.feeds/' . $this->_dataHelper->getSectionId();
    }

    /**
     * @return string
     */
    public function getExportPath()
    {
        return $this->_directoryList->getPath(DirectoryList::VAR_DIR) . '/dyi_export/';
    }

    /**
     * @return string
     */
    public function getExportFilename()
    {
        return "productfeed.csv";
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
}
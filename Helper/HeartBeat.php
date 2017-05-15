<?php

namespace DynamicYield\Integration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Filesystem\DirectoryList;

class HeartBeat extends AbstractHelper
{
    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * HeartBeat constructor
     *
     * @param Context $context
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList
    )
    {
        parent::__construct($context);

        $this->_directoryList = $directoryList;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->_directoryList->getPath(DirectoryList::VAR_DIR) . '/' . $this->getFileName();
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return "heartbeat.txt";
    }
}
<?php

namespace DynamicYield\Integration\Model\Config\Backend;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use DynamicYield\Integration\Helper\Data as Helper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class EnableFeed extends Value
{
    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_config;

    /**
     * AbstractUpdateRate constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Helper $helper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Helper $helper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_helper = $helper;
        $this->_config = $config;
    }

    /**
     * Enable/Disable product feed cron jobs
     *
     * @return Value
     */
    public function afterSave()
    {
       if($this->getValue() == 0) {
           $this->_helper->setCustomConfig(ProductFeedInterface::CRON_SCHEDULE_PATH, "");
           $this->_helper->setCustomConfig(ProductFeedInterface::HEARTBEAT_SCHEDULE_PATH, "");
       } else {
           $this->_helper->setCustomConfig(ProductFeedInterface::HEARTBEAT_SCHEDULE_PATH, ProductFeedInterface::HEARTBEAT_EXPR);
       }

       return parent::afterSave();
    }
}
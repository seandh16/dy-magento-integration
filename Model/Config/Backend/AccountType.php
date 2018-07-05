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
use DynamicYield\Integration\Model\Config\Source\IntegrationType;
use Magento\Framework\App\Config\Storage\WriterInterface;

class AccountType extends Value
{
    const EUROPE_ACCOUNT = 'europe_account';
    const CDN_INTEGRATION = 'cdn_integration';

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_config;

    /**
     * @var WriterInterface
     */
    protected $_configWriter;

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
     * @param WriterInterface $configWriter
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
        WriterInterface $configWriter,
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
        $this->_configWriter = $configWriter;
    }

    /**
     * Enable/Disable product feed cron jobs
     *
     * @return Value
     */
    public function afterSave()
    {
        $value = $this->getValue();

        if($value) {
           if($this->getField() == self::EUROPE_ACCOUNT) {
               $this->_configWriter->save('dyi_integration/integration/cdn_integration', IntegrationType::CDN_DISABLED, $this->getScope(), $this->getScopeId());
           } elseif($this->getField() == self::CDN_INTEGRATION) {
               $this->_configWriter->save('dyi_integration/integration/europe_account', '0', $this->getScope(), $this->getScopeId());
           }
        }

        return parent::afterSave();
    }
}
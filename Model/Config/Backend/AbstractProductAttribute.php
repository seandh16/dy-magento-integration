<?php

namespace DynamicYield\Integration\Model\Config\Backend;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use DynamicYield\Integration\Helper\Data as Helper;

abstract class AbstractProductAttribute extends Value
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_config;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * UsedProductAttribute constructor
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

        $this->_config = $config;
        $this->_helper = $helper;
    }

    /**
     * @return array
     */
    abstract public function getAttributes();

    /**
     * @return array
     */
    abstract public function getUsedAttributes();

    /**
     * @return array
     */
    public function getFeedAttributes()
    {
        return array_filter(
            explode(',', $this->_config->getValue(ProductFeedInterface::FEED_ATTRIBUTES) ?? '')
        );
    }

    /**
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $attributes = $this->getAttributes();
        $feedAttributes = $this->getFeedAttributes();
        $newAttributes = array_unique(
            array_merge($attributes, $feedAttributes)
        );

        if (count($newAttributes) > 10) {
            throw new ValidatorException(__('You can\'t select more than 10 additional attributes'));
        }

        return parent::beforeSave();
    }

    /**
     * @return Value
     */
    public function afterSave()
    {
        $attributes = $this->getAttributes();
        $feedAttributes = $this->getFeedAttributes();

        $newAttributes = array_unique(
            array_merge($attributes, $feedAttributes)
        );

        if ($attributes && $this->getPath() != ProductFeedInterface::ATTRIBUTES) {
            $this->_helper->setCustomConfig(ProductFeedInterface::ATTRIBUTES, '');
        }

        if ($feedAttributes != $newAttributes) {
            $this->_helper->setCustomConfig(ProductFeedInterface::FEED_ATTRIBUTES, implode(',', $newAttributes));
        }

        return parent::afterSave();
    }
}

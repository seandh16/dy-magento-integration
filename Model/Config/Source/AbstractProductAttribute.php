<?php

namespace DynamicYield\Integration\Model\Config\Source;

use DynamicYield\Integration\Helper\Data as Helper;
use DynamicYield\Integration\Helper\Feed as FeedHelper;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Option\ArrayInterface;

abstract class AbstractProductAttribute implements ArrayInterface
{
    /**
     * @var Attribute
     */
    protected $_attribute;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var FeedHelper
     */
    protected $_feedHelper;

    /**
     * ProductAttribute constructor
     *
     * @param Attribute $attribute
     * @param Helper $helper
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        Attribute $attribute,
        Helper $helper,
        FeedHelper $feedHelper
    )
    {
        $this->_attribute = $attribute;
        $this->_helper = $helper;
        $this->_feedHelper = $feedHelper;
    }

    /**
     * @return mixed
     */
    abstract function getAttributes();


}
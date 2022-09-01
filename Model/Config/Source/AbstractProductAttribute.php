<?php

namespace DynamicYield\Integration\Model\Config\Source;

use DynamicYield\Integration\Helper\Data as Helper;
use DynamicYield\Integration\Helper\Feed as FeedHelper;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;

abstract class AbstractProductAttribute implements OptionSourceInterface
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

    protected Collection $attributeCollection;

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
        FeedHelper $feedHelper,
        Collection $attributeCollection
    )
    {
        $this->_attribute = $attribute;
        $this->_helper = $helper;
        $this->_feedHelper = $feedHelper;
        $this->attributeCollection = $attributeCollection;
    }

    /**
     * @return mixed
     */
    abstract function getAttributes();


}
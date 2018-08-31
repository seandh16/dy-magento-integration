<?php

namespace DynamicYield\Integration\Model\Config\Source;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class UsedProductAttribute extends AbstractProductAttribute
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->getAttributes();
        $data = [];

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $data[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getAttributeCode() . " ({$attribute->getFrontend()->getLabel()})"
            ];
        }

        if($this->_feedHelper->isFinalPriceSelected()) {
            $data[] = array(
                "label" => ProductFeedInterface::FINAL_PRICE . " (Final Price)",
                "value" => ProductFeedInterface::FINAL_PRICE
            );
        }

        return $data;
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
        $attributes = array_unique(array_merge($this->_feedHelper->getBaseAttributes(), explode(',', $this->_feedHelper->getFeedAttributes())));

        $collection = $this->_attribute->getCollection()
            ->join(Attribute::ENTITY, Attribute::ENTITY .'.attribute_id = main_table.attribute_id', '*')
            ->addFieldToFilter('entity_type_id', ['eq' => ProductFeedInterface::EAV_ENTITY_TYPE])
            ->addFieldToFilter('attribute_code', ['in' => $attributes]);

        return $collection;
    }
}
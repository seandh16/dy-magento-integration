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

        foreach ($this->_feedHelper->getCustomProductAttributes() as $customAttribute) {
            if($this->_feedHelper->isAttributeSelected($customAttribute)) {
                $data[] = array(
                    "label" => $customAttribute . " (".$customAttribute.")",
                    "value" => $customAttribute
                );
            }
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
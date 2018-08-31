<?php

namespace DynamicYield\Integration\Model\Config\Source;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;


class ProductAttribute extends AbstractProductAttribute
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

        $data[] = array(
            "label" => ProductFeedInterface::FINAL_PRICE . " (Final Price)",
            "value" => ProductFeedInterface::FINAL_PRICE
        );

        return $data;
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
        $attributes = explode(',', $this->_feedHelper->getProductAttributes());
        $usedAttributes = explode(',', $this->_feedHelper->getUsedProductAttributes());
        $feedAttributes = explode(',', $this->_feedHelper->getFeedAttributes());

        $newAttributes = array_unique(
            array_merge($attributes, $feedAttributes)
        );
        $newAttributes = array_diff($newAttributes, $usedAttributes);
        $excludedAttributes = array_unique(
            array_merge($newAttributes, $this->_helper->getDefaultAttributes())
        );

        $collection = $this->_attribute->getCollection()
                ->addFieldToFilter('entity_type_id', ['eq' => ProductFeedInterface::EAV_ENTITY_TYPE])
                ->addFieldToFilter('attribute_code', ['nin' => $excludedAttributes]);

        return $collection;
    }
}
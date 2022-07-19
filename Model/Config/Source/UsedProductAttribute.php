<?php

namespace DynamicYield\Integration\Model\Config\Source;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use DynamicYield\Integration\Helper\Data as Helper;
use DynamicYield\Integration\Helper\Feed as FeedHelper;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;

class UsedProductAttribute extends AbstractProductAttribute
{
    /**
     * @var AttributeCollectionFactory
     */
    private AttributeCollectionFactory $attributeFactory;

    public function __construct(Attribute $attribute, Helper $helper, FeedHelper $feedHelper, AttributeCollectionFactory $attributeFactory)
    {
        parent::__construct($attribute, $helper, $feedHelper);
        $this->attributeFactory = $attributeFactory;
    }

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
        $attributes = array_unique(array_merge($this->_feedHelper->getBaseAttributes(), explode(',', $this->_feedHelper->getFeedAttributes() ?? '')));

        $collection = $this->attributeFactory->create();
        $collection->join(Attribute::ENTITY, Attribute::ENTITY .'.attribute_id = main_table.attribute_id', '*')
            ->addFieldToFilter('entity_type_id', ['eq' => ProductFeedInterface::EAV_ENTITY_TYPE])
            ->addFieldToFilter('attribute_code', ['in' => $attributes]);

        return $collection;
    }
}

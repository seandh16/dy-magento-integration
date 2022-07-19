<?php

namespace DynamicYield\Integration\Model\Config\Backend;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;

class UsedProductAttribute extends AbstractProductAttribute
{
    /**
     * @return array
     */
    public function getAttributes()
    {
        return array_filter(
            explode(',', $this->_config->getValue(ProductFeedInterface::ATTRIBUTES) ?? '')
        );
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        $value = $this->getValue();

        if (!is_array($value)) {
            $value = array_filter(
                explode(',', $value ?? '')
            );
        }

        return $value;
    }
}

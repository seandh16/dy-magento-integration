<?php

namespace DynamicYield\Integration\Model\Config\Backend;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

class ProductAttribute extends AbstractProductAttribute
{
    /**
     * @return array
     */
    public function getAttributes()
    {
        $value = $this->getValue();

        if (!is_array($value)) {
            $value = array_filter(
                explode(',', $value ?? '')
            );
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return array_filter(
            explode(',', $this->_config->getValue(ProductFeedInterface::USED_ATTRIBUTES) ?? '')
        );
    }
}

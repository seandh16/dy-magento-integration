<?php

namespace DynamicYield\Integration\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class UpdateRateTypeAttribute implements ArrayInterface
{
    /**
     * @var array
     */
    protected $_rates = [
        60 => 'Hours',
        1440 => 'Days',
        10080 => 'Weeks'
    ];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->_rates as $key => $rate) {
            $options[] = [
                'value' => $key,
                'label' => $rate
            ];
        }

        return $options;
    }
}
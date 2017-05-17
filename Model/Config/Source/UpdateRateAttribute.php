<?php

namespace DynamicYield\Integration\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class UpdateRateAttribute implements ArrayInterface
{
    /**
     * @var array
     */
    protected $_rates = [
        60 => 'Hours',
        1440 => 'Days'
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
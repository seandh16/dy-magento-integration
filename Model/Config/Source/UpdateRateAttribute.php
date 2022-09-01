<?php

namespace DynamicYield\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class UpdateRateAttribute implements OptionSourceInterface
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
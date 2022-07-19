<?php

namespace DynamicYield\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class IntegrationType implements OptionSourceInterface
{
    const CDN_DISABLED = 'No';
    const CDN_ENABLED = 'Yes';
    const CDN_EUROPEAN = 'European';


    /**
     * Integration Type Options
     *
     * @return array
     */
    protected function getOptions() {
        return array(static::CDN_DISABLED => static::CDN_DISABLED, static::CDN_ENABLED => static::CDN_ENABLED, static::CDN_EUROPEAN => static::CDN_EUROPEAN);
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        $options = array();

        foreach ($this->getOptions() as $key => $value) {
            $options[] = array('value' => $key, 'label' => $value);
        }

        return $options;
    }

    /**
     * Return options as key => value
     *
     * @return array
     */
    public function toArray() {
        return $this->getOptions();
    }
}

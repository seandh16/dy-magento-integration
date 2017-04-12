<?php

namespace DynamicYield\Integration\Model;

abstract class Event
{
    /**
     * @return string
     */
    abstract function getName();

    /**
     * @return string
     */
    abstract function getType();

    /**
     * @return array
     */
    abstract function getDefaultProperties();

    /**
     * @return array
     */
    abstract function generateProperties();

    /**
     * @return array
     */
    public function build()
    {
        $properties = array_replace((array) $this->getDefaultProperties(), (array) $this->generateProperties());
        $properties['dyType'] = $this->getType();

        return [
            'name' => $this->getName(),
            'properties' => $properties
        ];
    }
}
<?php

namespace DynamicYield\Integration\Model;


abstract class Event
{
    /**
     * @return mixed
     */
    abstract function getName();

    /**
     * @return mixed
     */
    abstract function getType();

    /**
     * @return mixed
     */
    abstract function getDefaultProperties();

    /**
     * @return mixed
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
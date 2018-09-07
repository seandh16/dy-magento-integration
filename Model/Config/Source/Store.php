<?php

namespace DynamicYield\Integration\Model\Config\Source;

use Magento\Store\Model\System\Store as SystemStore;
use Magento\Framework\Option\ArrayInterface;

class Store implements ArrayInterface
{
    protected $_store;


    /**
     * Store constructor.
     * @param SystemStore $store
     */
    public function __construct(
        SystemStore $store
    )
    {
        $this->_store = $store;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return $this->_store->getStoreValuesForForm();
    }
}
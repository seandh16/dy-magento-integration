<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Wishlist\Model\Item;

class AddToWishlistEvent extends Event
{
    /**
     * @var Item
     */
    protected $_item;

    /**
     * @return string
     */
    function getName()
    {
        return "Add to Wishlist";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "add-to-wishlist-v1";
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'productId' => null
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        return [
            'productId' => $this->_item->getProduct()->getSku()
        ];
    }

    /**
     * @param Item $item
     */
    public function setItem(Item $item)
    {
        $this->_item = $item;
    }
}
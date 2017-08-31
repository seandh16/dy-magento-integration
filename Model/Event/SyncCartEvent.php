<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Checkout\Model\Cart;

class SyncCartEvent extends Event
{
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * AddToCartEvent constructor
     * @param Cart $cart
     */
    public function __construct(
        Cart $cart
    )
    {
        $this->_cart = $cart;
    }

    /**
     * @return string
     */
    function getName()
    {
        return "Sync Cart";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "sync-cart-v1";
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'cart' => [],
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        return [
            'cart' => $this->getCartItems($this->_cart),
        ];
    }
}
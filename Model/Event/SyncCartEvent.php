<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Checkout\Model\Cart;
use DynamicYield\Integration\Helper\Data;

class SyncCartEvent extends Event
{
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * AddToCartEvent constructor
     * @param Cart $cart
     * @param Data $data
     */
    public function __construct(
        Cart $cart,
        Data $data
    )
    {
        $this->_cart = $cart;
        $this->_dataHelper = $data;
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
            'cart' => $this->getCartItems($this->_cart,$this->_dataHelper),
        ];
    }
}
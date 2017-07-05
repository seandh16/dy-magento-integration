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
            'uniqueRequestId' => '',
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        return [
            'uniqueRequestId' => $this->generateRandomString(10),
            'cart' => $this->getCartItems($this->_cart),
        ];
    }

    /**
     * @param int $length
     * @return string
     */
    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
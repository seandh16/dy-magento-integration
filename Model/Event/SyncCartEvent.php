<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Helper\Data;
use DynamicYield\Integration\Model\Event;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class SyncCartEvent extends Event
{
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * AddToCartEvent constructor
     * @param Cart $cart
     * @param Data $data
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        Cart $cart,
        Data $data,
        PriceHelper $priceHelper
    )
    {
        $this->_cart = $cart;
        $this->_dataHelper = $data;
        $this->_priceHelper = $priceHelper;
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
            'cart' => $this->getCartItems($this->_cart, $this->_dataHelper, $this->_priceHelper),
        ];
    }
}
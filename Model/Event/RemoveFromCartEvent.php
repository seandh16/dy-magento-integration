<?php

namespace DynamicYield\Integration\Model\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use DynamicYield\Integration\Model\Event;
use Magento\Quote\Model\Quote\Item;

class RemoveFromCartEvent extends Event
{
    /**
     * @var int
     */
    protected $_cartItem;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * AddToCartEvent constructor
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @return string
     */
    function getName()
    {
        return "Remove from Cart";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "remove-from-cart-v1";
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'value' => 0,
            'currency' => null,
            'productId' => '',
            'quantity' => 0
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        $quote = $this->_checkoutSession->getQuote();

        /** @var Item $item */
        $item = $quote->getItemById($this->_cartItem);

        var_dump($item->debug());

        return [
            'value' => $item->getPrice(),
            'currency' => $quote->getQuoteCurrencyCode(),
            'productId' => $item->getProduct()->getData('sku'),
            'quantity' => round($item->getQty(), 2)
        ];
    }

    /**
     * @param $cartItem
     */
    public function setCartItem($cartItem)
    {
        $this->_cartItem = $cartItem;
    }
}
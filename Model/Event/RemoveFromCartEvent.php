<?php

namespace DynamicYield\Integration\Model\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use DynamicYield\Integration\Model\Event;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * RemoveFromCartEvent constructor
     *
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
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
        $currency = $quote->getQuoteCurrencyCode();

        if (!$currency) {
            $currency = $quote->getStoreCurrencyCode() ?
                $quote->getStoreCurrencyCode() : $quote->getBaseCurrencyCode();
        }

        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        $storeCurrency = $store->getCurrentCurrency();

        return [
            'value' => $item->getPrice(),
            'currency' => $currency ? $currency : $storeCurrency->getCode(),
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
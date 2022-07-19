<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Helper\Data;
use DynamicYield\Integration\Model\Event;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Quote\Model\Quote;
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
     * @var Quote
     */
    protected $_quote;

    /**
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * RemoveFromCartEvent constructor
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Quote $quote
     * @param Data $data
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Quote $quote,
        Data $data,
        PriceHelper $priceHelper
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->_quote = $quote;
        $this->_dataHelper = $data;
        $this->_priceHelper = $priceHelper;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Remove from Cart";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return "remove-from-cart-v1";
    }

    /**
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'value' => 0,
            'currency' => null,
            'productId' => '',
            'quantity' => 0,
            'cart' => [],
        ];
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function generateProperties()
    {
        try {
            $quote = $this->_checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
        }

        /** @var Item $item */
        $item = $quote->getItemById($this->_cartItem);
        $currency = $quote->getQuoteCurrencyCode();

        if (!$item) {
            return $this->getDefaultProperties();
        }

        if (!$currency) {
            $currency = $quote->getStoreCurrencyCode() ?
                $quote->getStoreCurrencyCode() : $quote->getBaseCurrencyCode();
        }

        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        $storeCurrency = $store->getCurrentCurrency();

        $sku = $this->_dataHelper->validateSku($item->getProduct()) ? $item->getProduct()->getSku() : $item->getProduct()->getData('sku');

        return [
            'cart' => $this->getCartItems($quote, $this->_dataHelper, $this->_priceHelper, [$item->getId()]),
            'value' => round($this->_priceHelper->currency($item->getProduct()->getFinalPrice(), false, false), 2),
            'currency' => $currency ? $currency : $storeCurrency->getCode(),
            'productId' => $this->_dataHelper->replaceSpaces($sku),
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

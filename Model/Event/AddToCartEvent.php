<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Cart;
use DynamicYield\Integration\Helper\Data;


class AddToCartEvent extends Event
{
    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var integer
     */
    protected $_qty;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

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
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Cart $cart
     * @param Data $data
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Cart $cart,
        Data $data
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->_cart = $cart;
        $this->_dataHelper = $data;
    }

    /**
     * @return string
     */
    function getName()
    {
        return "Add to Cart";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "add-to-cart-v1";
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
            'quantity' => 0,
            'cart' => [],
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        $product = $this->_product;
        $quote = $this->_checkoutSession->getQuote();

        $currency = $quote->getQuoteCurrencyCode();

        if (!$currency) {
            $currency = $quote->getStoreCurrencyCode() ?
                $quote->getStoreCurrencyCode() : $quote->getBaseCurrencyCode();
        }

        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        $storeCurrency = $store->getCurrentCurrency();

        $valid = $this->_dataHelper->validateSku($product->getSku());

        return [
            'cart' => $this->getCartItems($this->_cart,$this->_dataHelper),
            'value' => $valid != null ? round($valid->getData('price'),2) : round($product->getData('price'),2),
            'currency' => $currency ? $currency : $storeCurrency->getCode(),
            'productId' => $valid != null ? $product->getSku() : $product->getData('sku'),
            'quantity' => round($this->_qty, 2)
        ];
    }

    /**
     * @param Product $product
     * @param $qty
     */
    public function setProduct(Product $product, $qty)
    {
        $this->_product = $product;
        $this->_qty = $qty;
    }
}
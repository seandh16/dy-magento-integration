<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Cart;
use DynamicYield\Integration\Helper\Data;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;


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
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * AddToCartEvent constructor
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Cart $cart
     * @param Data $data
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Cart $cart,
        Data $data,
        PriceHelper $priceHelper
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->_cart = $cart;
        $this->_dataHelper = $data;
        $this->_priceHelper = $priceHelper;
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

        $valid = $this->_dataHelper->validateSku($product);

        return [
            'cart' => $this->getCartItems($this->_cart,$this->_dataHelper,$this->_priceHelper),
            'value' => $valid ? round($this->_priceHelper->currency($valid->getData('price'),false,false),2) : round($this->_priceHelper->currency($product->getData('price'),false,false),2),
            'currency' => $currency ? $currency : $storeCurrency->getCode(),
            'productId' => $valid ? $valid->getSku() : $product->getData('sku'),
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
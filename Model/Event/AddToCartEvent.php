<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Helper\Data;
use DynamicYield\Integration\Model\Event;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;

class AddToCartEvent extends Event
{
    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var integer
     */
    protected $_qty;

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
     * AddToCartEvent constructor
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
        $this->_storeManager = $storeManager;
        $this->_quote = $quote;
        $this->_dataHelper = $data;
        $this->_priceHelper = $priceHelper;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Add to Cart";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return "add-to-cart-v1";
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateProperties()
    {
        $product = $this->_product;
        try {
            $quote = $this->_checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
        }

        $currency = $quote->getQuoteCurrencyCode();

        if (!$currency) {
            $currency = $quote->getStoreCurrencyCode() ?
                $quote->getStoreCurrencyCode() : $quote->getBaseCurrencyCode();
        }

        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        $storeCurrency = $store->getCurrentCurrency();

        $sku = $this->_dataHelper->validateSku($product) ? $product->getSku() : $product->getData('sku');

        return [
            'cart' => $this->getCartItems($quote, $this->_dataHelper, $this->_priceHelper),
            'value' => round(($this->_priceHelper->currency($product->getFinalPrice(), false, false) * $this->_qty), 2),
            'currency' => $currency ? $currency : $storeCurrency->getCode(),
            'productId' => $this->_dataHelper->replaceSpaces($sku),
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

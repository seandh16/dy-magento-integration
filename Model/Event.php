<?php

namespace DynamicYield\Integration\Model;

use Magento\Checkout\Model\Cart;
use DynamicYield\Integration\Helper\Data;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

abstract class Event
{
    /**
     * @return string
     */
    abstract function getName();

    /**
     * @return string
     */
    abstract function getType();

    /**
     * @return array
     */
    abstract function getDefaultProperties();

    /**
     * @return array
     */
    abstract function generateProperties();

    /**
     * @return array
     */
    public function build()
    {
        $properties = array_replace((array) $this->getDefaultProperties(), (array) $this->generateProperties());
        $properties['dyType'] = $this->getType();
        $properties['uniqueRequestId'] =  $this->generateUniqueId();

        return [
            'name' => $this->getName(),
            'properties' => $properties
        ];
    }

    /**
     * Get all cart items
     * @param Cart $cart
     * @param Data $dataHelper
     * @param PriceHelper $priceHelper
     * @param array $except
     * @return array
     */
    public function getCartItems(Cart $cart, Data $dataHelper = null, PriceHelper $priceHelper = null, array $except = [])
    {
        $prepareItems = [];
        $items = [];
        $cartItems = $cart->getQuote()->getAllItems();

        if (!count($cartItems)) {
            return [];
        }

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($cartItems as $item) {

            /**
             * Skip parent product types
             */
            if(in_array($item->getProductType(), array(Type::TYPE_BUNDLE, Data::PRODUCT_GROUPED, Data::PRODUCT_CONFIGURABLE))) {
                continue;
            }

            $sku = $dataHelper ? $dataHelper->replaceSpaces($item->getSku()) : $item->getSku();

            if (in_array($item->getId(), $except) || isset($prepareItems[$sku])) {
                continue;
            }

            $product = $item->getProduct();

            if(!$product || !$dataHelper->validateSku($product)) {
                continue;
            }

            $prepareItems[$sku] = [
                'itemPrice' => round($priceHelper->currency($product->getFinalPrice(),false,false),2),
                'productId' =>  $dataHelper->replaceSpaces($product->getSku()),
                'quantity' => round($item->getQty(), 2)
            ];
        }

        foreach ($prepareItems as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return string
     */
    public function generateUniqueId() {
        $eventId = (string) intval(str_pad(mt_rand(0, 999999999999), 10, '0', STR_PAD_LEFT));
        return $eventId;
    }
}
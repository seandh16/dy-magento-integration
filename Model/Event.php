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
     * @param PriceHelper $priceHelper
     * @param array $except
     * @return array
     */
    public function getCartItems(Cart $cart,PriceHelper $priceHelper = null, array $except = [])
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
             * Skip bundle and grouped products
             */
            if($item->getProductType() == Type::TYPE_BUNDLE || $item->getProductType() == Data::PRODUCT_GROUPED) {
                continue;
            }

            if (in_array($item->getId(), $except) || isset($prepareItems[$item->getSku()])) {
                continue;
            }

            $product = $item->getProduct();

            if(!$product) {
                continue;
            }

            $prepareItems[$item->getSku()] = [
                'itemPrice' => round($priceHelper->currency($product->getData('price'),false,false),2),
                'productId' =>  $product->getSku(),
                'quantity' => round($item->getQty(), 2)
            ];
        }

        foreach ($prepareItems as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return int
     */
    public function generateUniqueId() {
        $eventId = intval(str_pad(mt_rand(0, 999999999999), 10, '0', STR_PAD_LEFT));
        return $eventId;
    }
}
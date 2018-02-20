<?php

namespace DynamicYield\Integration\Model;

use Magento\Checkout\Model\Cart;
use DynamicYield\Integration\Helper\Data;
use Magento\Catalog\Model\Product\Type;

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
     * @param array $except
     * @return array
     */
    public function getCartItems(Cart $cart, Data $dataHelper = null, array $except = [])
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

            $variation = $dataHelper->validateSku($product->getSku());

            /**
             * IF invalid variation and no parent item - skip (because we need parent values)
             * IF valid variation and does not have a parent - skip (because we want need only variation values)
             */
            if(($variation == null && $item->getParentItemId() == null) || ($variation != null && $item->getParentItemId() != null)){
                continue;
            }

            $prepareItems[$item->getSku()] = [
                'itemPrice' => $variation ? round($variation->getData('price'),2) : round($product->getData('price'),2),
                'productId' =>  $variation != null ? $variation->getSku() : ($dataHelper->getParentItemSku($item) ?: ""),
                'quantity' => round($item->getQty(), 2),
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
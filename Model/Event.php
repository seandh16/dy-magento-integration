<?php

namespace DynamicYield\Integration\Model;

use Magento\Checkout\Model\Cart;

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
     * @param array $except
     * @return array
     */
    public function getCartItems(Cart $cart, array $except = [])
    {
        $prepareItems = [];
        $items = [];
        $cartItems = $cart->getQuote()->getAllVisibleItems();

        if (!count($cartItems)) {
            return [];
        }

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($cartItems as $item) {
            if (in_array($item->getId(), $except) || isset($prepareItems[$item->getSku()])) {
                continue;
            }

            $prepareItems[$item->getSku()] = [
                'itemPrice' => $item->getProduct()->getData('price'),
                'productId' => $item->getProduct()->getData('sku'),
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
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

        return [
            'name' => $this->getName(),
            'properties' => $properties
        ];
    }

    /**
     * Get all cart items
     *
     * @param Cart $cart
     * @return array
     */
    public function getCartItems(Cart $cart)
    {
        $items = [];
        $cartItems = $cart->getQuote()
            ->getCollection()
            ->addOrder('created_at', 'ASC');

        if (!count($cartItems)) {
            return [];
        }

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($cartItems as $item) {
            if ($item->isDeleted() && $item->getParentItemId()) {
                continue;
            }

            $items[] = [
                'itemPrice' => $item->getProduct()->getPrice(),
                'productId' => $item->getProduct()->getData('sku'),
                'quantity' => round($item->getQty(), 2),
            ];
        }

        return $items;
    }
}
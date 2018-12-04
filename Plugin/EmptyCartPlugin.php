<?php

namespace DynamicYield\Integration\Plugin;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Checkout\Model\Cart;

class EmptyCartPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * EmptyCartPlugin constructor
     *
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ManagerInterface $eventManager
    )
    {
        $this->_eventManager = $eventManager;
    }

    /**
     * @param Cart $cart
     * @param callable $proceed
     *
     * @return Cart
     */
    public function aroundTruncate(Cart $cart, callable $proceed)
    {
        if ($proceed()) {
            $items = $cart->getQuote()->getItems();
            $itemIds = [];

            if($items) {
                foreach ($items as $item) {
                    /** @var Item $item */
                    $itemIds[] = $item->getId();
                }
            }

            $this->_eventManager->dispatch('dyi_empty_cart', [
                'item_ids' => $itemIds
            ]);
        }

        return $cart;
    }
}
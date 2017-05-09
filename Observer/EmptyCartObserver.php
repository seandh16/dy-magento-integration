<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class EmptyCartObserver extends AbstractObserver
{
    const EVENT_TYPE = 'dyi_empty_cart';

    /**
     * @param Observer $observer
     * @return $this
     */
    function dispatch(Observer $observer)
    {
        $cartItems = $observer->getEvent()->getItemIds();

        foreach ($cartItems as $cartItem) {
            $this->_removeFromCartEvent->setCartItem($cartItem);
            $data = $this->_removeFromCartEvent->build();

            $this->buildResponse([
                'type' => self::EVENT_TYPE,
                'properties' => $data
            ]);
        }

        return $this;
    }
}
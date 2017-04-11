<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class RemoveFromCartObserver extends AbstractObserver
{
    const EVENT_TYPE = 'dyi_remove_item_from_cart';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $cartItem = $observer->getEvent()->getItemId();
        $this->_removeFromCartEvent->setCartItem($cartItem);
        $data = $this->_removeFromCartEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}
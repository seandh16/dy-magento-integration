<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class AddToWishlistObserver extends AbstractObserver
{
    const EVENT_TYPE = 'wishlist_add_product';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $item = $observer->getEvent()->getItem();
        $this->_addToWishlistEvent->setItem($item);
        $data = $this->_addToWishlistEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}
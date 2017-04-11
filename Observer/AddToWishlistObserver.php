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
        $product = $observer->getEvent()->getProduct();
        $this->_addToWishlistEvent->setProduct($product);
        $data = $this->_addToWishlistEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}
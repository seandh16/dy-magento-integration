<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class AddToCartObserver extends AbstractObserver
{
    const EVENT_TYPE = 'checkout_cart_product_add_after';

    /**
     * @param Observer $observer
     * @return mixed
     */
    public function dispatch(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $this->_addToCartEvent->setProduct($product);
        $data = $this->_addToCartEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}
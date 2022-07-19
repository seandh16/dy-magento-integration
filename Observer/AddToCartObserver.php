<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class AddToCartObserver extends AbstractObserver
{
    const EVENT_TYPE = 'dyi_add_item_to_cart';

    /**
     * @param Observer $observer
     * @return mixed
     */
    public function dispatch(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if ($product->getTypeId() == "grouped" || $product->getTypeId() == "bundle") {
            return;
        }
        $qty = $observer->getEvent()->getQty();
        $this->_addToCartEvent->setProduct($product, $qty);
        $data = $this->_addToCartEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}

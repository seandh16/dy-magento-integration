<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class PurchaseObserver extends AbstractObserver
{
    const EVENT_TYPE = 'checkout_onepage_controller_success_action';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $orderId = $observer->getEvent()->getOrderIds();
        $this->_purchaseEvent->setOrder($orderId);
        $data = $this->_purchaseEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}
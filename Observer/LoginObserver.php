<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class LoginObserver extends AbstractObserver
{
    const EVENT_TYPE = 'customer_login';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $this->_loginEvent->setCustomer($customer);
        $data = $this->_loginEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}
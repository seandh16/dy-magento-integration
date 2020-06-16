<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class SignupObserver extends AbstractObserver
{
    const EVENT_TYPE = 'customer_register_success';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if($customer) {
            $this->_signupEvent->setCustomerEmail($customer->getEmail());
            $data = $this->_signupEvent->build();

            return $this->buildResponse([
                'type' => self::EVENT_TYPE,
                'properties' => $data
            ]);
        }
    }
}
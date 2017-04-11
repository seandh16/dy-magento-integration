<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Customer\Model\Data\Customer;

class SignupEvent extends Event
{
    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @return string
     */
    function getName()
    {
        return "Signup";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "signup-v1";
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'hashedEmail' => null
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        return [
            'hashedEmail' => hash('sha256', $this->_customer->getEmail())
        ];
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer)
    {
        $this->_customer = $customer;
    }
}
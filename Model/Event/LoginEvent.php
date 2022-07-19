<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Customer\Model\Customer;

class LoginEvent extends Event
{
    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @return string
     */
    public function getName()
    {
        return "Login";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return "login-v1";
    }

    /**
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'hashedEmail' => null
        ];
    }

    /**
     * @return array
     */
    public function generateProperties()
    {
        return [
            'hashedEmail' => hash('sha256', strtolower($this->_customer->getEmail()))
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

<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Customer\Model\Data\Customer;

class SignupEvent extends Event
{
    /**
     * @var string
     */
    protected $_email;

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
            'hashedEmail' => hash('sha256', strtolower($this->_email))
        ];
    }

    /**
     * @param string
     */
    public function setCustomerEmail($email)
    {
        $this->_email = $email;
    }
}
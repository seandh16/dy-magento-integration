<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;

class SignupEvent extends Event
{
    /**
     * @var string
     */
    protected $_email;

    /**
     * @return string
     */
    public function getName()
    {
        return "Signup";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return "signup-v1";
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

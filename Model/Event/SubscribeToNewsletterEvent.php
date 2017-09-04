<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Newsletter\Model\Subscriber;

class SubscribeToNewsletterEvent extends Event
{
    /**
     * @var Subscriber
     */
    protected $_subscriber;

    /**
     * @return string
     */
    function getName()
    {
        return "Newsletter Subscription";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "newsletter-subscription-v1";
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
            'hashedEmail' => hash('sha256', strtolower($this->_subscriber->getEmail()))
        ];
    }

    /**
     * @param Subscriber $subscriber
     */
    public function setSubscriber(Subscriber $subscriber)
    {
        $this->_subscriber = $subscriber;
    }
}
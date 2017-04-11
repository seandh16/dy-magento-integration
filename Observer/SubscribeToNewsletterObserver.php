<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class SubscribeToNewsletterObserver extends AbstractObserver
{
    const EVENT_NAME = 'dyi_newsletter_subscription_after';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $this->_subscribeToNewsletterEvent->setSubscriber($subscriber);
        $data = $this->_subscribeToNewsletterEvent->build();

        $this->buildResponse([
            'type' => self::EVENT_NAME,
            'properties' => $data
        ]);

        return $data;
    }
}
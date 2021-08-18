<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\Subscriber;

/**
 * Class SubscribeToNewsletterObserver
 * @package DynamicYield\Integration\Observer
 */
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

        if ($subscriber->getStatus() === Subscriber::STATUS_SUBSCRIBED) {
            $this->_subscribeToNewsletterEvent->setSubscriber($subscriber);
            $data = $this->_subscribeToNewsletterEvent->build();
            return $this->buildResponse([
                'type' => self::EVENT_NAME,
                'properties' => $data
            ]);
        }
    }
}

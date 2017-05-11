<?php

namespace DynamicYield\Integration\Plugin;

use Magento\Newsletter\Controller\Subscriber\NewAction;
use Magento\Framework\Event\ManagerInterface;
use Magento\Newsletter\Model\Subscriber;

class NewsletterSubscriptionPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var Subscriber
     */
    protected $_subscriber;

    /**
     * NewsletterSubscriptionPlugin constructor
     *
     * @param ManagerInterface $eventManager
     * @param Subscriber $subscriber
     */
    public function __construct(
        ManagerInterface $eventManager,
        Subscriber $subscriber
    )
    {
        $this->_eventManager = $eventManager;
        $this->_subscriber = $subscriber;
    }

    /**
     * @param NewAction $subject
     * @param $result
     */
    public function afterExecute(NewAction $subject, $result)
    {
        $email = (string)$subject->getRequest()->getParam('email');

        if ($email) {
            $subscriber = $this->_subscriber->loadByEmail($email);

            if ($subscriber) {
                $this->_eventManager->dispatch('dyi_newsletter_subscription_after', [
                    'subscriber' => $subscriber
                ]);
            }
        }
    }
}
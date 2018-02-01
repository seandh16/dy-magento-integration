<?php
namespace DynamicYield\Integration\Plugin;

use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\Event\ManagerInterface;

class NewsletterSubscriptionPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;
    /**
     * NewsletterSubscriptionPlugin constructor
     *
     * @param ManagerInterface $eventManager
     */
    public function __construct(ManagerInterface $eventManager) {
        $this->_eventManager = $eventManager;
    }
    /**
     * @param Subscriber $subscriber
     * @param $status
     * @return mixed
     */
    public function afterSubscribe(Subscriber $subscriber, $status)
    {
        if ($subscriber->isStatusChanged() && $status === Subscriber::STATUS_SUBSCRIBED) {
            $this->_eventManager->dispatch('dyi_newsletter_subscription_after', [
                'subscriber' => $subscriber
            ]);
        }
        return $status;
    }
}
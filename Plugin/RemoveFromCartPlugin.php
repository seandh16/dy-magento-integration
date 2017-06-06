<?php

namespace DynamicYield\Integration\Plugin;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\ManagerInterface;

class RemoveFromCartPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * RemoveFromCartPlugin constructor
     *
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ManagerInterface $eventManager
    )
    {
        $this->_eventManager = $eventManager;
    }

    /**
     * @param Cart $subject
     * @param $itemId
     */
    public function beforeRemoveItem(Cart $subject, $itemId)
    {
        if ($itemId) {
            $this->_eventManager->dispatch('dyi_remove_item_from_cart', [
               'item_id' => $itemId
            ]);
        }
    }
}
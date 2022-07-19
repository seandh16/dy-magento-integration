<?php

namespace DynamicYield\Integration\Plugin;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Checkout\Model\Session;

class EmptyCartPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * EmptyCartPlugin constructor
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
     * @param Session $session
     * @param callable $proceed
     *
     * @return Session
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundTruncate(Session $session, callable $proceed)
    {
        if ($proceed()) {
            $items = $session->getQuote()->getItems();
            $itemIds = [];

            if($items) {
                foreach ($items as $item) {
                    /** @var Item $item */
                    $itemIds[] = $item->getId();
                }
            }

            $this->_eventManager->dispatch('dyi_empty_cart', [
                'item_ids' => $itemIds
            ]);
        }

        return $session;
    }
}

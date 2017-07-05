<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\App\Area;

class FrontendLoadLayoutObserver extends AbstractObserver
{
    const SYNC_CART = 'sync_cart';
    const EVENT_TYPE = 'sync-cart-v1';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        if ($this->_state->getAreaCode() == Area::AREA_FRONTEND) {
            $getSessionId = $this->_customerSession->getData(self::SYNC_CART);

            if ($getSessionId != $this->_customerSession->getSessionId()) {
                $this->buildResponse([
                    'type' => self::EVENT_TYPE,
                    'properties' => $this->_syncCartEvent->build()
                ]);

                $this->_customerSession->setData(self::SYNC_CART, $this->_customerSession->getSessionId());
            }
        }
    }
}
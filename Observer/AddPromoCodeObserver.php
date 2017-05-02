<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class AddPromoCodeObserver extends AbstractObserver
{
    const EVENT_TYPE = 'dyi_set_coupon_code_after';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $this->_addPromoCodeEvent->setQuote($quote);
        $data = $this->_addPromoCodeEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}
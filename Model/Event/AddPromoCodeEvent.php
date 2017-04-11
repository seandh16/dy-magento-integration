<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Quote\Model\Quote;

class AddPromoCodeEvent extends Event
{
    /**
     * @var Quote
     */
    protected $_quote;

    /**
     * @return string
     */
    function getName()
    {
        return 'Promo Code Entered';
    }

    /**
     * @return string
     */
    function getType()
    {
        return 'enter-promo-code-v1';
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'code' => null
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        return [
            'code' => $this->_quote->getCouponCode()
        ];
    }

    /**
     * @param Quote $quote
     */
    public function setQuote(Quote $quote)
    {
        $this->_quote = $quote;
    }
}
<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Helper\Data;
use DynamicYield\Integration\Model\Event;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Checkout\Model\Session as CheckoutSession;

class SyncCartEvent extends Event
{
    /**
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * AddToCartEvent constructor
     * @param Data $data
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Data $data,
        PriceHelper $priceHelper
    ) {
        $this->_dataHelper = $data;
        $this->_priceHelper = $priceHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Sync Cart";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return "sync-cart-v1";
    }

    /**
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'cart' => [],
        ];
    }

    /**
     * @return array
     */
    public function generateProperties()
    {
        return [
            'cart' => $this->getCartItems($this->checkoutSession->getQuote(), $this->_dataHelper, $this->_priceHelper),
        ];
    }
}

<?php

namespace DynamicYield\Integration\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\CouponManagement;
use Magento\Quote\Model\Quote;
use Magento\Framework\Event\ManagerInterface;

class SetCouponCodePlugin
{
    /**
     * @var CartRepositoryInterface
     */
    protected $_cartRepository;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * SetCouponCodePlugin constructor
     *
     * @param CartRepositoryInterface $cartRepository
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        ManagerInterface $eventManager
    )
    {
        $this->_cartRepository = $cartRepository;
        $this->_eventManager = $eventManager;
    }

    /**
     * @param CouponManagement $couponManagement
     * @param callable $proceed
     * @param $cartId
     * @param $couponCode
     */
    public function aroundSet(CouponManagement $couponManagement, callable $proceed, $cartId, $couponCode)
    {
        if ($proceed($cartId, $couponCode) === true) {
            /** @var Quote $quote */
            $quote = $this->_cartRepository->getActive($cartId);

            if ($quote->getId()) {
                $this->_eventManager->dispatch('dyi_set_coupon_code_after', [
                    'quote' => $quote,
                    'code' => $couponCode
                ]);
            }
        }
    }
}
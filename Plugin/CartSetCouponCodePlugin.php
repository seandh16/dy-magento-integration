<?php

namespace DynamicYield\Integration\Plugin;

use Magento\Checkout\Controller\Cart\CouponPost;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Event\ManagerInterface;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class CartSetCouponCodePlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var CouponFactory
     */
    protected $_couponFactory;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * CartSetCouponCodePlugin constructor
     *
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ManagerInterface $eventManager,
        CouponFactory $couponFactory,
        CheckoutSession $checkoutSession
    )
    {
        $this->_eventManager = $eventManager;
        $this->_couponFactory = $couponFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param CouponPost $couponPost
     * @param callable $proceed
     * @return CouponPost
     */
    public function aroundExecute(CouponPost $couponPost, callable $proceed)
    {
        if ($proceed() && $couponPost->getRequest()->getParam('remove') != 1) {
            $couponCode = trim($couponPost->getRequest()->getParam('coupon_code'));
            $isValidLength = strlen($couponCode) && strlen($couponCode) <= Cart::COUPON_CODE_MAX_LENGTH;

            if ($isValidLength) {
                /** @var Coupon $coupon */
                $coupon = $this->_couponFactory->create();
                $coupon->loadByCode($couponCode);

                if ($coupon->getId()) {
                    $this->_eventManager->dispatch('dyi_set_coupon_code_after', [
                        'quote' => $this->_checkoutSession->getQuote(),
                        'code' => $couponCode
                    ]);
                }
            }
        }

        return $proceed();
    }
}
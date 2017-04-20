<?php

namespace DynamicYield\Integration\Observer;

use DynamicYield\Integration\Helper\Data;
use DynamicYield\Integration\Model\Event\AddPromoCodeEvent;
use DynamicYield\Integration\Model\Event\AddToCartEvent;
use DynamicYield\Integration\Model\Event\AddToWishlistEvent;
use DynamicYield\Integration\Model\Event\LoginEvent;
use DynamicYield\Integration\Model\Event\PurchaseEvent;
use DynamicYield\Integration\Model\Event\RemoveFromCartEvent;
use DynamicYield\Integration\Model\Event\SearchEvent;
use DynamicYield\Integration\Model\Event\SignupEvent;
use DynamicYield\Integration\Model\Event\SubscribeToNewsletterEvent;
use DynamicYield\Integration\Model\Queue;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

abstract class AbstractObserver implements ObserverInterface
{
    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var Response
     */
    protected $_response;

    /**
     * @var Queue
     */
    protected $_queue;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var AddPromoCodeEvent
     */
    protected $_addPromoCodeEvent;

    /**
     * @var AddToCartEvent
     */
    protected $_addToCartEvent;

    /**
     * @var AddToWishlistEvent
     */
    protected $_addToWishlistEvent;

    /**
     * @var LoginEvent
     */
    protected $_loginEvent;

    /**
     * @var PurchaseEvent
     */
    protected $_purchaseEvent;

    /**
     * @var RemoveFromCartEvent
     */
    protected $_removeFromCartEvent;

    /**
     * @var SearchEvent
     */
    protected $_searchEvent;

    /**
     * @var SignupEvent
     */
    protected $_signupEvent;

    /**
     * @var SubscribeToNewsletterEvent
     */
    protected $_subscribeToNewsletterEvent;

    /**
     * AbstractObserver constructor
     *
     * @param Request $request
     * @param Response $response
     * @param Queue $queue
     * @param Data $helper
     * @param AddPromoCodeEvent $addPromoCodeEvent
     * @param AddToCartEvent $addToCartEvent
     * @param AddToWishlistEvent $addToWishlistEvent
     * @param LoginEvent $loginEvent
     * @param PurchaseEvent $purchaseEvent
     * @param RemoveFromCartEvent $removeFromCartEvent
     * @param SearchEvent $searchEvent
     * @param SignupEvent $signupEvent
     * @param SubscribeToNewsletterEvent $subscribeToNewsletterEvent
     */
    public function __construct(
        Request $request,
        Response $response,
        Queue $queue,
        Data $helper,
        AddPromoCodeEvent $addPromoCodeEvent,
        AddToCartEvent $addToCartEvent,
        AddToWishlistEvent $addToWishlistEvent,
        LoginEvent $loginEvent,
        PurchaseEvent $purchaseEvent,
        RemoveFromCartEvent $removeFromCartEvent,
        SearchEvent $searchEvent,
        SignupEvent $signupEvent,
        SubscribeToNewsletterEvent $subscribeToNewsletterEvent
    )
    {
        $this->_request = $request;
        $this->_response = $response;
        $this->_queue = $queue;
        $this->_helper = $helper;
        $this->_addPromoCodeEvent = $addPromoCodeEvent;
        $this->_addToCartEvent = $addToCartEvent;
        $this->_addToWishlistEvent = $addToWishlistEvent;
        $this->_loginEvent = $loginEvent;
        $this->_purchaseEvent = $purchaseEvent;
        $this->_removeFromCartEvent = $removeFromCartEvent;
        $this->_searchEvent = $searchEvent;
        $this->_signupEvent = $signupEvent;
        $this->_subscribeToNewsletterEvent = $subscribeToNewsletterEvent;
    }

    /**
     * @param Observer $observer
     * @return mixed
     */
    abstract function dispatch(Observer $observer);

    /**
     * @param array $data
     */
    public function buildResponse(array $data)
    {
        if ($this->_request->isAjax()
            && $data['type'] != AddPromoCodeObserver::EVENT_TYPE) {
            $this->_response->setHeader(strtolower($this->_helper->getEventName()), json_encode($data['properties']));
        } else {
            $this->_queue->addToQueue($data);
        }
    }

    /**
     * @param Observer $observer
     * @return mixed
     */
    public function execute(Observer $observer)
    {
        return $this->dispatch($observer);
    }
}
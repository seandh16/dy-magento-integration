<?php

namespace DynamicYield\Integration\Controller\Synccart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use DynamicYield\Integration\Model\Event\SyncCartEvent as Synccartevent;

class Index extends Action
{

    const SYNC_CART = 'sync_cart';

    /**
     * @var JsonFactory
     */
    protected $_jsonFactory;


    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Synccartevent
     */
    protected $_syncCartEvent;
    /**
     * Index constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Session $session
     * @param Synccartevent $event
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Session $session,
        Synccartevent $event
    )
    {
        parent::__construct($context);

        $this->_jsonFactory = $jsonFactory;
        $this->_customerSession = $session;
        $this->_syncCartEvent = $event;
    }

    /**
     * Return session status if true/false
     * Return Sync Cart data if false
     *
     * @return Json
     */
    public function execute()
    {
        $getSessionId = $this->_customerSession->getData(self::SYNC_CART);

        if ($getSessionId != $this->_customerSession->getSessionId()) {
            $this->_customerSession->setData(self::SYNC_CART, $this->_customerSession->getSessionId());
        }

        $json = $this->_jsonFactory->create();

        $syncCart = $getSessionId == $this->_customerSession->getSessionId() ? true : false;

        if(!$syncCart) {
            return $json->setData([
                'sync_cart' => $syncCart,
                'eventData' => $this->_syncCartEvent->build()
            ]);
        } else {
            return $json->setData([
                'sync_cart' => $syncCart,
            ]);
        }
    }
}
<?php

namespace DynamicYield\Integration\Controller\Synccart;

use DynamicYield\Integration\Model\Event\SyncCartEvent as Synccartevent;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Index implements HttpGetActionInterface
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
     * @param JsonFactory $jsonFactory
     * @param Session $session
     * @param Synccartevent $event
     */
    public function __construct(
        JsonFactory $jsonFactory,
        Session $session,
        Synccartevent $event
    ) {
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

        $syncCart = $getSessionId == $this->_customerSession->getSessionId();

        if (!$syncCart) {
            return $json->setData([
                'sync_cart' => false,
                'eventData' => $this->_syncCartEvent->build()
            ]);
        } else {
            return $json->setData([
                'sync_cart' => true,
            ]);
        }
    }
}

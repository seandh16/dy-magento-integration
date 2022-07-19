<?php

namespace DynamicYield\Integration\Observer;

use DynamicYield\Integration\Model\Cronjob;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use DynamicYield\Integration\Helper\Feed;

class BackendLoadLayoutObserver implements ObserverInterface
{
    const AUTH_CHECK = 'admin_auth';

    /**
     * @var State
     */
    protected $_state;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var Cronjob
     */
    protected $_cronjob;



    /**
     * @var Feed
     */
    protected $_feedHelper;



    /**
     * BackendLoadLayoutObserver constructor
     *
     * @param State $state
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param Cronjob $cronjob
     * @param Feed $feed
     */
    public function __construct(
        State $state,
        ManagerInterface $messageManager,
        RequestInterface $request,
        Cronjob $cronjob,
        Feed $feed
    )
    {
        $this->_state = $state;
        $this->_messageManager = $messageManager;
        $this->_request = $request;
        $this->_cronjob = $cronjob;
        $this->_feedHelper = $feed;
    }

    /**
     * @return bool
     */
    protected function isAuthPage()
    {
        $currentPage = $this->_request->getModuleName() . '_' . $this->_request->getControllerName();

        return self::AUTH_CHECK == $currentPage;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->_state->getAreaCode() == "adminhtml" && !$this->isAuthPage()) {
            if($this->_feedHelper->isFeedSyncEnabled()) {
                if(!$this->_cronjob->isRunning()){
                    $this->_messageManager->addErrorMessage($this->_cronjob->getMessage());
                }
                if($this->_feedHelper->isSkippedProducts()) {
                    $this->_messageManager->addWarningMessage("DynamicYield Integration: Products missing mandatory attributes. Details: var/dyi_export/dyi_skipped_products.log");
                }
            }
        }
    }
}

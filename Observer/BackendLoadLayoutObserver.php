<?php

namespace DynamicYield\Integration\Observer;

use DynamicYield\Integration\Model\Cronjob;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;

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
     * BackendLoadLayoutObserver constructor
     *
     * @param State $state
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param Cronjob $cronjob
     */
    public function __construct(
        State $state,
        ManagerInterface $messageManager,
        RequestInterface $request,
        Cronjob $cronjob
    )
    {
        $this->_state = $state;
        $this->_messageManager = $messageManager;
        $this->_request = $request;
        $this->_cronjob = $cronjob;
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
        if ($this->_state->getAreaCode() == "adminhtml" &&
            !$this->_cronjob->isRunning() &&
            !$this->isAuthPage()
        ) {
            $this->_messageManager->addErrorMessage($this->_cronjob->getMessage());
        }
    }
}
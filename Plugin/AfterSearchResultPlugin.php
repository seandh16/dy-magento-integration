<?php

namespace DynamicYield\Integration\Plugin;

use Magento\CatalogSearch\Controller\Result\Index;
use Magento\Framework\Event\ManagerInterface;

class AfterSearchResultPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * AfterSearchResultPlugin constructor
     *
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ManagerInterface $eventManager
    )
    {
        $this->_eventManager = $eventManager;
    }

    /**
     * @param Index $subject
     * @param $result
     */
    public function afterExecute(Index $subject, $result)
    {
       $this->_eventManager->dispatch('dyi_search_result_load_after');
    }
}
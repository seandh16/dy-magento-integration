<?php

namespace DynamicYield\Integration\Controller\Storage;

use DynamicYield\Integration\Model\Queue;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    /**
     * @var JsonFactory
     */
    protected $_jsonFactory;

    /**
     * @var Queue
     */
    protected $_queue;

    /**
     * Index constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Queue $queue
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Queue $queue
    )
    {
        parent::__construct($context);

        $this->_jsonFactory = $jsonFactory;
        $this->_queue = $queue;
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $events = $this->_queue->getCollection();
        $json = $this->_jsonFactory->create();
        $this->_queue->clearQueue();

        return $json->setData([
            'events' => $events
        ]);
    }
}
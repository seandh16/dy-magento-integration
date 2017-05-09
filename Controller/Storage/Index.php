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
     * @return JsonFactory|Json
     */
    public function execute()
    {
        $json = $this->_jsonFactory->create();
        $data = $this->getRequest()->getParam('data', []);

        if (is_array($data) && empty($data)) {
            return $json->setData([
                'data' => $this->_queue->getCollection()
            ]);
        }

        $this->_queue->addToQueue(json_decode($data, true));

        return $json->setData([
            'status' => true
        ]);
    }
}
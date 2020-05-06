<?php

namespace DynamicYield\Integration\Model;

use Magento\Customer\Model\Session;

class Queue
{
    const COLLECTION_ID = 'dyi_queue';

    /**
     * @var array
     */
    protected $_collection = [];

    /**
     * @var Session
     */
    protected $_session;

    /**
     * Queue constructor
     *
     * @param Session $session
     */
    public function __construct(
        Session $session
    )
    {
        $this->_session = $session;
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        return json_decode($this->_session->getData(self::COLLECTION_ID),true);
    }

    /**
     * @return bool|int
     */
    public function updateCollection()
    {
        return $this->_session->setData(self::COLLECTION_ID, json_encode($this->_collection));
    }

    /**
     * @param array $data
     * @return bool
     */
    public function addToQueue(array $data)
    {
        $this->_collection = $this->getCollection();
        $this->_collection[] = $data;
        return $this->updateCollection();
    }

    /**
     * @return bool
     */
    public function clearQueue()
    {
        $this->_collection = [];
        return $this->updateCollection();
    }
}
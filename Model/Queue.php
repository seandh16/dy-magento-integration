<?php

namespace DynamicYield\Integration\Model;

use Magento\Catalog\Model\Session;

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
     * Queue constructor.
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
        $data = $this->_session->getData(self::COLLECTION_ID);

        if (!$data || empty($data)) {
            $this->_session->setData(self::COLLECTION_ID, serialize($this->_collection));
        }

        return unserialize($this->_session->getData(self::COLLECTION_ID));
    }

    /**
     * @return bool
     */
    public function updateCollection()
    {
        return $this->_session->setData(self::COLLECTION_ID, serialize($this->_collection));
    }

    /**
     * @param array $data
     * @return bool
     */
    public function addToQueue(array $data)
    {
        $collection = $this->getCollection();

        if (!isset($data['session_id'])) {
            $data['session_id'] = session_id();
        }

        $collection[] = $data;

        $this->_collection = array_map('unserialize',
            array_unique(
                array_map('serialize', $collection)
            )
        );

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
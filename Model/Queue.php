<?php

namespace DynamicYield\Integration\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;

class Queue
{
    const COLLECTION_ID = 'dyi_queue';

    /**
     * @var array
     */
    protected $_collection = [];

    /**
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * Queue constructor.
     * @param CacheInterface $cache
     * @param Session $session
     */
    public function __construct(
        CacheInterface $cache,
        Session $session
    )
    {
        $this->_cache = $cache;
        $this->_session = $session;
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        $data = $this->_cache->load(self::COLLECTION_ID);

        if (!$data || empty($data)) {
            $this->_cache->save(serialize($this->_collection), self::COLLECTION_ID);
        }

        return unserialize($this->_cache->load(self::COLLECTION_ID));
    }

    /**
     * @return bool
     */
    public function updateCollection()
    {
        return $this->_cache->save(serialize($this->_collection), self::COLLECTION_ID);
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
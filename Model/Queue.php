<?php

namespace DynamicYield\Integration\Model;

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
     * Queue constructor.
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->_cache = $cache;
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

        $collection[] = $data;
        $this->_collection = $collection;

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
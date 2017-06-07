<?php

namespace DynamicYield\Integration\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;

class Queue
{
    const FILE_NAME = 'dyi_queue.json';

    /**
     * @var array
     */
    protected $_collection = [];

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var File
     */
    protected $_file;

    /**
     * Queue constructor
     *
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file
    )
    {
        $this->_directoryList = $directoryList;
        $this->_file = $file;
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        return json_decode($this->_file->read($this->getFile()), true);
    }

    /**
     * @return bool|int
     */
    public function updateCollection()
    {
        return $this->_file->write($this->getFile(), json_encode($this->_collection));
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

        $this->_collection = array_unique($collection, SORT_REGULAR);

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

    /**
     * @return string
     */
    protected function getFile()
    {
        $path = $this->_directoryList->getPath(DirectoryList::VAR_DIR);
        $file = $path . '/' . self::FILE_NAME;

        $this->_file->open([
            'path' => $path
        ]);

        if (!$this->_file->fileExists($file)) {
            $this->_file->write($file, json_encode($this->_collection), 0666);
        }

        return $file;
    }
}
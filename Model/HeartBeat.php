<?php

namespace DynamicYield\Integration\Model;

use DynamicYield\Integration\Helper\HeartBeat as HeartBeatHelper;

class HeartBeat
{
    /**
     * @var HeartBeatHelper
     */
    protected $_helper;

    /**
     * HeartBeat constructor
     *
     * @param HeartBeatHelper $helper
     */
    public function __construct(
        HeartBeatHelper $helper
    )
    {
        $this->_helper = $helper;
    }

    /**
     * @throws \Exception
     */
    public function newBeat()
    {
        try {
            fopen($this->_helper->getDirectory(), 'w+');
            $now = new \DateTime();

            file_put_contents($this->_helper->getDirectory(), $now->getTimestamp());
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
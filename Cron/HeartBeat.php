<?php

namespace DynamicYield\Integration\Cron;

use DynamicYield\Integration\Model\HeartBeat as HeartBeatModel;
use Magento\Cron\Model\Schedule;
use Psr\Log\LoggerInterface;

class HeartBeat
{
    /**
     * @var HeartBeatModel
     */
    protected $_heartBeat;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * HeartBeat constructor
     *
     * @param HeartBeatModel $heartBeat
     * @param LoggerInterface $logger
     */
    public function __construct(
        HeartBeatModel $heartBeat,
        LoggerInterface $logger
    )
    {
        $this->_heartBeat = $heartBeat;
        $this->_logger = $logger;
    }

    public function execute(Schedule $schedule)
    {
        try {
            $this->_heartBeat->newBeat();
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }
    }
}
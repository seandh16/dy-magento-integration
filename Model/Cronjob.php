<?php

namespace DynamicYield\Integration\Model;

use Magento\Framework\Phrase;
use DynamicYield\Integration\Helper\HeartBeat;

class Cronjob
{
    /**
     * @var HeartBeat
     */
    protected $_heartBeat;

    /**
     * Cronjob constructor
     * @param HeartBeat $heartBeat
     */
    public function __construct(
        HeartBeat $heartBeat
    )
    {
        $this->_heartBeat = $heartBeat;
    }

    /**
     * @return Phrase
     */
    public function getMessage()
    {
        return __('Cronjobs aren\'t running, please check your cronjob configuration.');
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        $date = new \DateTime();
        $dateTimestamp = $date->getTimestamp();
        $cronTimestamp = false;
        $jobRunning = false;

        try {
            $cronTimestamp = file_get_contents($this->_heartBeat->getDirectory());
        } catch (\Exception $exception) {}

        if ($cronTimestamp) {
            $jobRunning = round(($dateTimestamp - $cronTimestamp) / 60) <= 30;
        }

        return $jobRunning;
    }
}
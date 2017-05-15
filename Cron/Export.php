<?php

namespace DynamicYield\Integration\Cron;

use Magento\Cron\Model\Schedule;
use Psr\Log\LoggerInterface;
use DynamicYield\Integration\Model\Export as ExportModel;

class Export
{
    /**
     * @var ExportModel
     */
    protected $_export;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Export constructor
     *
     * @param ExportModel $export
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExportModel $export,
        LoggerInterface $logger
    )
    {
        $this->_export = $export;
        $this->_logger = $logger;
    }

    /**
     * @param Schedule $schedule
     */
    public function execute(Schedule $schedule)
    {
        try {
            $this->_export->export();
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }
    }
}
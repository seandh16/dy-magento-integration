<?php

namespace DynamicYield\Integration\Model\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     *Logging level
     *@var int
     */
    protected $_loggerType = Logger::DEBUG;
    /**
     *File Name
     *@var string
     */
    protected $fileName = '/var/log/dyi_feed.log';
}

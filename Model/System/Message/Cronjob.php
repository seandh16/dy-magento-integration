<?php

namespace DynamicYield\Integration\Model\System\Message;

use DynamicYield\Integration\Model\Cronjob as CronjobModel;
use Magento\Framework\Notification\MessageInterface;

class Cronjob implements MessageInterface
{
    /**
     * @var CronjobModel
     */
    protected $_cronjob;

    /**
     * Cronjob constructor
     *
     * @param CronjobModel $cronjob
     */
    public function __construct(
        CronjobModel $cronjob
    )
    {
        $this->_cronjob = $cronjob;
    }

    /**
     * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return 'cronjob_heartbeat';
    }

    /**
     * Check whether
     *
     * @return bool
     */
    public function isDisplayed()
    {
        return !$this->_cronjob->isRunning();
    }

    /**
     * Retrieve message text
     *
     * @return string
     */
    public function getText()
    {
        return $this->_cronjob->getMessage();
    }

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }
}
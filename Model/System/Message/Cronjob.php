<?php

namespace DynamicYield\Integration\Model\System\Message;

use DynamicYield\Integration\Model\Cronjob as CronjobModel;
use Magento\Framework\Notification\MessageInterface;
use DynamicYield\Integration\Helper\Feed\Proxy as FeedHelper;

class Cronjob implements MessageInterface
{
    /**
     * @var CronjobModel
     */
    protected $_cronjob;

    /**
     * @var FeedHelper
     */
    protected $_feedHelper;

    /**
     * Cronjob constructor
     *
     * @param CronjobModel $cronjob
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        CronjobModel $cronjob,
        FeedHelper $feedHelper
    )
    {
        $this->_cronjob = $cronjob;
        $this->_feedHelper = $feedHelper;
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
        return !$this->_feedHelper->isFeedSyncEnabled() ? false : !$this->_cronjob->isRunning();
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
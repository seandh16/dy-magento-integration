<?php

namespace DynamicYield\Integration\Model\Config\Backend;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;

class UpdateRateTime extends AbstractUpdateRate
{
    /**
     * @return mixed
     */
    function getTime()
    {
        return $this->getValue();
    }

    /**
     * @return mixed
     */
    function getType()
    {
        return $this->_config->getValue(ProductFeedInterface::UPDATE_RATE_TYPE);
    }
}
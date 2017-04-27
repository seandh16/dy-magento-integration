<?php

namespace DynamicYield\Integration\Api\Data;

interface ProductFeedInterface
{
    const EAV_ENTITY_TYPE = 4;
    const UPDATE_RATE_TIME = 'dyi_integration/feed/update_rate_time';
    const UPDATE_RATE_TYPE = 'dyi_integration/feed/update_rate_type';
    const CRON_SCHEDULE_PATH = 'dyi_integration/feed/schedule/cron_expr';
    const ATTRIBUTES = 'dyi_integration/feed/attributes';
    const USED_ATTRIBUTES = 'dyi_integration/feed/used_attributes';
    const FEED_ATTRIBUTES = 'dyi_integration/feed/feed_attributes';
    const ACCESS_KEY = 'dyi_integration/feed/access_key';
    const ACCESS_KEY_ID = 'dyi_integration/feed/access_key_id';
}
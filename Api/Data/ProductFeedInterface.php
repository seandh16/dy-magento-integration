<?php

namespace DynamicYield\Integration\Api\Data;

interface ProductFeedInterface
{
    const EAV_ENTITY_TYPE = 4;
    const UPDATE_RATE = 'dyi_integration/feed/update_rate';
    const CRON_SCHEDULE_PATH = 'dyi_integration/feed/cron_expr';
    const ATTRIBUTES = 'dyi_integration/feed/attributes';
    const USED_ATTRIBUTES = 'dyi_integration/feed/used_attributes';
    const FEED_ATTRIBUTES = 'dyi_integration/feed/feed_attributes';
    const SECTION_ID = 'dyi_integration/general/section_id';
    const ACCESS_KEY_ID = 'dyi_integration/general/access_key_id';
    const ACCESS_KEY = 'dyi_integration/general/access_key';
    const DEBUG_MODE = 'dyi_integration/feed/dyi_debug_mode';
    const HEARTBEAT_SCHEDULE_PATH= 'dyi_integration/feed/heartbeat_expr';
    const HEARTBEAT_EXPR = '*/5 * * * *';
    const FINAL_PRICE = 'final_price';
    const BASE_PRICE = 'base_price';
    const PRODUCT_ID = 'product_id';
    const EDITION_COMMUNITY = 'Community';
}
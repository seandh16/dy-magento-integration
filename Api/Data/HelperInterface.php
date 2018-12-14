<?php

namespace DynamicYield\Integration\Api\Data;

interface HelperInterface
{
    const SECTION_ID = 'dyi_integration/general/section_id';
    const EVENT_NAME = 'dyi_integration/general/event_name';
    const LOCALE_ENABLE = 'dyi_integration/locale/enable';
    const LOCALE_CUSTOM_ENABLE = 'dyi_integration/locale/enable_custom';
    const LOCALE_CUSTOM_LOCALE = 'dyi_integration/locale/custom';
    const LOCALE_CUSTOM_SELECT = 'dyi_integration/locale/select';
    const PRODUCT_SYNC_ENABLE = 'dyi_integration/feed/enable_feed_sync';
    const CONF_ENABLE_EUROPE_ACCOUNT = 'dyi_integration/integration/europe_account';
    const CONF_ENABLE_CDN_INTEGRATION = 'dyi_integration/integration/cdn_integration';
    const CONF_EXCLUDED_CATEGORIES = 'dyi_integration/feed/excluded_categories';
    const CONF_CATEGORY_ROOT = 'dyi_integration/feed/website_category_root';
    const CONF_CUSTOM_CDN = 'dyi_integration/integration/cdn_url';
    const CONF_DEFAULT_STORE = 'dyi_integration/locale/default_store';
    const DEFAULT_CDN = "cdn.dynamicyield.com";
    const EUROPE_CDN = "cdn-eu.dynamicyield.com";
}
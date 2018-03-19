<?php

namespace DynamicYield\Integration\Api\Data;

interface EventSelectorInterface
{
    const CONFIGURATION_PATH = 'dev/dyi_event_selectors/';

    /**
     * Layered Navigation Selectors
     */
    const LAYERED_NAV_TRIGGER = 'layered_nav_trigger';
    const LAYERED_NAV_SWATCH_TRIGGER = 'layered_nav_swatch_trigger';
    const LAYERED_NAV_TYPE = 'category_page_filters_type';
    const LAYERED_NAV_PRICE_VALUE = 'category_page_filters_price_value';
    const LAYERED_NAV_REGULAR_VALUE = 'category_page_filters_regular_value';
    const LAYERED_NAV_SWATCH_VALUE = 'category_page_filters_swatch_value';
    const LAYERED_NAV_SWATCH_IMAGE_VALUE = 'category_page_filters_swatch_image_value';

    /**
     * Product Page Swatch Selectors
     */
    const PRODUCT_SWATCH_TRIGGER = 'product_page_swatch_trigger';
    const PRODUCT_SWATCH_TYPE = 'product_page_swatch_type';
    const PRODUCT_SWATCH_VALUE = 'product_page_swatch_value';
    const PRODUCT_SWATCH_IMAGE_VALUE = 'product_page_swatch_image_value';

    /**
     * Product Page Attribute Selectors
     */

    const PRODUCT_ATTRIBUTE_TRIGGER = 'product_page_attribute_trigger';
    const PRODUCT_ATTRIBUTE_TYPE = 'product_page_attribute_type';
    const PRODUCT_ATTRIBUTE_VALUE = 'product_page_attribute_value';

    /**
     * Product Custom Options Selectors
     */
    const PRODUCT_CUSTOM_OPTION_TRIGGER = 'product_page_custom_option_trigger';
    const PRODUCT_CUSTOM_OPTION_TYPE = 'product_page_custom_option_type';
    const PRODUCT_CUSTOM_OPTION_VALUE = 'product_page_custom_option_value';
    const PRODUCT_CUSTOM_OPTION_ALT_VALUE = 'product_page_custom_option_alt_value';

    /**
     * Sort Navigation Structure
     */
    const CATEGORY_SORT_ORDER_TRIGGER = 'category_page_sort_order_trigger';
    const CATEGORY_SORT_ORDER_SWITCHER_TRIGGER = 'category_page_sort_order_switcher_trigger';
    const CATEGORY_SORT_ORDER_VALUE = 'category_page_sort_order_by';
    const CATEGORY_SORT_ORDER_DIRECTION = 'category_page_sort_order_direction';


}
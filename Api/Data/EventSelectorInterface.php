<?php

namespace DynamicYield\Integration\Api\Data;

interface EventSelectorInterface
{
    const CONFIGURATION_PATH = 'dev/dyi_event_selectors/';

    /**
     * Layered Navigation Selectors
     */
    const LAYERED_NAV_BLOCK = 'layered_nav_block';
    const LAYERED_NAV_CONTENT = 'layered_nav_content';
    const LAYERED_NAV_TRIGGER = 'layered_nav_trigger';
    const LAYERED_NAV_CONTAINER = 'layered_nav_filter_container';
    const LAYERED_NAV_FILTER_TITLE = 'layered_nav_filter_title';
    const LAYERED_NAV_SWATCH_OPTION = 'layered_nav_filter_swatch_option';
    const LAYERED_NAV_SWATCH_TITLE = 'layered_nav_filter_swatch_title';
    const LAYERED_NAV_SWATCH_DATA_TITLE = 'layered_nav_filter_swatch_data_title';
    const LAYERED_NAV_FILTER_PRICE = 'layered_nav_filter_price';
    const LAYERED_NAV_FILTER_ITEM_COUNT = 'layered_nav_filter_item_count';

    /**
     * Toolbar Selectors
     */
    const TOOLBAR_SORTER_BLOCK = 'toolbar_sorter_block';
    const TOOLBAR_SORTER_TYPE = 'toolbar_sorter_type';
    const TOOLBAR_SORTER_ORDER = 'toolbar_sorter_order';
    const TOOLBAR_SORTER_VALUE = 'toolbar_sorter_order_value';

    /**
     * Product Options Selectors
     */
    const PRODUCT_OPTIONS_CONTAINER = 'product_options_wrapper';
    const PRODUCT_OPTIONS_ATTRIBUTE = 'product_options_regular_attribute';
    const PRODUCT_OPTIONS_ATTRIBUTE_PARENT = 'product_options_regular_attribute_parent';
    const PRODUCT_OPTIONS_ATTRIBUTE_NAME_LABEL = 'product_options_regular_attribute_name_label';
    const PRODUCT_OPTIONS_ATTRIBUTE_NAME_CONTAINER = 'product_options_regular_attribute_name_container';

    /**
     * Product Custom Options Selectors
     */
    const PRODUCT_CUSTOM_OPTIONS_CONTAINER = 'product_custom_options_container';
    const PRODUCT_CUSTOM_OPTIONS_TYPE_SELECT = 'product_custom_options_type_select';
    const PRODUCT_CUSTOM_OPTIONS_TYPE_INPUT = 'product_custom_options_type_input';
    const PRODUCT_CUSTOM_OPTIONS_TYPE_RADIO = 'product_custom_options_type_radio';
    const PRODUCT_CUSTOM_OPTIONS_TYPE_CHECKBOX = 'product_custom_options_type_checkbox';
    const PRODUCT_CUSTOM_OPTIONS_SWATCH_CONTAINER = 'product_custom_options_swatch_container';
    const PRODUCT_CUSTOM_OPTIONS_SWATCH_SELECT = 'product_custom_options_swatch_select';
    const PRODUCT_CUSTOM_OPTIONS_SWATCH_ATTRIBUTE_VALUE = 'product_custom_options_swatch_attribute_value';
    const PRODUCT_CUSTOM_OPTIONS_SWATCH_SELECTED_ATTRIBUTE = 'product_custom_options_swatch_attribute_selected';
    const PRODUCT_CUSTOM_OPTIONS_SWATCH_ATTRIBUTE_PARENT = 'product_custom_options_swatch_attribute_parent';
    const PRODUCT_CUSTOM_OPTIONS_CONTROL = 'product_custom_options_control';
    const PRODUCT_CUSTOM_OPTIONS_CONTROL_PARENT = 'product_custom_options_control_parent';
    const PRODUCT_CUSTOM_OPTIONS_LABEL = 'product_custom_options_label';
    const PRODUCT_CUSTOM_OPTIONS_LABEL_CONTAINER = 'product_custom_options_label_container';
    const PRODUCT_CUSTOM_OPTIONS_SELECT_MULTIPLE = 'product_custom_options_select_multiple';
}
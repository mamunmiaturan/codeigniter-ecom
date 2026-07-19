<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV)
 * @author   : Mamun Mia Turan
 * @filename : eav_helper.php
 *
 * Canonical EAV constants ported from Bagisto's Attribute module. The single
 * most important fact here is eav_value_column(): it maps each attribute type
 * to the physical column of product_attribute_values it reads/writes. This map
 * is the linchpin of EAV correctness and MUST be used everywhere a value is
 * stored or read.
 *
 * This app is single-locale/single-channel: values are stored with locale=NULL
 * and channel=NULL, so unique_id collapses to "{product_id}|{attribute_id}".
 */

if (!function_exists('eav_types')) {
    /** The 11 canonical attribute types (Bagisto AttributeTypeEnum). */
    function eav_types()
    {
        return ['text', 'textarea', 'price', 'boolean', 'checkbox', 'select', 'multiselect', 'date', 'datetime', 'image', 'file'];
    }
}

if (!function_exists('eav_value_column')) {
    /**
     * Resolve which product_attribute_values column a type persists into.
     * Ported byte-for-byte from Bagisto ProductAttributeValue::$attributeTypeFields.
     */
    function eav_value_column($type)
    {
        $map = [
            'text'        => 'text_value',
            'textarea'    => 'text_value',
            'price'       => 'float_value',
            'boolean'     => 'boolean_value',
            'select'      => 'integer_value',
            'multiselect' => 'text_value',
            'datetime'    => 'datetime_value',
            'date'        => 'date_value',
            'file'        => 'text_value',
            'image'       => 'text_value',
            'checkbox'    => 'text_value',
        ];
        return $map[$type] ?? 'text_value';
    }
}

if (!function_exists('eav_is_option_type')) {
    /** Types whose values reference attribute_options (select/multiselect/checkbox). */
    function eav_is_option_type($type)
    {
        return in_array($type, ['select', 'multiselect', 'checkbox'], true);
    }
}

if (!function_exists('eav_is_multi_type')) {
    /** Types that store a comma-joined list of option ids in text_value. */
    function eav_is_multi_type($type)
    {
        return in_array($type, ['multiselect', 'checkbox'], true);
    }
}

if (!function_exists('eav_swatch_types')) {
    /** Presentation of a select/multiselect option list (Bagisto SwatchTypeEnum). */
    function eav_swatch_types()
    {
        return ['dropdown', 'color', 'image', 'text'];
    }
}

if (!function_exists('eav_validations')) {
    /** Value validation rules an admin can attach to a text attribute (ValidationEnum). */
    function eav_validations()
    {
        return ['numeric', 'email', 'decimal', 'url', 'regex'];
    }
}

if (!function_exists('eav_unique_id')) {
    /**
     * Natural upsert key for a value row. Empty channel/locale parts are dropped,
     * so a single-scope value collapses to "{product_id}|{attribute_id}".
     */
    function eav_unique_id($channel, $locale, $product_id, $attribute_id)
    {
        return implode('|', array_filter([$channel, $locale, (int) $product_id, (int) $attribute_id], function ($v) {
            return $v !== null && $v !== '';
        }));
    }
}

if (!function_exists('eav_filterable_types')) {
    /** Types eligible to be marked filterable (drives the admin form gating). */
    function eav_filterable_types()
    {
        return ['select', 'multiselect', 'checkbox', 'boolean', 'price'];
    }
}

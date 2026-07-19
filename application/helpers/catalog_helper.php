<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @author   : Mamun Mia Turan
 * @filename : catalog_helper.php
 *
 * Shared render helpers for the catalog admin DataTables (status toggle + row actions).
 */

if (!function_exists('catalog_status_html')) {
    /**
     * Status toggle button + badge for a catalog list row.
     */
    function catalog_status_html($status, $encrypted_id, $can_edit = true)
    {
        $is_active = ($status === 'Active');
        $icon      = $is_active ? 'fa-toggle-on text-success' : 'fa-toggle-off text-danger';
        $tooltip   = $is_active ? (translate('deactivate') ?: 'Deactivate') : (translate('activate') ?: 'Activate');
        $disabled  = $can_edit ? '' : 'disabled';

        $label = $is_active ? (translate('active') ?: 'Active') : (translate('inactive') ?: 'Inactive');
        if ($status === 'Draft') {
            $label = translate('draft') ?: 'Draft';
        }
        $badge_class = $is_active ? 'success' : ($status === 'Draft' ? 'warning' : 'secondary');

        return '<button type="button" class="btn btn-circle btn-default icon btn-status-toggle" '
            . 'data-id="' . html_escape($encrypted_id) . '" data-active="' . ($is_active ? 1 : 0) . '" '
            . 'data-toggle="tooltip" data-original-title="' . $tooltip . '" ' . $disabled . '>'
            . '<i class="fas ' . $icon . '"></i></button> '
            . '<span class="badge badge-' . $badge_class . '">' . html_escape($label) . '</span>';
    }
}

if (!function_exists('catalog_row_actions')) {
    /**
     * Edit + delete action buttons for a catalog list row.
     *
     * @param string $controller e.g. 'category', 'brand', 'product'
     */
    function catalog_row_actions($controller, $id, $can_edit, $can_delete)
    {
        $html = '';
        if ($can_edit) {
            $html .= '<a href="' . base_url($controller . '/edit/' . encrypt_id($id)) . '" '
                . 'class="btn btn-circle btn-default icon" data-toggle="tooltip" '
                . 'data-original-title="' . (translate('edit') ?: 'Edit') . '"><i class="fas fa-pen-nib"></i></a> ';
        }
        if ($can_delete) {
            $html .= btn_delete($controller . '/delete/' . encrypt_id($id));
        }
        return $html;
    }
}

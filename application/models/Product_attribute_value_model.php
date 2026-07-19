<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV)
 * @author   : Mamun Mia Turan
 * @filename : Product_attribute_value_model.php
 *
 * The EAV value store + runtime, ported from Bagisto's
 * ProductAttributeValueRepository. Every custom attribute value for a product is
 * one row; the attribute type decides which column holds the value
 * (eav_value_column). Single-locale/channel: locale/channel are NULL and
 * unique_id = "{product_id}|{attribute_id}". Written with the raw query builder
 * so bulk value writes don't spam the activity log.
 */
class Product_attribute_value_model extends MY_Model
{
    protected $table = 'product_attribute_values';

    private $_opt_cache = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('eav');
    }

    // ================= Write =================

    /**
     * Upsert a product's attribute values from a submitted code=>value map.
     * $attributes = the product family's attribute rows (each with id/code/type).
     * An attribute submitted empty is cleared; one not submitted is left
     * untouched (except boolean, which always writes 0/1 like Bagisto).
     */
    public function save_values($product_id, $values, $attributes)
    {
        $product_id = (int) $product_id;
        $now = date('Y-m-d H:i:s');

        foreach ($attributes as $attr) {
            $code = $attr['code'];
            $type = $attr['type'];

            // Not submitted → untouched (boolean is the exception: unchecked = 0).
            if (!array_key_exists($code, $values) && $type !== 'boolean') {
                continue;
            }

            $val = $this->prepare_value($type, $values[$code] ?? null);
            $col = eav_value_column($type);
            $uid = eav_unique_id(null, null, $product_id, (int) $attr['id']);

            // Empty non-boolean value clears the stored row.
            if ($type !== 'boolean' && ($val === null || $val === '')) {
                $this->db->where('unique_id', $uid)->delete('product_attribute_values');
                continue;
            }

            $existing = $this->db->where('unique_id', $uid)->get('product_attribute_values')->row_array();
            if ($existing) {
                $this->db->where('id', (int) $existing['id'])->update('product_attribute_values', [$col => $val]);
            } else {
                $row = $this->_null_columns();
                $row[$col]           = $val;
                $row['product_id']   = $product_id;
                $row['attribute_id'] = (int) $attr['id'];
                $row['unique_id']    = $uid;
                $row['created_at']   = $now;
                $this->db->insert('product_attribute_values', $row);
            }
        }
    }

    /** Type-aware preprocessing of a submitted raw value (ported from saveValues). */
    public function prepare_value($type, $raw)
    {
        switch ($type) {
            case 'boolean':
                return empty($raw) ? 0 : 1;

            case 'multiselect':
            case 'checkbox':
                $arr = is_array($raw) ? $raw : (($raw === null || $raw === '') ? [] : [$raw]);
                $ids = array_values(array_filter(array_map('intval', $arr)));
                return empty($ids) ? null : implode(',', $ids);

            case 'select':
                return ($raw === null || $raw === '') ? null : (int) $raw;

            case 'price':
                return ($raw === null || $raw === '') ? null : (float) $raw;

            case 'date':
            case 'datetime':
                return ($raw === null || $raw === '') ? null : $raw;

            default: // text, textarea, image, file
                return ($raw === null) ? null : trim((string) $raw);
        }
    }

    private function _null_columns()
    {
        return [
            'locale' => null, 'channel' => null,
            'text_value' => null, 'boolean_value' => null, 'integer_value' => null,
            'float_value' => null, 'datetime_value' => null, 'date_value' => null, 'json_value' => null,
        ];
    }

    // ================= Read =================

    /**
     * Raw stored value keyed by attribute_id (option id for select, "1,2" csv for
     * multiselect/checkbox, 0/1 for boolean, text/number otherwise). Feeds the
     * admin product form.
     */
    public function get_for_product($product_id)
    {
        $rows = $this->db->select('pav.*, a.type')
            ->from('product_attribute_values pav')
            ->join('attributes a', 'a.id = pav.attribute_id')
            ->where('pav.product_id', (int) $product_id)
            ->get()->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['attribute_id']] = $r[eav_value_column($r['type'])];
        }
        return $out;
    }

    /**
     * Resolved, display-ready attribute values for one product (option ids ->
     * labels, boolean -> Yes/No). Used by the storefront PDP + API.
     * @return array [ ['code','name','type','value'], ... ]
     */
    public function get_display_values($product_id, $only_visible = true)
    {
        $rows = $this->db->select('pav.*, a.code, a.name, a.type, a.is_visible_on_front')
            ->from('product_attribute_values pav')
            ->join('attributes a', 'a.id = pav.attribute_id')
            ->where('pav.product_id', (int) $product_id)
            ->where('a.deleted_at', null)
            ->where('a.status', 'Active')
            ->order_by('a.position', 'ASC')->order_by('a.id', 'ASC')
            ->get()->result_array();

        $out = [];
        foreach ($rows as $r) {
            if ($only_visible && (int) $r['is_visible_on_front'] !== 1) {
                continue;
            }
            $display = $this->_resolve_display($r);
            if ($display === null || $display === '') {
                continue;
            }
            $out[] = ['code' => $r['code'], 'name' => $r['name'], 'type' => $r['type'], 'value' => $display];
        }
        return $out;
    }

    private function _resolve_display($r)
    {
        $type = $r['type'];
        $col  = eav_value_column($type);
        $raw  = $r[$col];
        if ($raw === null || $raw === '') {
            return null;
        }
        switch ($type) {
            case 'boolean':
                return ((int) $raw === 1) ? 'Yes' : 'No';
            case 'select':
                $labels = $this->_option_labels((int) $r['attribute_id']);
                return $labels[(int) $raw] ?? null;
            case 'multiselect':
            case 'checkbox':
                $labels = $this->_option_labels((int) $r['attribute_id']);
                $parts = [];
                foreach (explode(',', $raw) as $id) {
                    $id = (int) $id;
                    if (isset($labels[$id])) {
                        $parts[] = $labels[$id];
                    }
                }
                return $parts ? implode(', ', $parts) : null;
            default:
                return $raw;
        }
    }

    private function _option_labels($attribute_id)
    {
        if (!isset($this->_opt_cache[$attribute_id])) {
            $map = [];
            foreach ($this->db->where('attribute_id', $attribute_id)->get('attribute_options')->result_array() as $o) {
                $map[(int) $o['id']] = $o['label'];
            }
            $this->_opt_cache[$attribute_id] = $map;
        }
        return $this->_opt_cache[$attribute_id];
    }

    /**
     * Batch: product_id => display value list, for a set of product ids.
     * (product grid / API list). Only visible-on-front attributes.
     */
    public function get_for_products($product_ids)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $product_ids)));
        if (empty($ids)) {
            return [];
        }
        $rows = $this->db->select('pav.*, a.code, a.name, a.type, a.is_visible_on_front')
            ->from('product_attribute_values pav')
            ->join('attributes a', 'a.id = pav.attribute_id')
            ->where_in('pav.product_id', $ids)
            ->where('a.deleted_at', null)
            ->where('a.status', 'Active')
            ->where('a.is_visible_on_front', 1)
            ->order_by('a.position', 'ASC')
            ->get()->result_array();
        $out = [];
        foreach ($rows as $r) {
            $display = $this->_resolve_display($r);
            if ($display === null || $display === '') {
                continue;
            }
            $out[(int) $r['product_id']][] = ['code' => $r['code'], 'name' => $r['name'], 'value' => $display];
        }
        return $out;
    }

    // ================= Uniqueness (is_unique validation) =================

    public function is_value_unique($attribute, $value, $exclude_product_id = null)
    {
        // Compare against the normalized (stored) value so e.g. a select's option
        // id matches integer_value rather than a raw string.
        $value = $this->prepare_value($attribute['type'], $value);
        if ($value === null || $value === '') {
            return true; // empty values are not subject to uniqueness
        }
        $col = eav_value_column($attribute['type']);
        $this->db->where('attribute_id', (int) $attribute['id'])->where($col, $value);
        if ($exclude_product_id) {
            $this->db->where('product_id !=', (int) $exclude_product_id);
        }
        return $this->db->count_all_results('product_attribute_values') === 0;
    }

    // ================= Facets (storefront layered nav) =================

    /**
     * Count of Active products holding each option of a filterable attribute,
     * optionally scoped to a category. Returns option_id => count. Type-aware:
     * select stores the option id in integer_value; multiselect/checkbox store a
     * CSV of option ids in text_value (counted per option via FIND_IN_SET).
     */
    public function option_counts($attribute_id, $category_id = null)
    {
        $attribute_id = (int) $attribute_id;
        $attr = $this->db->select('type')->where('id', $attribute_id)->get('attributes')->row_array();
        $type = $attr['type'] ?? 'select';
        $out = [];

        if (eav_is_multi_type($type)) {
            // multiselect/checkbox: count products per option via FIND_IN_SET on the CSV.
            $opts = $this->db->select('id')->where('attribute_id', $attribute_id)->get('attribute_options')->result_array();
            foreach ($opts as $o) {
                $oid = (int) $o['id'];
                $this->db->from('product_attribute_values pav')
                    ->join('products p', 'p.id = pav.product_id')
                    ->where('pav.attribute_id', $attribute_id)
                    ->where("FIND_IN_SET('" . $oid . "', pav.text_value)", null, false)
                    ->where('p.deleted_at', null)
                    ->where('p.status', 'Active');
                if ($category_id) {
                    $this->db->where('p.category_id', (int) $category_id);
                }
                $cnt = $this->db->count_all_results();
                if ($cnt > 0) {
                    $out[$oid] = $cnt;
                }
            }
            return $out;
        }

        // select (and any option-id-in-integer_value type)
        $this->db->select('pav.integer_value AS option_id, COUNT(DISTINCT pav.product_id) AS cnt')
            ->from('product_attribute_values pav')
            ->join('products p', 'p.id = pav.product_id')
            ->where('pav.attribute_id', $attribute_id)
            ->where('pav.integer_value IS NOT NULL', null, false)
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active');
        if ($category_id) {
            $this->db->where('p.category_id', (int) $category_id);
        }
        $this->db->group_by('pav.integer_value');
        foreach ($this->db->get()->result_array() as $r) {
            $out[(int) $r['option_id']] = (int) $r['cnt'];
        }
        return $out;
    }
}

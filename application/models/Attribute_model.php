<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV)
 * @author   : Mamun Mia Turan
 * @filename : Attribute_model.php
 *
 * Attribute definitions + their options (select/multiselect/checkbox). Ported
 * from Bagisto's Attribute module. The attribute `type` decides which column of
 * product_attribute_values a value lives in (see eav_helper::eav_value_column).
 */
class Attribute_model extends MY_Model
{
    protected $table = 'attributes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'code', 'admin_name', 'name', 'type', 'swatch_type', 'validation', 'regex',
        'position', 'is_required', 'is_unique', 'is_filterable', 'is_comparable',
        'is_configurable', 'is_visible_on_front', 'is_user_defined', 'default_value',
        'status', 'created_by', 'updated_by',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('eav');
    }

    // ---- reads ----

    public function get_active()
    {
        return $this->db->where('deleted_at', null)->where('status', 'Active')
            ->order_by('position', 'ASC')->order_by('id', 'ASC')->get('attributes')->result_array();
    }

    public function get_by_code($code)
    {
        return $this->db->where('code', $code)->where('deleted_at', null)->get('attributes')->row_array();
    }

    /** Filterable attributes for storefront layered navigation (excludes image swatches). */
    public function filterable()
    {
        return $this->db->where('deleted_at', null)->where('status', 'Active')->where('is_filterable', 1)
            ->where("(swatch_type IS NULL OR swatch_type <> 'image')", null, false)
            ->order_by('position', 'ASC')->get('attributes')->result_array();
    }

    public function get_dropdown()
    {
        $rows = $this->db->where('deleted_at', null)->order_by('admin_name', 'ASC')->get('attributes')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['admin_name'] . ' (' . $r['code'] . ')';
        }
        return $out;
    }

    public function unique_code($code, $ignore_id = null)
    {
        $this->db->where('code', trim((string) $code))->where('deleted_at', null);
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get('attributes')->num_rows() === 0;
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    // ---- options ----

    public function get_options($attribute_id)
    {
        return $this->db->where('attribute_id', (int) $attribute_id)
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('attribute_options')->result_array();
    }

    /** id => label map for one attribute (for rendering stored values). */
    public function options_map($attribute_id)
    {
        $out = [];
        foreach ($this->get_options($attribute_id) as $o) {
            $out[(int) $o['id']] = $o['label'];
        }
        return $out;
    }

    /**
     * Sync an attribute's options: upsert by id, delete rows no longer present.
     * $options rows: ['id'=>?, 'label'=>, 'admin_name'=>?, 'swatch_value'=>?, 'sort_order'=>?]
     * Raw query builder (not MY_Model) so it does not spam the activity log.
     */
    public function save_options($attribute_id, $options)
    {
        $attribute_id = (int) $attribute_id;
        $keep = [];
        $sort = 1;
        foreach ($options as $opt) {
            $label = trim((string) ($opt['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $row = [
                'attribute_id' => $attribute_id,
                'admin_name'   => trim((string) ($opt['admin_name'] ?? $label)),
                'label'        => $label,
                'swatch_value' => (isset($opt['swatch_value']) && $opt['swatch_value'] !== '') ? $opt['swatch_value'] : null,
                'sort_order'   => isset($opt['sort_order']) && $opt['sort_order'] !== '' ? (int) $opt['sort_order'] : $sort,
            ];
            $oid = (int) ($opt['id'] ?? 0);
            if ($oid > 0 && $this->db->where('id', $oid)->where('attribute_id', $attribute_id)->get('attribute_options')->row()) {
                $this->db->where('id', $oid)->update('attribute_options', $row);
                $keep[] = $oid;
            } else {
                $row['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('attribute_options', $row);
                $keep[] = (int) $this->db->insert_id();
            }
            $sort++;
        }
        // delete removed options
        $this->db->where('attribute_id', $attribute_id);
        if (!empty($keep)) {
            $this->db->where_not_in('id', $keep);
        }
        $this->db->delete('attribute_options');
    }

    // ---- datatable ----

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('attributes')->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()->like('admin_name', $search)->or_like('code', $search)->or_like('type', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }
}

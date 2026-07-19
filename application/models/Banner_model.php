<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Banners
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Banner_model.php
 */
class Banner_model extends MY_Model
{
    protected $table = 'banners';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'title', 'subtitle', 'image', 'link_url', 'button_text', 'type',
        'position', 'status', 'starts_at', 'ends_at', 'created_by', 'updated_by',
    ];

    /**
     * Total non-deleted rows (optionally scoped by type) — DataTables recordsTotal.
     */
    public function count_all($type = '')
    {
        $this->db->where('deleted_at', null);
        if ($type !== '' && $type !== null) {
            $this->db->where('type', $type);
        }
        return $this->db->count_all_results($this->table);
    }

    /**
     * Server-side DataTables payload. Returns ['filtered' => int, 'data' => object[]].
     */
    public function datatable($search, $start, $length, $order_col, $order_dir, $type = '')
    {
        $apply = function () use ($search, $type) {
            $this->db->from('banners')->where('deleted_at', null);
            if ($type !== '' && $type !== null) {
                $this->db->where('type', $type);
            }
            if ($search !== '') {
                $this->db->group_start()->like('title', $search)->or_like('subtitle', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    /**
     * Toggle Active/Inactive; returns the new status string or false.
     */
    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    // ---- Public storefront API ----

    /**
     * Active banners of a given type, respecting the optional schedule window,
     * ordered by position (then newest first). Returns an array of rows.
     */
    public function get_active_by_type($type)
    {
        $now = date('Y-m-d H:i:s');
        return $this->db
            ->where('type', $type)
            ->where('status', 'Active')
            ->where('deleted_at', null)
            ->group_start()
                ->where('starts_at', null)->or_where('starts_at <=', $now)
            ->group_end()
            ->group_start()
                ->where('ends_at', null)->or_where('ends_at >=', $now)
            ->group_end()
            ->order_by('position', 'ASC')
            ->order_by('id', 'DESC')
            ->get($this->table)
            ->result_array();
    }
}

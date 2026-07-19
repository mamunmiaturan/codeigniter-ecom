<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Brand_model.php
 */
class Brand_model extends MY_Model
{
    protected $table = 'brands';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'name', 'slug', 'logo', 'description', 'website',
        'is_featured', 'sort_order', 'status', 'meta_title', 'meta_description',
        'created_by', 'updated_by',
    ];

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    /**
     * Server-side DataTables payload. Returns ['filtered' => int, 'data' => object[]].
     */
    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start();
                $this->db->like('name', $search);
                $this->db->or_like('slug', $search);
                $this->db->group_end();
            }
        };

        // Filtered count
        $this->db->from($this->table);
        $apply();
        $filtered = $this->db->count_all_results();

        // Data
        $this->db->from($this->table);
        $apply();
        $this->db->order_by($order_col, $order_dir);
        $this->db->limit($length, $start);
        $data = $this->db->get()->result();

        return ['filtered' => $filtered, 'data' => $data];
    }

    /**
     * Active brands for the product form dropdown (id => name).
     */
    public function get_dropdown()
    {
        $this->db->select('id, name');
        $this->db->where('deleted_at', null);
        $this->db->where('status', 'Active');
        $this->db->order_by('name', 'ASC');
        $rows = $this->db->get($this->table)->result();

        $options = [];
        foreach ($rows as $row) {
            $options[$row->id] = $row->name;
        }
        return $options;
    }

    public function unique_slug($name, $ignore_id = null)
    {
        $base = url_title($name, 'dash', true);
        if ($base === '') {
            $base = 'brand';
        }
        $slug = $base;
        $i = 1;
        while (true) {
            $this->db->where('slug', $slug);
            if ($ignore_id) {
                $this->db->where('id !=', (int) $ignore_id);
            }
            $exists = $this->db->get($this->table)->num_rows() > 0;
            if (!$exists) {
                break;
            }
            $slug = $base . '-' . (++$i);
        }
        return $slug;
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

    // ---- Public storefront API ----

    public function api_list()
    {
        $this->db->select('id, name, slug, logo, description, website, is_featured, sort_order');
        $this->db->where('deleted_at', null);
        $this->db->where('status', 'Active');
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->table)->result_array();
    }

    public function get_active_by_slug($slug)
    {
        $this->db->where('slug', $slug);
        $this->db->where('deleted_at', null);
        $this->db->where('status', 'Active');
        return $this->db->get($this->table)->row_array();
    }
}

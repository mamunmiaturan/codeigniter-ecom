<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Category_model.php
 */
class Category_model extends MY_Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'name', 'slug', 'parent_id', 'description', 'image', 'icon',
        'is_featured', 'sort_order', 'status', 'meta_title', 'meta_description',
        'created_by', 'updated_by',
    ];

    /**
     * Total non-deleted rows (optionally scoped by status) — DataTables recordsTotal.
     */
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
        // Filtered count
        $this->db->from('categories c');
        $this->db->where('c.deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('c.status', $status);
        }
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('c.name', $search);
            $this->db->or_like('c.slug', $search);
            $this->db->group_end();
        }
        $filtered = $this->db->count_all_results();

        // Data
        $this->db->select('c.*, p.name AS parent_name');
        $this->db->from('categories c');
        $this->db->join('categories p', 'p.id = c.parent_id', 'left');
        $this->db->where('c.deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('c.status', $status);
        }
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('c.name', $search);
            $this->db->or_like('c.slug', $search);
            $this->db->group_end();
        }
        $this->db->order_by($order_col, $order_dir);
        $this->db->limit($length, $start);
        $data = $this->db->get()->result();

        return ['filtered' => $filtered, 'data' => $data];
    }

    /**
     * Parent-category options for the form dropdown (id => name).
     * Excludes the given id so a category cannot become its own parent.
     */
    public function get_dropdown($exclude_id = null)
    {
        $this->db->select('id, name');
        $this->db->where('deleted_at', null);
        $this->db->where('status', 'Active');
        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }
        $this->db->order_by('name', 'ASC');
        $rows = $this->db->get($this->table)->result();

        $options = [];
        foreach ($rows as $row) {
            $options[$row->id] = $row->name;
        }
        return $options;
    }

    /**
     * Generate a URL-safe, table-unique slug from a name.
     */
    public function unique_slug($name, $ignore_id = null)
    {
        $base = url_title($name, 'dash', true);
        if ($base === '') {
            $base = 'category';
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

    public function api_list()
    {
        $this->db->select('id, name, slug, parent_id, image, icon, is_featured, sort_order');
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

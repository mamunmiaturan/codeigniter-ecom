<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / CMS
 * @author   : Mamun Mia Turan
 * @filename : Cms_page_model.php
 */
class Cms_page_model extends MY_Model
{
    protected $table = 'cms_pages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'title', 'slug', 'content', 'meta_title', 'meta_description',
        'status', 'show_in_footer', 'sort_order', 'created_by', 'updated_by',
    ];

    public function unique_slug($title, $ignore_id = null)
    {
        $base = url_title($title, 'dash', true) ?: 'page';
        $slug = $base;
        $i = 1;
        while (true) {
            $this->db->where('slug', $slug);
            if ($ignore_id) {
                $this->db->where('id !=', (int) $ignore_id);
            }
            if ($this->db->get('cms_pages')->num_rows() === 0) {
                break;
            }
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function get_active_by_slug($slug)
    {
        return $this->db->where('slug', $slug)->where('status', 'Active')->where('deleted_at', null)->get('cms_pages')->row_array();
    }

    /** Pages flagged for the storefront footer. */
    public function footer_pages()
    {
        return $this->db->select('title, slug')
            ->where('status', 'Active')->where('show_in_footer', 1)->where('deleted_at', null)
            ->order_by('sort_order', 'ASC')->order_by('title', 'ASC')
            ->get('cms_pages')->result_array();
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

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('cms_pages')->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()->like('title', $search)->or_like('slug', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }
}

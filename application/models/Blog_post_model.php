<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Blog
 * @author   : Mamun Mia Turan
 * @filename : Blog_post_model.php
 */
class Blog_post_model extends MY_Model
{
    protected $table = 'blog_posts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'title', 'slug', 'excerpt', 'content', 'thumbnail', 'category', 'tags',
        'status', 'is_featured', 'published_at', 'meta_title', 'meta_description',
        'created_by', 'updated_by',
    ];

    /**
     * Generate a URL-safe, table-unique slug from a title.
     */
    public function unique_slug($title, $ignore_id = null)
    {
        $base = url_title($title, 'dash', true) ?: 'post';
        $slug = $base;
        $i = 1;
        while (true) {
            $this->db->where('slug', $slug);
            if ($ignore_id) {
                $this->db->where('id !=', (int) $ignore_id);
            }
            if ($this->db->get('blog_posts')->num_rows() === 0) {
                break;
            }
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    /**
     * Single published post by slug (Active, published_at reached, not deleted).
     */
    public function get_active_by_slug($slug)
    {
        return $this->db->where('slug', $slug)
            ->where('status', 'Active')
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->where('deleted_at', null)
            ->get('blog_posts')->row_array();
    }

    /**
     * Storefront list of published posts (newest first).
     */
    public function published_list($limit, $offset = 0, $category = null)
    {
        $this->db->from('blog_posts')
            ->where('status', 'Active')
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->where('deleted_at', null);
        if ($category !== null && $category !== '') {
            $this->db->where('category', $category);
        }
        return $this->db->order_by('published_at', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get()->result_array();
    }

    /**
     * Count of published posts (for storefront pagination).
     */
    public function count_published($category = null)
    {
        $this->db->where('status', 'Active')
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->where('deleted_at', null);
        if ($category !== null && $category !== '') {
            $this->db->where('category', $category);
        }
        return $this->db->count_all_results('blog_posts');
    }

    /**
     * Recent published posts for the storefront sidebar.
     */
    public function recent($limit = 5)
    {
        return $this->db->select('title, slug, thumbnail, published_at')
            ->where('status', 'Active')
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->where('deleted_at', null)
            ->order_by('published_at', 'DESC')
            ->limit((int) $limit)
            ->get('blog_posts')->result_array();
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

    /**
     * Server-side DataTables payload. Returns ['filtered' => int, 'data' => object[]].
     */
    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('blog_posts')->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()
                    ->like('title', $search)
                    ->or_like('slug', $search)
                    ->or_like('category', $search)
                    ->or_like('tags', $search)
                    ->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }
}

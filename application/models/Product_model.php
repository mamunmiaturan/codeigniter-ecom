<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Product_model.php
 */
class Product_model extends MY_Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'name', 'slug', 'sku', 'product_type', 'category_id', 'brand_id', 'short_description',
        'description', 'price', 'special_price', 'cost_price', 'currency',
        'stock_quantity', 'stock_status', 'unit', 'weight', 'has_variants', 'tax_category_id',
        'attribute_family_id',
        'thumbnail', 'is_featured', 'status', 'meta_title', 'meta_description',
        'tags', 'video_url', 'label',
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
    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '', $category_id = '')
    {
        // Filtered count
        $this->db->from('products p');
        $this->db->where('p.deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('p.status', $status);
        }
        if ($category_id !== '' && $category_id !== null) {
            $this->db->where('p.category_id', (int) $category_id);
        }
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('p.name', $search);
            $this->db->or_like('p.sku', $search);
            $this->db->or_like('p.slug', $search);
            $this->db->group_end();
        }
        $filtered = $this->db->count_all_results();

        // Data
        $this->db->select('p.*, c.name AS category_name, b.name AS brand_name');
        $this->db->from('products p');
        $this->db->join('categories c', 'c.id = p.category_id', 'left');
        $this->db->join('brands b', 'b.id = p.brand_id', 'left');
        $this->db->where('p.deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('p.status', $status);
        }
        if ($category_id !== '' && $category_id !== null) {
            $this->db->where('p.category_id', (int) $category_id);
        }
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('p.name', $search);
            $this->db->or_like('p.sku', $search);
            $this->db->or_like('p.slug', $search);
            $this->db->group_end();
        }
        $this->db->order_by($order_col, $order_dir);
        $this->db->limit($length, $start);
        $data = $this->db->get()->result();

        return ['filtered' => $filtered, 'data' => $data];
    }

    public function unique_slug($name, $ignore_id = null)
    {
        $base = url_title($name, 'dash', true);
        if ($base === '') {
            $base = 'product';
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
     * SKU uniqueness check (nullable field). Empty SKU is always considered unique.
     */
    public function unique_sku($sku, $ignore_id = null)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return true;
        }
        $this->db->where('sku', $sku);
        $this->db->where('deleted_at', null);
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get($this->table)->num_rows() === 0;
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        // Draft/Inactive -> Active, Active -> Inactive
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    // ---- Public storefront API ----

    public function api_list($filters, $page, $per_page)
    {
        $page     = max(1, (int) $page);
        $per_page = min(100, max(1, (int) $per_page));
        $offset   = ($page - 1) * $per_page;

        // Count
        $this->db->from('products p');
        $this->db->join('categories c', 'c.id = p.category_id', 'left');
        $this->db->join('brands b', 'b.id = p.brand_id', 'left');
        $this->_api_where($filters);
        $total = $this->db->count_all_results();

        // Data
        $this->db->select('p.id, p.name, p.slug, p.sku, p.product_type, p.price, p.special_price, p.currency, p.stock_quantity, p.stock_status, p.thumbnail, p.is_featured, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug');
        $this->db->from('products p');
        $this->db->join('categories c', 'c.id = p.category_id', 'left');
        $this->db->join('brands b', 'b.id = p.brand_id', 'left');
        $this->_api_where($filters);
        list($col, $dir) = $this->_api_sort($filters['sort'] ?? '');
        $this->db->order_by($col, $dir);
        $this->db->limit($per_page, $offset);
        $items = $this->db->get()->result_array();

        return ['total' => (int) $total, 'items' => $items];
    }

    public function get_active_by_slug($slug)
    {
        $this->db->select('p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug');
        $this->db->from('products p');
        $this->db->join('categories c', 'c.id = p.category_id', 'left');
        $this->db->join('brands b', 'b.id = p.brand_id', 'left');
        $this->db->where('p.slug', $slug);
        $this->db->where('p.deleted_at', null);
        $this->db->where('p.status', 'Active');
        return $this->db->get()->row_array();
    }

    public function get_images($product_id)
    {
        $this->db->where('product_id', (int) $product_id);
        $this->db->order_by('is_primary', 'DESC');
        $this->db->order_by('sort_order', 'ASC');
        return $this->db->get('product_images')->result_array();
    }

    public function get_active_variants($product_id)
    {
        $this->db->where('product_id', (int) $product_id);
        $this->db->where('status', 'Active');
        $this->db->order_by('id', 'ASC');
        return $this->db->get('product_variants')->result_array();
    }

    /**
     * Non-composite products (simple/virtual/downloadable) as an id => label map,
     * for authoring grouped items and bundle option products. Excludes $exclude_id
     * (a product cannot contain itself).
     */
    public function simple_dropdown($exclude_id = null)
    {
        $this->db->select('id, name, sku')->where('deleted_at', null)
            ->where_in('product_type', ['simple', 'virtual', 'downloadable'])
            ->order_by('name', 'ASC');
        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }
        $out = [];
        foreach ($this->db->get('products')->result_array() as $r) {
            $out[$r['id']] = $r['name'] . ($r['sku'] ? ' (' . $r['sku'] . ')' : '');
        }
        return $out;
    }

    public function get_variant($variant_id, $product_id)
    {
        return $this->db
            ->where('id', (int) $variant_id)
            ->where('product_id', (int) $product_id)
            ->where('status', 'Active')
            ->get('product_variants')
            ->row_array();
    }

    // ---- Admin authoring: variants ----

    /**
     * All variants for a product (any status) — used by the admin edit form.
     */
    public function get_variants($product_id)
    {
        $this->db->where('product_id', (int) $product_id);
        $this->db->order_by('id', 'ASC');
        return $this->db->get('product_variants')->result_array();
    }

    /**
     * Synchronise a product's variants against the submitted set (raw query
     * builder to avoid activity-log spam):
     *   - rows with a positive `id` matching this product are updated,
     *   - rows without an id are inserted,
     *   - existing variants absent from the submitted set are deleted.
     *
     * Each row: name, sku, price, special_price, stock_quantity, attributes
     * (already JSON-encoded string or null), image, status.
     *
     * @return int Number of variants persisted.
     */
    public function save_variants($product_id, $rows)
    {
        $product_id = (int) $product_id;
        $now  = date('Y-m-d H:i:s');
        $keep = [];

        foreach ($rows as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            if ($name === '') {
                continue; // skip blank template rows
            }
            $fields = [
                'name'           => $name,
                'sku'            => ($r['sku'] ?? '') !== '' ? $r['sku'] : null,
                'price'          => ($r['price'] ?? '') !== '' && is_numeric($r['price']) ? (float) $r['price'] : null,
                'special_price'  => ($r['special_price'] ?? '') !== '' && is_numeric($r['special_price']) ? (float) $r['special_price'] : null,
                'stock_quantity' => (int) ($r['stock_quantity'] ?? 0),
                'attributes'     => $r['attributes'] ?? null,
                'status'         => ($r['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active',
            ];

            $id = (int) ($r['id'] ?? 0);
            if ($id > 0) {
                $fields['updated_at'] = $now;
                $this->db->where('id', $id)->where('product_id', $product_id)->update('product_variants', $fields);
                $keep[] = $id;
            } else {
                $fields['product_id'] = $product_id;
                $fields['created_at'] = $now;
                $this->db->insert('product_variants', $fields);
                $keep[] = (int) $this->db->insert_id();
            }
        }

        // Delete variants that were removed in the form.
        $this->db->where('product_id', $product_id);
        if (!empty($keep)) {
            $this->db->where_not_in('id', $keep);
        }
        $this->db->delete('product_variants');

        return count($keep);
    }

    // ---- Admin authoring: gallery images ----

    public function add_image($product_id, $path, $is_primary = 0, $sort_order = 0)
    {
        $this->db->insert('product_images', [
            'product_id' => (int) $product_id,
            'image_path' => $path,
            'sort_order' => (int) $sort_order,
            'is_primary' => $is_primary ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    public function get_image($image_id, $product_id)
    {
        return $this->db
            ->where('id', (int) $image_id)
            ->where('product_id', (int) $product_id)
            ->get('product_images')
            ->row_array();
    }

    public function count_images($product_id)
    {
        return (int) $this->db->where('product_id', (int) $product_id)->count_all_results('product_images');
    }

    /**
     * Delete a gallery image (scoped to its product) and return its file path so
     * the controller can unlink the file. Returns null if not found.
     */
    public function delete_image($image_id, $product_id)
    {
        $img = $this->get_image($image_id, $product_id);
        if (!$img) {
            return null;
        }
        $this->db->where('id', (int) $image_id)->where('product_id', (int) $product_id)->delete('product_images');
        // If we removed the primary image, promote the next one.
        if (!empty($img['is_primary'])) {
            $next = $this->db->where('product_id', (int) $product_id)->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get('product_images')->row_array();
            if ($next) {
                $this->db->where('id', $next['id'])->update('product_images', ['is_primary' => 1]);
            }
        }
        return $img['image_path'];
    }

    public function set_primary_image($image_id, $product_id)
    {
        $img = $this->get_image($image_id, $product_id);
        if (!$img) {
            return false;
        }
        $this->db->where('product_id', (int) $product_id)->update('product_images', ['is_primary' => 0]);
        $this->db->where('id', (int) $image_id)->where('product_id', (int) $product_id)->update('product_images', ['is_primary' => 1]);
        return true;
    }

    private function _api_where($filters)
    {
        $this->db->where('p.deleted_at', null);
        $this->db->where('p.status', 'Active');
        if (!empty($filters['category_id'])) {
            $this->db->where('p.category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['category'])) {
            $this->db->where('c.slug', $filters['category']);
        }
        if (!empty($filters['brand_id'])) {
            $this->db->where('p.brand_id', (int) $filters['brand_id']);
        }
        if (!empty($filters['brand'])) {
            $this->db->where('b.slug', $filters['brand']);
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $this->db->where('p.price >=', (float) $filters['min_price']);
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $this->db->where('p.price <=', (float) $filters['max_price']);
        }
        if (!empty($filters['featured'])) {
            $this->db->where('p.is_featured', 1);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('p.name', $filters['search']);
            $this->db->or_like('p.short_description', $filters['search']);
            $this->db->or_like('p.sku', $filters['search']);
            $this->db->group_end();
        }
        // EAV attribute facets: ?attr[<code>]=<option_id> (AND across attributes,
        // OR within one). Matches select (integer_value) + multiselect (text_value CSV).
        if (!empty($filters['attr']) && is_array($filters['attr'])) {
            foreach ($filters['attr'] as $code => $vals) {
                $code = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $code));
                if ($code === '') {
                    continue;
                }
                $ids = array_values(array_filter(array_map('intval', (array) $vals)));
                if (empty($ids)) {
                    continue;
                }
                $fis = [];
                foreach ($ids as $id) {
                    $fis[] = "FIND_IN_SET('" . $id . "', pav.text_value)";
                }
                $sub = "EXISTS (SELECT 1 FROM product_attribute_values pav "
                    . "JOIN attributes a ON a.id = pav.attribute_id "
                    . "WHERE pav.product_id = p.id AND a.code = " . $this->db->escape($code) . " "
                    . "AND (pav.integer_value IN (" . implode(',', $ids) . ") OR " . implode(' OR ', $fis) . "))";
                $this->db->where($sub, null, false);
            }
        }
    }

    private function _api_sort($sort)
    {
        switch ($sort) {
            case 'price_asc':  return ['p.price', 'ASC'];
            case 'price_desc': return ['p.price', 'DESC'];
            case 'name':       return ['p.name', 'ASC'];
            case 'oldest':     return ['p.created_at', 'ASC'];
            case 'featured':   return ['p.is_featured', 'DESC'];
            case 'newest':
            default:           return ['p.created_at', 'DESC'];
        }
    }
}

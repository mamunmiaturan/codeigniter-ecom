<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'models/landing/Storefront_model.php';

/**
 * Storefront product listing/queries: featured, latest, best-selling and the
 * paginated + filtered shop grid (search, category, in-stock, EAV facets).
 */
class Listing_model extends Storefront_model
{
    public function featured($limit = 8)
    {
        return $this->_list_query(['featured' => 1], 1, $limit)['items'];
    }

    public function latest($limit = 8)
    {
        return $this->_list_query(['sort' => 'newest'], 1, $limit)['items'];
    }

    /**
     * Paginated + filtered product listing for the shop page.
     * @return array ['items','total','page','per_page','pages']
     */
    public function list_products($filters, $page, $per_page)
    {
        return $this->_list_query($filters, $page, $per_page);
    }

    /** Top-selling products by total ordered quantity (excl. cancelled/returned). */
    public function best_selling($limit = 8)
    {
        $rows = $this->db
            ->select('p.*, MAX(c.name) AS category_name, MAX(c.slug) AS category_slug, MAX(b.name) AS brand_name, SUM(oi.quantity) AS total_sold', false)
            ->from('order_items oi')
            ->join('orders o', 'o.id = oi.order_id', 'inner')
            ->join('products p', 'p.id = oi.product_id', 'inner')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->where_not_in('o.status', ['cancelled', 'returned'])
            ->group_by('p.id')
            ->order_by('total_sold', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();
        return array_map([$this, 'shape'], $rows);
    }

    // ---- internals ----

    private function _list_query($filters, $page, $per_page)
    {
        $page     = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        $offset   = ($page - 1) * $per_page;

        $apply = function () use ($filters) {
            $this->db->where('p.deleted_at', null)->where('p.status', 'Active');
            if (!empty($filters['featured'])) {
                $this->db->where('p.is_featured', 1);
            }
            if (!empty($filters['in_stock'])) {
                $this->db->where('p.stock_status', 'in_stock');
            }
            if (!empty($filters['category'])) {
                $slug = $this->db->escape($filters['category']);
                $this->db->group_start()
                    ->where('c.slug', $filters['category'])
                    ->or_where('c.parent_id IN (SELECT id FROM categories WHERE slug = ' . $slug . ')', null, false)
                    ->group_end();
            }
            if (!empty($filters['search'])) {
                $this->db->group_start()
                    ->like('p.name', $filters['search'])
                    ->or_like('p.short_description', $filters['search'])
                    ->or_like('p.sku', $filters['search'])
                    ->group_end();
            }
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
        };

        $this->db->from('products p')->join('categories c', 'c.id = p.category_id', 'left');
        $apply();
        $total = $this->db->count_all_results();

        $this->db->select('p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name')
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left');
        $apply();
        list($col, $dir) = $this->_sort($filters['sort'] ?? '');
        $this->db->order_by($col, $dir)->limit($per_page, $offset);
        $items = array_map([$this, 'shape'], $this->db->get()->result_array());

        return [
            'items'    => $items,
            'total'    => (int) $total,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => (int) ceil($total / $per_page),
        ];
    }

    private function _sort($s)
    {
        switch ($s) {
            case 'price_asc':  return ['p.price', 'ASC'];
            case 'price_desc': return ['p.price', 'DESC'];
            case 'name':       return ['p.name', 'ASC'];
            case 'newest':     return ['p.created_at', 'DESC'];
            default:           return ['p.is_featured', 'DESC'];
        }
    }
}

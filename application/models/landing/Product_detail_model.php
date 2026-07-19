<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'models/landing/Storefront_model.php';

/**
 * Single-product storefront reads: detail page, gallery images, related and
 * frequently-bought-together products, and shaped rows for a set of ids.
 */
class Product_detail_model extends Storefront_model
{
    public function product($slug)
    {
        $p = $this->db->select('p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name')
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->where('p.slug', $slug)
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->get()->row_array();
        return $p ? $this->shape($p) : null;
    }

    public function images($product_id)
    {
        return $this->db->where('product_id', (int) $product_id)
            ->order_by('is_primary', 'DESC')
            ->order_by('sort_order', 'ASC')
            ->get('product_images')->result_array();
    }

    public function related($category_id, $exclude_id, $limit = 4)
    {
        if (!$category_id) {
            return [];
        }
        $rows = $this->db->select('p.*, c.name AS category_name, c.slug AS category_slug')
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->where('p.category_id', (int) $category_id)
            ->where('p.id !=', (int) $exclude_id)
            ->order_by('p.id', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();
        return array_map([$this, 'shape'], $rows);
    }

    /** Products frequently bought together, falling back to same-category related. */
    public function frequently_bought_together($product_id, $limit = 4)
    {
        $product_id = (int) $product_id;
        $rows = $this->db
            ->select('p.*, MAX(c.name) AS category_name, MAX(c.slug) AS category_slug, MAX(b.name) AS brand_name, COUNT(DISTINCT oi1.order_id) AS bought_together', false)
            ->from('order_items oi1')
            ->join('order_items oi2', 'oi2.order_id = oi1.order_id', 'inner')
            ->join('orders o', 'o.id = oi1.order_id', 'inner')
            ->join('products p', 'p.id = oi2.product_id', 'inner')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->where('oi1.product_id', $product_id)
            ->where('oi2.product_id !=', $product_id)
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->where_not_in('o.status', ['cancelled', 'returned'])
            ->group_by('p.id')
            ->order_by('bought_together', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();
        $items = array_map([$this, 'shape'], $rows);

        if (count($items) < 2) {
            $prow   = $this->db->select('category_id')->where('id', $product_id)->get('products')->row_array();
            $cat_id = $prow ? (int) $prow['category_id'] : 0;
            return $this->related($cat_id, $product_id, $limit);
        }
        return $items;
    }

    /** Shaped product rows for a set of ids, preserving the given order. */
    public function by_ids(array $ids, $limit = 8)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }
        $ids = array_slice($ids, 0, (int) $limit);
        $rows = $this->db->select('p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name')
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->where_in('p.id', $ids)
            ->get()->result_array();
        $by_id = [];
        foreach ($rows as $r) {
            $by_id[(int) $r['id']] = $this->shape($r);
        }
        $out = [];
        foreach ($ids as $id) {
            if (isset($by_id[$id])) {
                $out[] = $by_id[$id];
            }
        }
        return $out;
    }
}

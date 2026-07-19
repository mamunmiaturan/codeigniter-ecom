<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'models/landing/Storefront_model.php';

/**
 * Storefront catalog taxonomy: categories, category tree, brands and the
 * layered-navigation (filterable attribute) facets.
 */
class Catalog_model extends Storefront_model
{
    /** Active categories for the storefront nav / tiles. */
    public function categories()
    {
        return $this->db->select('id, name, slug, image, icon, is_featured')
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->order_by('sort_order', 'ASC')
            ->order_by('name', 'ASC')
            ->get('categories')->result_array();
    }

    /** Category hierarchy: each top-level category with a nested `children` array. */
    public function categories_tree()
    {
        $rows = $this->db->select('id, name, slug, image, icon, is_featured, parent_id')
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->order_by('sort_order', 'ASC')
            ->order_by('name', 'ASC')
            ->get('categories')->result_array();

        $byParent = [];
        foreach ($rows as $r) {
            $byParent[(int) $r['parent_id']][] = $r;
        }
        $tree = [];
        foreach (($byParent[0] ?? []) as $top) {
            $top['children'] = $byParent[(int) $top['id']] ?? [];
            $tree[] = $top;
        }
        return $tree;
    }

    public function category_by_slug($slug)
    {
        return $this->db->where('slug', $slug)
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->get('categories')->row_array();
    }

    /** Featured brands (is_featured=1, with a logo) for the homepage. */
    public function featured_brands($limit = 12)
    {
        return $this->db->select('id, name, slug, logo')
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->where('is_featured', 1)
            ->order_by('name', 'ASC')
            ->limit((int) $limit)
            ->get('brands')->result_array();
    }

    /** Featured categories (is_featured=1) for the homepage. */
    public function featured_categories($limit = 8)
    {
        return $this->db->select('id, name, slug, image, icon, is_featured')
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->where('is_featured', 1)
            ->order_by('sort_order', 'ASC')
            ->order_by('name', 'ASC')
            ->limit((int) $limit)
            ->get('categories')->result_array();
    }

    /** Filterable attributes for the shop sidebar (layered navigation). */
    public function filterable_attributes($category_slug = null)
    {
        $this->load->model('attribute_model');
        $this->load->model('product_attribute_value_model');
        $cat_id = null;
        if ($category_slug) {
            $c = $this->category_by_slug($category_slug);
            $cat_id = $c ? (int) $c['id'] : null;
        }
        $out = [];
        foreach ($this->attribute_model->filterable() as $a) {
            $counts = $this->product_attribute_value_model->option_counts((int) $a['id'], $cat_id);
            $opts = [];
            foreach ($this->attribute_model->get_options((int) $a['id']) as $o) {
                $cnt = $counts[(int) $o['id']] ?? 0;
                if ($cnt <= 0) {
                    continue;
                }
                $opts[] = ['id' => (int) $o['id'], 'label' => $o['label'], 'swatch' => $o['swatch_value'], 'count' => $cnt];
            }
            if (!empty($opts)) {
                $out[] = ['code' => $a['code'], 'name' => $a['name'], 'swatch_type' => $a['swatch_type'], 'options' => $opts];
            }
        }
        return $out;
    }
}

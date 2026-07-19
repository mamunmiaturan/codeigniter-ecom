<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Public storefront catalog API (read-only, no auth).
 *
 *  GET /api/v1/categories            -> flat list + nested tree (Active only)
 *  GET /api/v1/categories/{slug}     -> single category
 *  GET /api/v1/brands                -> list (Active only)
 *  GET /api/v1/brands/{slug}         -> single brand
 *  GET /api/v1/products              -> paginated list; filters:
 *        category, brand, category_id, brand_id, search, min_price,
 *        max_price, featured, sort(price_asc|price_desc|name|newest|oldest|featured),
 *        page, per_page
 *  GET /api/v1/products/{slug}       -> product detail incl. images + variants
 */
class Catalog extends Api_Controller
{
    protected $require_auth = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['category_model', 'brand_model', 'product_model']);
    }

    public function categories()
    {
        $rows = array_map([$this, '_shape_category'], $this->category_model->api_list());
        $this->ok(['items' => $rows, 'tree' => $this->_build_tree($rows)]);
    }

    public function category($slug = '')
    {
        $row = $this->category_model->get_active_by_slug(rawurldecode($slug));
        if (!$row) {
            $this->fail('Category not found', 404);
            return;
        }
        $this->ok($this->_shape_category($row));
    }

    public function brands()
    {
        $rows = array_map([$this, '_shape_brand'], $this->brand_model->api_list());
        $this->ok(['items' => $rows]);
    }

    public function brand($slug = '')
    {
        $row = $this->brand_model->get_active_by_slug(rawurldecode($slug));
        if (!$row) {
            $this->fail('Brand not found', 404);
            return;
        }
        $this->ok($this->_shape_brand($row));
    }

    public function products()
    {
        $filters = [
            'category'    => $this->input->get('category', true),
            'brand'       => $this->input->get('brand', true),
            'category_id' => $this->input->get('category_id', true),
            'brand_id'    => $this->input->get('brand_id', true),
            'search'      => $this->input->get('search', true),
            'min_price'   => $this->input->get('min_price', true),
            'max_price'   => $this->input->get('max_price', true),
            'featured'    => $this->input->get('featured', true),
            'sort'        => $this->input->get('sort', true),
            'attr'        => (is_array($this->input->get('attr', true)) ? $this->input->get('attr', true) : []),
        ];
        $page     = max(1, (int) ($this->input->get('page') ?: 1));
        $per_page = min(100, max(1, (int) ($this->input->get('per_page') ?: 20)));

        $res   = $this->product_model->api_list($filters, $page, $per_page);
        $total = $res['total'];

        $this->ok([
            'items'      => array_map([$this, '_shape_product_list'], $res['items']),
            'pagination' => [
                'page'        => $page,
                'per_page'    => $per_page,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $per_page),
                'has_more'    => ($page * $per_page) < $total,
            ],
        ]);
    }

    public function product($slug = '')
    {
        $row = $this->product_model->get_active_by_slug(rawurldecode($slug));
        if (!$row) {
            $this->fail('Product not found', 404);
            return;
        }
        $images   = $this->product_model->get_images($row['id']);
        $variants = $this->product_model->get_active_variants($row['id']);
        $samples  = [];
        if (($row['product_type'] ?? 'simple') === 'downloadable') {
            $this->load->model('download_model');
            $samples = $this->download_model->samples($row['id']);
        }
        $this->load->model('product_attribute_value_model');
        $attributes = $this->product_attribute_value_model->get_display_values($row['id']);
        $this->ok($this->_shape_product_detail($row, $images, $variants, $samples, $attributes));
    }

    /**
     * GET /api/v1/attributes[?category=<slug>] -> filterable attributes + their
     * options with product counts (storefront layered navigation).
     */
    public function attributes()
    {
        $this->load->model(['attribute_model', 'product_attribute_value_model']);
        $cat_id = null;
        $slug = $this->input->get('category', true);
        if ($slug) {
            $c = $this->category_model->get_active_by_slug(rawurldecode($slug));
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
                $out[] = [
                    'code'        => $a['code'],
                    'name'        => $a['name'],
                    'type'        => $a['type'],
                    'swatch_type' => $a['swatch_type'],
                    'options'     => $opts,
                ];
            }
        }
        $this->ok(['items' => $out]);
    }

    // ------------------------------------------------------------------
    // Shaping helpers
    // ------------------------------------------------------------------

    private function _media_url($subdir, $file)
    {
        return $file ? base_url('uploads/catalog/' . $subdir . '/' . $file) : null;
    }

    private function _price_block($price, $special, $currency)
    {
        $price   = (float) $price;
        $special = ($special !== null && $special !== '' && (float) $special > 0) ? (float) $special : null;
        $effective = $special !== null ? $special : $price;
        return [
            'currency'        => $currency ?: 'BDT',
            'price'           => number_format($price, 2, '.', ''),
            'special_price'   => $special !== null ? number_format($special, 2, '.', '') : null,
            'effective_price' => number_format($effective, 2, '.', ''),
            'on_sale'         => $special !== null,
            'discount_pct'    => ($special !== null && $price > 0) ? (int) round((1 - $special / $price) * 100) : 0,
        ];
    }

    private function _shape_category($c)
    {
        return [
            'id'          => (int) $c['id'],
            'name'        => $c['name'],
            'slug'        => $c['slug'],
            'parent_id'   => isset($c['parent_id']) && $c['parent_id'] !== null ? (int) $c['parent_id'] : null,
            'icon'        => $c['icon'] ?? null,
            'image'       => $this->_media_url('category', $c['image'] ?? null),
            'is_featured' => (bool) ($c['is_featured'] ?? false),
        ];
    }

    private function _shape_brand($b)
    {
        return [
            'id'          => (int) $b['id'],
            'name'        => $b['name'],
            'slug'        => $b['slug'],
            'logo'        => $this->_media_url('brand', $b['logo'] ?? null),
            'website'     => $b['website'] ?? null,
            'is_featured' => (bool) ($b['is_featured'] ?? false),
        ];
    }

    private function _shape_product_list($p)
    {
        return [
            'id'          => (int) $p['id'],
            'name'        => $p['name'],
            'slug'        => $p['slug'],
            'sku'         => $p['sku'],
            'product_type' => $p['product_type'] ?? 'simple',
            'thumbnail'   => $this->_media_url('product', $p['thumbnail']),
            'pricing'     => $this->_price_block($p['price'], $p['special_price'], $p['currency']),
            'stock'       => ['quantity' => (int) $p['stock_quantity'], 'status' => $p['stock_status']],
            'category'    => $p['category_slug'] ? ['name' => $p['category_name'], 'slug' => $p['category_slug']] : null,
            'brand'       => $p['brand_slug'] ? ['name' => $p['brand_name'], 'slug' => $p['brand_slug']] : null,
            'is_featured' => (bool) $p['is_featured'],
        ];
    }

    private function _shape_product_detail($p, $images, $variants, $samples = [], $attributes = [])
    {
        $ptype = $p['product_type'] ?? 'simple';
        return [
            'id'                => (int) $p['id'],
            'name'              => $p['name'],
            'slug'              => $p['slug'],
            'sku'               => $p['sku'],
            'product_type'      => $ptype,
            'is_shippable'      => !in_array($ptype, ['virtual', 'downloadable'], true),
            'short_description' => $p['short_description'],
            'description'       => $p['description'],
            'thumbnail'         => $this->_media_url('product', $p['thumbnail']),
            'pricing'           => $this->_price_block($p['price'], $p['special_price'], $p['currency']),
            'stock'             => ['quantity' => (int) $p['stock_quantity'], 'status' => $p['stock_status']],
            'unit'              => $p['unit'],
            'weight'            => $p['weight'] !== null ? (float) $p['weight'] : null,
            'category'          => $p['category_slug'] ? ['name' => $p['category_name'], 'slug' => $p['category_slug']] : null,
            'brand'             => $p['brand_slug'] ? ['name' => $p['brand_name'], 'slug' => $p['brand_slug']] : null,
            'images'            => array_map(function ($img) {
                return [
                    'url'        => $this->_media_url('product', $img['image_path']),
                    'alt'        => $img['alt_text'],
                    'is_primary' => (bool) $img['is_primary'],
                ];
            }, $images),
            'variants' => array_map(function ($v) {
                return [
                    'id'         => (int) $v['id'],
                    'name'       => $v['name'],
                    'sku'        => $v['sku'],
                    'price'      => $v['price'] !== null ? number_format((float) $v['price'], 2, '.', '') : null,
                    'stock'      => (int) $v['stock_quantity'],
                    'attributes' => !empty($v['attributes']) ? json_decode($v['attributes'], true) : null,
                    'image'      => $this->_media_url('product', $v['image']),
                ];
            }, $variants),
            'samples' => array_map(function ($s) {
                return [
                    'id'   => (int) $s['id'],
                    'name' => $s['name'],
                    'url'  => base_url('download/sample/' . (int) $s['id']),
                ];
            }, $samples),
            'attributes' => array_map(function ($a) {
                return ['code' => $a['code'], 'name' => $a['name'], 'value' => $a['value']];
            }, $attributes),
            'seo' => [
                'meta_title'       => $p['meta_title'],
                'meta_description' => $p['meta_description'],
            ],
        ];
    }

    private function _build_tree($flat)
    {
        $by_parent = [];
        foreach ($flat as $node) {
            $key = $node['parent_id'] ?? 0;
            $by_parent[$key][] = $node;
        }
        $build = function ($parent_id) use (&$build, $by_parent) {
            $out = [];
            foreach ($by_parent[$parent_id] ?? [] as $node) {
                $node['children'] = $build($node['id']);
                $out[] = $node;
            }
            return $out;
        };
        return $build(0);
    }
}

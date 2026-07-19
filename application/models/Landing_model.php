<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Storefront
 * @author   : Mamun Mia Turan
 * @filename : Landing_model.php
 *
 * Backward-compatible FACADE over the split storefront read-models. The actual
 * query logic now lives in feature models under application/models/landing/:
 *   - Catalog_model        (categories, tree, brands, filterable attributes)
 *   - Listing_model        (featured / latest / best-selling / shop grid)
 *   - Product_detail_model (product, images, related, FBT, by-ids)
 * shape() is shared via their Storefront_model base.
 *
 * Existing callers keep using $this->landing_model->x(); each call delegates to
 * the relevant feature model, so controllers can migrate to the feature models
 * directly and incrementally without a breaking change.
 */
class Landing_model extends MY_Model
{
    protected $table = 'products';

    private function catalog()
    {
        $this->load->model('landing/catalog_model');
        return $this->catalog_model;
    }

    private function listing()
    {
        $this->load->model('landing/listing_model');
        return $this->listing_model;
    }

    private function detail()
    {
        $this->load->model('landing/product_detail_model');
        return $this->product_detail_model;
    }

    // ---- Catalog ----
    public function categories() { return $this->catalog()->categories(); }
    public function categories_tree() { return $this->catalog()->categories_tree(); }
    public function category_by_slug($slug) { return $this->catalog()->category_by_slug($slug); }
    public function featured_brands($limit = 12) { return $this->catalog()->featured_brands($limit); }
    public function featured_categories($limit = 8) { return $this->catalog()->featured_categories($limit); }
    public function filterable_attributes($category_slug = null) { return $this->catalog()->filterable_attributes($category_slug); }

    // ---- Listing ----
    public function featured($limit = 8) { return $this->listing()->featured($limit); }
    public function latest($limit = 8) { return $this->listing()->latest($limit); }
    public function list_products($filters, $page, $per_page) { return $this->listing()->list_products($filters, $page, $per_page); }
    public function best_selling($limit = 8) { return $this->listing()->best_selling($limit); }

    // ---- Product detail ----
    public function product($slug) { return $this->detail()->product($slug); }
    public function images($product_id) { return $this->detail()->images($product_id); }
    public function related($category_id, $exclude_id, $limit = 4) { return $this->detail()->related($category_id, $exclude_id, $limit); }
    public function frequently_bought_together($product_id, $limit = 4) { return $this->detail()->frequently_bought_together($product_id, $limit); }
    public function by_ids(array $ids, $limit = 8) { return $this->detail()->by_ids($ids, $limit); }

    // ---- Shared ----
    public function shape($p) { return $this->catalog()->shape($p); }
}

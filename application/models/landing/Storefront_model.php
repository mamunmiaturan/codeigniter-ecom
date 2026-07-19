<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Shared base for the split storefront read-models (Catalog / Listing /
 * Product_detail). Holds shape(): adds effective price, sale flags and a
 * ready-to-use image URL to a raw product row so the views stay logic-free.
 */
class Storefront_model extends MY_Model
{
    protected $table = 'products';

    /** Add effective price, sale flags and image URL to a raw product row. */
    public function shape($p)
    {
        $price = (float) $p['price'];
        $sp = ($p['special_price'] !== null && $p['special_price'] !== '' && (float) $p['special_price'] > 0)
            ? (float) $p['special_price'] : null;
        $eff = $sp !== null ? $sp : $price;

        // Composite parents have no own price — advertise the cheapest child.
        $p['price_from'] = false;
        $ptype = $p['product_type'] ?? 'simple';
        if ($ptype === 'configurable') {
            $row = $this->db->select_min('price', 'min_price')
                ->where('product_id', (int) $p['id'])->where('status', 'Active')->where('price >', 0)
                ->get('product_variants')->row_array();
            if ($row && $row['min_price'] !== null) {
                $eff = (float) $row['min_price'];
                $sp  = null;
            }
            $p['price_from'] = true;
        } elseif ($ptype === 'grouped') {
            $this->load->model('composite_model');
            $min = $this->composite_model->grouped_min_price((int) $p['id']);
            if ($min !== null) {
                $eff = (float) $min;
                $sp  = null;
            }
            $p['price_from'] = true;
        } elseif ($ptype === 'bundle') {
            $this->load->model('composite_model');
            $eff = (float) $this->composite_model->bundle_min_price((int) $p['id']);
            $sp  = null;
            $p['price_from'] = true;
        }

        $p['effective_price'] = $eff;
        $p['on_sale']         = $sp !== null;
        $p['discount_pct']    = ($sp !== null && $price > 0) ? (int) round((1 - $sp / $price) * 100) : 0;
        $p['image_url']       = !empty($p['thumbnail'])
            ? base_url('uploads/catalog/product/' . $p['thumbnail'])
            : base_url('assets/frontend/assets/img/product/product-1.webp');
        return $p;
    }
}

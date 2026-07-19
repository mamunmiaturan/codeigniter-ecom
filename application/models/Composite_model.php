<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Composite Product Types)
 * @author   : Mamun Mia Turan
 * @filename : Composite_model.php
 *
 * Relations + read/write for grouped and bundle products (ported from Bagisto).
 * Grouped = a curated list of simple products (each added to cart as its own
 * line). Bundle = option groups of components, priced dynamically as the sum of
 * the selected components' effective prices. Pricing always defers to
 * Cart_model::effective_price so the single-source-of-truth invariant holds.
 */
class Composite_model extends MY_Model
{
    protected $table = 'product_grouped_items';

    // ================= Grouped =================

    /** Raw grouped rows (admin authoring): associated product + qty + sort. */
    public function grouped_items($product_id)
    {
        return $this->db->select('gi.id, gi.associated_product_id, gi.qty, gi.sort_order, p.name, p.sku, p.product_type, p.status')
            ->from('product_grouped_items gi')
            ->join('products p', 'p.id = gi.associated_product_id', 'left')
            ->where('gi.product_id', (int) $product_id)
            ->order_by('gi.sort_order', 'ASC')->order_by('gi.id', 'ASC')
            ->get()->result_array();
    }

    /**
     * Saleable associated products for the storefront (Active, not deleted,
     * simple/virtual/downloadable), each with a display price + stock + default qty.
     */
    public function grouped_saleable($product_id)
    {
        $rows = $this->db->select('gi.qty AS default_qty, gi.sort_order,
                p.id, p.name, p.slug, p.thumbnail, p.price, p.special_price, p.stock_status, p.stock_quantity, p.product_type')
            ->from('product_grouped_items gi')
            ->join('products p', 'p.id = gi.associated_product_id')
            ->where('gi.product_id', (int) $product_id)
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->where_in('p.product_type', ['simple', 'virtual', 'downloadable'])
            ->order_by('gi.sort_order', 'ASC')->order_by('gi.id', 'ASC')
            ->get()->result_array();
        foreach ($rows as &$r) {
            $r['display_price'] = ($r['special_price'] !== null && (float) $r['special_price'] > 0)
                ? (float) $r['special_price'] : (float) $r['price'];
            $r['in_stock'] = ($r['stock_status'] !== 'out_of_stock');
        }
        unset($r);
        return $rows;
    }

    /** Min display price across a grouped product's saleable constituents. */
    public function grouped_min_price($product_id)
    {
        $min = null;
        foreach ($this->grouped_saleable($product_id) as $r) {
            if ($r['display_price'] > 0 && ($min === null || $r['display_price'] < $min)) {
                $min = $r['display_price'];
            }
        }
        return $min;
    }

    /**
     * Sync a grouped product's associated items: upsert by associated_product_id,
     * delete removed. $rows: [['associated_product_id'=>, 'qty'=>, 'sort_order'=>], ...]
     */
    public function save_grouped($product_id, $rows)
    {
        $product_id = (int) $product_id;
        $keep = [];
        $sort = 1;
        foreach ($rows as $r) {
            $aid = (int) ($r['associated_product_id'] ?? 0);
            if ($aid <= 0 || $aid === $product_id) {
                continue; // ignore blanks + self-reference
            }
            $qty = max(1, (int) ($r['qty'] ?? 1));
            if ($this->db->where('product_id', $product_id)->where('associated_product_id', $aid)->get('product_grouped_items')->row()) {
                $this->db->where('product_id', $product_id)->where('associated_product_id', $aid)
                    ->update('product_grouped_items', ['qty' => $qty, 'sort_order' => $sort]);
            } else {
                $this->db->insert('product_grouped_items', ['product_id' => $product_id, 'associated_product_id' => $aid, 'qty' => $qty, 'sort_order' => $sort]);
            }
            $keep[] = $aid;
            $sort++;
        }
        $this->db->where('product_id', $product_id);
        if (!empty($keep)) {
            $this->db->where_not_in('associated_product_id', $keep);
        }
        $this->db->delete('product_grouped_items');
    }

    // ================= Bundle =================

    /**
     * A bundle's option groups, each with its selectable (Active, simple)
     * component products + a display price + stock.
     */
    public function bundle_options($product_id)
    {
        $opts = $this->db->where('product_id', (int) $product_id)
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('product_bundle_options')->result_array();
        foreach ($opts as &$o) {
            $o['products'] = $this->db->select('bop.id, bop.product_id, bop.qty, bop.is_default, bop.sort_order,
                    p.name, p.sku, p.price, p.special_price, p.stock_status, p.product_type')
                ->from('product_bundle_option_products bop')
                ->join('products p', 'p.id = bop.product_id')
                ->where('bop.bundle_option_id', (int) $o['id'])
                ->where('p.deleted_at', null)->where('p.status', 'Active')
                ->order_by('bop.sort_order', 'ASC')->order_by('bop.id', 'ASC')
                ->get()->result_array();
            foreach ($o['products'] as &$bp) {
                $bp['display_price'] = ($bp['special_price'] !== null && (float) $bp['special_price'] > 0)
                    ? (float) $bp['special_price'] : (float) $bp['price'];
                $bp['in_stock'] = ($bp['stock_status'] !== 'out_of_stock');
            }
            unset($bp);
        }
        unset($o);
        return $opts;
    }

    /** Map bundle_option_product id => {product_id, qty, option_id, is_required}. */
    public function bundle_option_product_index($product_id)
    {
        $index = [];
        foreach ($this->bundle_options($product_id) as $o) {
            foreach ($o['products'] as $bp) {
                $index[(int) $bp['id']] = [
                    'product_id'  => (int) $bp['product_id'],
                    'qty'         => max(1, (int) $bp['qty']),
                    'option_id'   => (int) $o['id'],
                    'is_required' => (int) $o['is_required'],
                    'in_stock'    => (bool) $bp['in_stock'],
                    'name'        => $bp['name'],
                ];
            }
        }
        return $index;
    }

    /**
     * Display "From" price: sum of the cheapest in-stock component of each
     * REQUIRED option (optional options contribute 0 to the minimum).
     */
    public function bundle_min_price($product_id)
    {
        $this->load->model('cart_model');
        $min = 0.0;
        foreach ($this->bundle_options($product_id) as $o) {
            $prices = [];
            foreach ($o['products'] as $bp) {
                if (!$bp['in_stock']) {
                    continue;
                }
                $prices[] = $this->cart_model->component_effective_price((int) $bp['product_id']) * max(1, (int) $bp['qty']);
            }
            if (!empty($prices) && (int) $o['is_required'] === 1) {
                $min += min($prices);
            }
        }
        return round($min, 2);
    }

    /**
     * Sync a bundle's option groups + their products.
     * $options: [ ['id'=>?, 'label'=>, 'type'=>, 'is_required'=>, 'products'=>[
     *   ['product_id'=>, 'qty'=>, 'is_default'=>], ... ] ], ... ]
     */
    public function save_bundle($product_id, $options)
    {
        $product_id = (int) $product_id;
        $keep_opts = [];
        $osort = 1;
        foreach ($options as $o) {
            $label = trim((string) ($o['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $type = in_array($o['type'] ?? 'select', ['select', 'radio', 'checkbox', 'multiselect'], true) ? $o['type'] : 'select';
            $orow = [
                'product_id'  => $product_id,
                'label'       => $label,
                'type'        => $type,
                'is_required' => !empty($o['is_required']) ? 1 : 0,
                'sort_order'  => $osort,
            ];
            $oid = (int) ($o['id'] ?? 0);
            if ($oid > 0 && $this->db->where('id', $oid)->where('product_id', $product_id)->get('product_bundle_options')->row()) {
                $this->db->where('id', $oid)->update('product_bundle_options', $orow);
            } else {
                $this->db->insert('product_bundle_options', $orow);
                $oid = (int) $this->db->insert_id();
            }
            $keep_opts[] = $oid;

            // sync this option's products
            $keep_bp = [];
            $psort = 1;
            foreach ((array) ($o['products'] ?? []) as $bp) {
                $bpid = (int) ($bp['product_id'] ?? 0);
                if ($bpid <= 0 || $bpid === $product_id) {
                    continue;
                }
                $brow = [
                    'bundle_option_id' => $oid,
                    'product_id'       => $bpid,
                    'qty'              => max(1, (int) ($bp['qty'] ?? 1)),
                    'is_default'       => !empty($bp['is_default']) ? 1 : 0,
                    'sort_order'       => $psort,
                ];
                $existing = $this->db->where('bundle_option_id', $oid)->where('product_id', $bpid)->get('product_bundle_option_products')->row();
                if ($existing) {
                    $this->db->where('id', $existing->id)->update('product_bundle_option_products', $brow);
                    $keep_bp[] = (int) $existing->id;
                } else {
                    $this->db->insert('product_bundle_option_products', $brow);
                    $keep_bp[] = (int) $this->db->insert_id();
                }
                $psort++;
            }
            $this->db->where('bundle_option_id', $oid);
            if (!empty($keep_bp)) {
                $this->db->where_not_in('id', $keep_bp);
            }
            $this->db->delete('product_bundle_option_products');
            $osort++;
        }
        $this->db->where('product_id', $product_id);
        if (!empty($keep_opts)) {
            $this->db->where_not_in('id', $keep_opts);
        }
        $this->db->delete('product_bundle_options');
    }
}

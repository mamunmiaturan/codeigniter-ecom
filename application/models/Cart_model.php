<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Cart
 * @author   : Mamun Mia Turan
 * @filename : Cart_model.php
 *
 * Shopping cart persistence. A cart is keyed by user_id (logged-in) OR
 * guest_token (anonymous). Uses raw query builder (not MY_Model insert/update)
 * so high-frequency cart mutations don't spam the activity log.
 */
class Cart_model extends MY_Model
{
    protected $table = 'carts';

    public function get_active_cart_id($user_id, $guest_token, $create = true)
    {
        $this->db->where('status', 'active');
        if ($user_id) {
            $this->db->where('user_id', (int) $user_id);
        } else {
            $this->db->where('guest_token', $guest_token);
        }
        $row = $this->db->get('carts')->row();
        if ($row) {
            return (int) $row->id;
        }
        if (!$create) {
            return null;
        }
        $this->db->insert('carts', [
            'user_id'     => $user_id ? (int) $user_id : null,
            'guest_token' => $user_id ? null : $guest_token,
            'status'      => 'active',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    public function find_item($cart_id, $product_id, $variant_id)
    {
        $this->db->where('cart_id', (int) $cart_id)->where('product_id', (int) $product_id);
        if ($variant_id) {
            $this->db->where('variant_id', (int) $variant_id);
        } else {
            $this->db->where('variant_id', null);
        }
        return $this->db->get('cart_items')->row();
    }

    public function add_or_increment($cart_id, $product_id, $variant_id, $qty, $unit_price)
    {
        $existing = $this->find_item($cart_id, $product_id, $variant_id);
        if ($existing) {
            $this->db->where('id', $existing->id)->update('cart_items', [
                'quantity'   => (int) $existing->quantity + (int) $qty,
                'unit_price' => $unit_price,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return (int) $existing->id;
        }
        $this->db->insert('cart_items', [
            'cart_id'    => (int) $cart_id,
            'product_id' => (int) $product_id,
            'variant_id' => $variant_id ? (int) $variant_id : null,
            'quantity'   => (int) $qty,
            'unit_price' => $unit_price,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    public function get_item($cart_id, $item_id)
    {
        return $this->db->where('id', (int) $item_id)->where('cart_id', (int) $cart_id)->get('cart_items')->row();
    }

    public function update_qty($cart_id, $item_id, $qty)
    {
        $this->db->where('id', (int) $item_id)->where('cart_id', (int) $cart_id)->update('cart_items', [
            'quantity'   => (int) $qty,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function remove_item($cart_id, $item_id)
    {
        $this->db->where('id', (int) $item_id)->where('cart_id', (int) $cart_id)->delete('cart_items');
        return $this->db->affected_rows() > 0;
    }

    public function clear($cart_id)
    {
        $this->db->where('cart_id', (int) $cart_id)->delete('cart_items');
    }

    public function get_items_detailed($cart_id)
    {
        $this->db->select('ci.id, ci.product_id, ci.variant_id, ci.quantity, ci.unit_price, ci.meta,
            p.name, p.slug, p.sku, p.thumbnail, p.price, p.special_price, p.currency, p.stock_quantity, p.stock_status, p.status AS product_status,
            p.category_id, p.tax_category_id, p.product_type,
            pv.name AS variant_name, pv.price AS variant_price, pv.stock_quantity AS variant_stock,
            crpp.price AS catalog_rule_price');
        $this->db->from('cart_items ci');
        $this->db->join('products p', 'p.id = ci.product_id', 'left');
        $this->db->join('product_variants pv', 'pv.id = ci.variant_id', 'left');
        $this->db->join('catalog_rule_product_prices crpp', 'crpp.product_id = ci.product_id', 'left');
        $this->db->where('ci.cart_id', (int) $cart_id);
        $this->db->order_by('ci.id', 'ASC');
        $rows = $this->db->get()->result_array();
        foreach ($rows as &$r) {
            // A bundle line is priced LIVE as the sum of its selected components'
            // effective prices; every other line uses the standard resolver.
            if (($r['product_type'] ?? '') === 'bundle') {
                $r['bundle_components']    = $this->_bundle_components($r['meta']);
                $r['effective_price']      = $this->_bundle_line_price($r['bundle_components']);
                // Availability comes from the components, not the virtual parent.
                $r['bundle_available_qty'] = $this->bundle_available_qty($r['bundle_components']);
            } else {
                $r['effective_price'] = $this->effective_price($r);
            }
        }
        unset($r);
        return $rows;
    }

    /** Decode a bundle cart line's stored component list. */
    private function _bundle_components($meta)
    {
        if (empty($meta)) {
            return [];
        }
        $d = json_decode($meta, true);
        if (!is_array($d)) {
            return [];
        }
        $comps = isset($d['components']) && is_array($d['components']) ? $d['components'] : $d;
        return is_array($comps) ? $comps : [];
    }

    /** Bundle unit price = sum of each selected component's effective price x qty. */
    private function _bundle_line_price($components)
    {
        $sum = 0.0;
        foreach ((array) $components as $c) {
            $pid = (int) ($c['product_id'] ?? 0);
            $q   = max(1, (int) ($c['qty'] ?? 1));
            if ($pid > 0) {
                $sum += $this->component_effective_price($pid) * $q;
            }
        }
        return round($sum, 2);
    }

    /**
     * Effective unit price of a single (simple) product id, reusing the standard
     * resolver. Used for grouped/bundle component pricing so composites compose on
     * top of the same special/catalog-rule pipeline.
     */
    public function component_effective_price($product_id)
    {
        $row = $this->db->select('p.price, p.special_price, crpp.price AS catalog_rule_price')
            ->from('products p')
            ->join('catalog_rule_product_prices crpp', 'crpp.product_id = p.id', 'left')
            ->where('p.id', (int) $product_id)
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->get()->row_array();
        if (!$row) {
            return 0.0; // gone/disabled component contributes nothing; blocked at checkout
        }
        return $this->effective_price([
            'special_price'      => $row['special_price'],
            'price'              => $row['price'],
            'catalog_rule_price' => $row['catalog_rule_price'],
            'variant_id'         => null,
            'variant_price'      => null,
        ]);
    }

    /**
     * Component-based availability for a bundle line: the max number of whole
     * bundles that can be fulfilled from current component stock (pre_order
     * components are treated as unlimited). Returns 0 if any component is
     * missing/disabled/out of stock. Used so the cart agrees with the order engine.
     */
    public function bundle_available_qty($components)
    {
        $comps = (array) $components;
        if (empty($comps)) {
            return 0;
        }
        $max = null;
        foreach ($comps as $c) {
            $pid = (int) ($c['product_id'] ?? 0);
            $need = max(1, (int) ($c['qty'] ?? 1));
            if ($pid <= 0) {
                return 0;
            }
            $p = $this->db->select('stock_quantity, stock_status, status, deleted_at')->where('id', $pid)->get('products')->row_array();
            if (!$p || $p['deleted_at'] !== null || $p['status'] !== 'Active') {
                return 0;
            }
            if ($p['stock_status'] === 'pre_order') {
                continue; // effectively unlimited
            }
            if ($p['stock_status'] === 'out_of_stock') {
                return 0;
            }
            $possible = (int) floor((int) $p['stock_quantity'] / $need);
            $max = ($max === null) ? $possible : min($max, $possible);
        }
        return $max === null ? 999999 : (int) $max;
    }

    /** Insert a bundle cart line (never merged — each selection is distinct). */
    public function add_bundle($cart_id, $product_id, $qty, $unit_price, $meta)
    {
        $this->db->insert('cart_items', [
            'cart_id'    => (int) $cart_id,
            'product_id' => (int) $product_id,
            'variant_id' => null,
            'quantity'   => (int) $qty,
            'unit_price' => $unit_price,
            'meta'       => json_encode($meta),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    /**
     * Single source of truth for a cart line's effective unit price:
     * variant price wins; otherwise the lowest of (catalog-rule price, special
     * price, base price). Used by the cart display, storefront summary and the
     * order engine so all three always agree.
     */
    public function effective_price($r)
    {
        $base = ($r['special_price'] !== null && (float) $r['special_price'] > 0) ? (float) $r['special_price'] : (float) $r['price'];
        if (isset($r['catalog_rule_price']) && $r['catalog_rule_price'] !== null
            && (float) $r['catalog_rule_price'] > 0 && (float) $r['catalog_rule_price'] < $base) {
            $base = (float) $r['catalog_rule_price'];
        }
        if (!empty($r['variant_id']) && $r['variant_price'] !== null && $r['variant_price'] !== '') {
            $base = (float) $r['variant_price'];
        }
        return round($base, 2);
    }

    /**
     * Merge an anonymous guest cart into the user's active cart, then delete
     * the guest cart. Quantities of the same product+variant are summed.
     */
    public function merge_guest_into_user($guest_token, $user_id)
    {
        $guest_cart_id = $this->get_active_cart_id(null, $guest_token, false);
        if (!$guest_cart_id) {
            return false;
        }
        $user_cart_id = $this->get_active_cart_id($user_id, null, true);
        if ($guest_cart_id === $user_cart_id) {
            return false;
        }
        $items = $this->db->where('cart_id', $guest_cart_id)->get('cart_items')->result();
        foreach ($items as $it) {
            if (!empty($it->meta)) {
                // Bundle line: preserve its component selection as a distinct line.
                $this->add_bundle($user_cart_id, $it->product_id, $it->quantity, $it->unit_price, json_decode($it->meta, true));
            } else {
                $this->add_or_increment($user_cart_id, $it->product_id, $it->variant_id, $it->quantity, $it->unit_price);
            }
        }
        $this->db->where('id', $guest_cart_id)->delete('carts'); // cascade removes its items
        return true;
    }

    // ---- coupon on cart ----

    public function set_coupon($cart_id, $code)
    {
        $this->db->where('id', (int) $cart_id)->update('carts', [
            'coupon_code' => $code,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function clear_coupon($cart_id)
    {
        $this->db->where('id', (int) $cart_id)->update('carts', [
            'coupon_code' => null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_coupon_code($cart_id)
    {
        $row = $this->db->select('coupon_code')->where('id', (int) $cart_id)->get('carts')->row();
        return $row ? $row->coupon_code : null;
    }
}

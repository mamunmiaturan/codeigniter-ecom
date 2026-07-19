<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Orders (OMS)
 * @author   : Mamun Mia Turan
 * @filename : Order_model.php
 *
 * Order persistence + the transactional checkout that converts a cart into an
 * order (server-side price/stock re-validation, stock decrement, cart close-out,
 * status history) — all in one DB transaction.
 */
class Order_model extends MY_Model
{
    protected $table = 'orders';

    /** Flat shipping charge (BDT). Placeholder for the shipping-zones engine (§14.3). */
    const FLAT_SHIPPING = 60.00;

    const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'returned'];

    public function generate_order_number()
    {
        do {
            $num = 'ORD' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->db->where('order_number', $num)->get('orders')->num_rows() > 0);
        return $num;
    }

    /**
     * Convert a cart into an order atomically.
     * @return array ['ok'=>bool, 'code'=>string, 'order_id'=>?int, 'order_number'=>?string, 'message'=>?string]
     */
    public function create_from_cart($cart_id, $identity, $customer, $shipping, $payment_method, $note, $shipping_method_id = null)
    {
        $this->load->model(['cart_model', 'shipping_model', 'tax_model', 'cart_rule_model']);
        $rows = $this->cart_model->get_items_detailed($cart_id);
        if (empty($rows)) {
            return ['ok' => false, 'code' => 'empty_cart', 'message' => 'Your cart is empty'];
        }

        $subtotal = 0.0;
        $count = 0;
        $physical_count = 0;
        $order_items = [];
        $stock_updates = [];
        $tax_lines = [];
        $rule_lines = [];
        $has_physical = false;

        foreach ($rows as $r) {
            if ($r['product_status'] !== 'Active') {
                return ['ok' => false, 'code' => 'unavailable', 'message' => '"' . $r['name'] . '" is no longer available'];
            }
            // effective_price is catalog-rule + variant aware (Cart_model single source of truth).
            $price = (float) $r['effective_price'];
            $qty = (int) $r['quantity'];
            $is_pre_order = ($r['stock_status'] === 'pre_order');
            $ptype = $r['product_type'] ?? 'simple';
            // Virtual + downloadable products have no physical stock and no shipping.
            $is_digital = in_array($ptype, ['virtual', 'downloadable'], true);
            $is_bundle  = ($ptype === 'bundle');

            $bundle_components = [];
            if ($is_bundle) {
                // A bundle is physical + shippable; its stock lives in its
                // components, which are stock-checked (component qty x bundle qty).
                $has_physical = true;
                $physical_count += $qty;
                $bundle_components = (isset($r['bundle_components']) && is_array($r['bundle_components'])) ? $r['bundle_components'] : [];
                foreach ($bundle_components as $c) {
                    $cpid = (int) ($c['product_id'] ?? 0);
                    $cq   = max(1, (int) ($c['qty'] ?? 1)) * $qty;
                    if ($cpid <= 0) {
                        continue;
                    }
                    $crow = $this->db->select('stock_quantity, stock_status, name, status, deleted_at')->where('id', $cpid)->get('products')->row_array();
                    // A component must still be sellable — the bundle parent's own
                    // status doesn't cover its components.
                    if (!$crow || $crow['deleted_at'] !== null || $crow['status'] !== 'Active') {
                        return ['ok' => false, 'code' => 'unavailable',
                                'message' => '"' . ($crow['name'] ?? 'A bundle item') . '" is no longer available'];
                    }
                    if ($crow['stock_status'] !== 'pre_order' && (int) $crow['stock_quantity'] < $cq) {
                        return ['ok' => false, 'code' => 'insufficient_stock',
                                'message' => 'Insufficient stock for "' . $crow['name'] . '" (available ' . (int) $crow['stock_quantity'] . ')'];
                    }
                }
            } elseif (!$is_digital) {
                $has_physical = true;
                $physical_count += $qty;
                $avail = $r['variant_id'] ? (int) $r['variant_stock'] : (int) $r['stock_quantity'];
                if (!$is_pre_order && $avail < $qty) {
                    return ['ok' => false, 'code' => 'insufficient_stock',
                            'message' => 'Insufficient stock for "' . $r['name'] . '" (available ' . $avail . ')'];
                }
            }

            $line = $price * $qty;
            $subtotal += $line;
            $count += $qty;

            $order_items[] = [
                'product_id'   => (int) $r['product_id'],
                'variant_id'   => $r['variant_id'] ? (int) $r['variant_id'] : null,
                'product_name' => $r['name'],
                'product_slug' => $r['slug'],
                'sku'          => $r['sku'] ?? null,
                'variant_name' => $r['variant_name'],
                'thumbnail'    => $r['thumbnail'],
                'unit_price'   => $price,
                'quantity'     => $qty,
                'line_total'   => $line,
                'meta'         => $is_bundle ? json_encode(['components' => $bundle_components]) : null,
            ];
            // Stock: a bundle decrements each component (not the virtual parent);
            // a physical item decrements itself; digital items decrement nothing.
            if ($is_bundle) {
                foreach ($bundle_components as $c) {
                    $cpid = (int) ($c['product_id'] ?? 0);
                    if ($cpid > 0) {
                        $stock_updates[] = ['product_id' => $cpid, 'variant_id' => null, 'qty' => max(1, (int) ($c['qty'] ?? 1)) * $qty, 'pre_order' => false];
                    }
                }
            } elseif (!$is_digital) {
                $stock_updates[] = [
                    'product_id' => (int) $r['product_id'],
                    'variant_id' => $r['variant_id'] ? (int) $r['variant_id'] : null,
                    'qty'        => $qty,
                    'pre_order'  => $is_pre_order,
                ];
            }
            $tax_lines[]  = ['taxable' => $line, 'tax_category_id' => $r['tax_category_id'] ?? null];
            $rule_lines[] = ['category_id' => $r['category_id'] ?? null, 'line_total' => $line];
        }

        // Guard the same BASE product appearing in multiple cart lines (e.g. a
        // standalone line + a bundle component sharing it, or two bundles): the
        // per-line checks above each saw the full rollup, so sum the demand per
        // base product and validate against the rollup once to prevent oversell.
        $base_demand = [];
        foreach ($stock_updates as $su) {
            if (!empty($su['pre_order']) || !empty($su['variant_id'])) {
                continue;
            }
            $bpid = (int) $su['product_id'];
            $base_demand[$bpid] = ($base_demand[$bpid] ?? 0) + (int) $su['qty'];
        }
        foreach ($base_demand as $bpid => $need) {
            $prow = $this->db->select('stock_quantity, name')->where('id', $bpid)->get('products')->row_array();
            if ($prow && (int) $prow['stock_quantity'] < $need) {
                return ['ok' => false, 'code' => 'insufficient_stock',
                        'message' => 'Insufficient stock for "' . $prow['name'] . '" (available ' . (int) $prow['stock_quantity'] . ')'];
            }
        }

        // Coupon (optional) — read from the cart, re-validate at checkout time.
        $coupon_discount = 0.0;
        $free_shipping = false;
        $applied_coupon = null;
        $coupon_code = $this->_cart_coupon_code($cart_id);
        if ($coupon_code) {
            $this->load->model('coupon_model');
            $cv = $this->coupon_model->validate($coupon_code, $subtotal, $identity);
            if (!$cv['ok']) {
                return ['ok' => false, 'code' => 'coupon_invalid', 'message' => 'Coupon problem: ' . $cv['message']];
            }
            $coupon_discount = (float) $cv['discount'];
            $free_shipping   = (bool) $cv['free_shipping'];
            $applied_coupon  = $cv['coupon'];
        }

        // Auto-applied cart price rules stack on top of the coupon.
        $cr = $this->cart_rule_model->evaluate($subtotal, $identity, $rule_lines);
        $cart_rule_discount = (float) $cr['discount'];
        if ($cr['free_shipping']) {
            $free_shipping = true;
        }
        $applied_cart_rules = $cr['applied'];

        // Combined discount, capped at subtotal.
        $discount = round(min($coupon_discount + $cart_rule_discount, $subtotal), 2);
        $disc_sub = max(0, $subtotal - $discount);

        // Shipping — method-derived (replaces the flat constant); free if a promo grants it.
        // A digital-only order (all virtual/downloadable) ships nothing.
        $division = $shipping['division'] ?? '';
        $shipping_charge = 0.00;
        $ship_code  = null;
        $ship_label = null;
        if (!$has_physical) {
            $ship_label = 'Digital delivery';
        } else {
            // Per-unit shipping is priced on physical quantity only — digital
            // items ship nothing and must not inflate it.
            $method     = $this->shipping_model->resolve($division, $disc_sub, $physical_count, $shipping_method_id);
            $ship_code  = $method ? $method['code'] : null;
            $ship_label = $method ? $method['title'] : null;
            if ($method && !$free_shipping) {
                $shipping_charge = (float) $method['computed_rate'];
            } elseif ($method && $free_shipping) {
                $ship_label = $method['title'] . ' (Free)';
            }
        }

        // Tax — exclusive, per line by category, matched on the shipping address.
        $tax_res = $this->tax_model->compute_for_lines(
            $tax_lines,
            ['country' => 'BD', 'state' => $division, 'postcode' => $shipping['postcode'] ?? '']
        );
        $tax = (float) $tax_res['tax'];

        $total = round($disc_sub + $shipping_charge + $tax, 2);
        $order_number = $this->generate_order_number();
        $now = date('Y-m-d H:i:s');
        $actor = $identity['user_id'] ?: null;

        $this->db->trans_begin();

        $this->db->insert('orders', [
            'order_number'          => $order_number,
            'user_id'               => $identity['user_id'] ?: null,
            'guest_token'           => $identity['user_id'] ? null : ($identity['guest_token'] ?? null),
            'customer_name'         => $customer['name'],
            'customer_phone'        => $customer['phone'],
            'customer_email'        => $customer['email'] ?? null,
            'shipping_division'     => $shipping['division'] ?? null,
            'shipping_district'     => $shipping['district'] ?? null,
            'shipping_area'         => $shipping['area'] ?? null,
            'shipping_address'      => $shipping['address'] ?? null,
            'shipping_landmark'     => $shipping['landmark'] ?? null,
            'shipping_postcode'     => $shipping['postcode'] ?? null,
            'payment_method'        => $payment_method,
            'payment_status'        => 'pending',
            'status'                => 'pending',
            'subtotal'              => $subtotal,
            'shipping_charge'       => $shipping_charge,
            'shipping_method'       => $ship_code,
            'shipping_method_label' => $ship_label,
            'discount'              => $discount,
            'coupon_code'           => $applied_coupon ? $applied_coupon['code'] : null,
            'tax'                   => $tax,
            'total'                 => $total,
            'currency'              => 'BDT',
            'item_count'            => $count,
            'note'                  => $note,
            'placed_at'             => $now,
            'created_at'            => $now,
        ]);
        $order_id = (int) $this->db->insert_id();

        // When the combined discount is capped at subtotal, scale each promotion's
        // recorded amount proportionally so the recorded totals never exceed the
        // discount actually applied.
        $raw_discount = $coupon_discount + $cart_rule_discount;
        $usage_ratio  = $raw_discount > 0 ? ($discount / $raw_discount) : 1.0;

        if ($applied_coupon) {
            $this->coupon_model->record_usage((int) $applied_coupon['id'], $order_id, $identity, round($coupon_discount * $usage_ratio, 2));
        }
        foreach ($applied_cart_rules as $ar) {
            // id=0 is the live customer-group discount (not a cart_rules row) — no usage record.
            if ((int) $ar['id'] > 0) {
                $this->cart_rule_model->record_usage((int) $ar['id'], $order_id, $identity, round((float) $ar['amount'] * $usage_ratio, 2));
            }
        }

        foreach ($order_items as $it) {
            $it['order_id']   = $order_id;
            $it['created_at'] = $now;
            $this->db->insert('order_items', $it);
        }

        $this->load->model('inventory_model');
        foreach ($stock_updates as $su) {
            if ($su['pre_order']) {
                continue;
            }
            if ($su['variant_id']) {
                // Variants keep their own single stock (not multi-source in v1).
                $this->db->set('stock_quantity', 'GREATEST(stock_quantity - ' . (int) $su['qty'] . ', 0)', false)
                    ->where('id', $su['variant_id'])->update('product_variants');
            } else {
                // Base products: allocate across inventory sources by priority and
                // refresh the cached rollup (products.stock_quantity). Honour the
                // allocated amount — a shortfall (stock drained by a concurrent
                // order between check and decrement) rolls the whole order back.
                $got = $this->inventory_model->allocate($order_id, (int) $su['product_id'], (int) $su['qty']);
                if ($got < (int) $su['qty']) {
                    $this->db->trans_rollback();
                    return ['ok' => false, 'code' => 'insufficient_stock',
                            'message' => 'Stock for one of your items changed during checkout. Please review your cart and try again.'];
                }
            }
        }

        $this->db->insert('order_status_history', [
            'order_id'   => $order_id,
            'status'     => 'pending',
            'note'       => 'Order placed',
            'changed_by' => $actor,
            'created_at' => $now,
        ]);

        $this->db->where('id', $cart_id)->update('carts', ['status' => 'ordered', 'updated_at' => $now]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return ['ok' => false, 'code' => 'txn_failed', 'message' => 'Order could not be placed. Please try again.'];
        }
        $this->db->trans_commit();
        return ['ok' => true, 'code' => 'placed', 'order_id' => $order_id, 'order_number' => $order_number];
    }

    private function _cart_coupon_code($cart_id)
    {
        $row = $this->db->select('coupon_code')->where('id', (int) $cart_id)->get('carts')->row();
        return $row ? $row->coupon_code : null;
    }

    // ---- reads ----

    public function find_order($id)
    {
        return $this->db->where('id', (int) $id)->get('orders')->row_array();
    }

    public function get_by_number($order_number, $user_id = null)
    {
        $this->db->where('order_number', $order_number);
        if ($user_id !== null) {
            $this->db->where('user_id', (int) $user_id);
        }
        return $this->db->get('orders')->row_array();
    }

    public function get_items($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->order_by('id', 'ASC')->get('order_items')->result_array();
    }

    public function get_history($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->order_by('id', 'ASC')->get('order_status_history')->result_array();
    }

    public function get_customer_orders($user_id, $page, $per_page)
    {
        $page     = max(1, (int) $page);
        $per_page = min(50, max(1, (int) $per_page));
        $offset   = ($page - 1) * $per_page;

        $total = $this->db->where('user_id', (int) $user_id)->count_all_results('orders');
        $this->db->where('user_id', (int) $user_id)->order_by('created_at', 'DESC')->limit($per_page, $offset);
        $items = $this->db->get('orders')->result_array();

        return ['total' => (int) $total, 'items' => $items];
    }

    // ---- status transitions ----

    public function update_status($order_id, $new_status, $note, $changed_by)
    {
        if (!in_array($new_status, self::STATUSES, true)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->where('id', (int) $order_id)->update('orders', ['status' => $new_status, 'updated_at' => $now]);
        $this->db->insert('order_status_history', [
            'order_id'   => (int) $order_id,
            'status'     => $new_status,
            'note'       => $note,
            'changed_by' => $changed_by,
            'created_at' => $now,
        ]);
        $this->db->trans_complete();
        return $this->db->trans_status() !== false;
    }

    // ---- admin DataTables ----

    public function count_all($status = '')
    {
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results('orders');
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start();
                $this->db->like('order_number', $search);
                $this->db->or_like('customer_name', $search);
                $this->db->or_like('customer_phone', $search);
                $this->db->group_end();
            }
        };

        $this->db->from('orders');
        $apply();
        $filtered = $this->db->count_all_results();

        $this->db->from('orders');
        $apply();
        $this->db->order_by($order_col, $order_dir);
        $this->db->limit($length, $start);
        $data = $this->db->get()->result();

        return ['filtered' => $filtered, 'data' => $data];
    }
}

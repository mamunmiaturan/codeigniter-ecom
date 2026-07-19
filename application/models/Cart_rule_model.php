<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @author   : Mamun Mia Turan
 * @filename : Cart_rule_model.php
 *
 * Auto-applied cart price rules — cart-level promotions that stack ON TOP of a
 * coded coupon (percentage / fixed off, or free shipping), optionally scoped to
 * a category and gated by min-subtotal + usage limits. Evaluated in sort_order;
 * end_other_rules short-circuits.
 */
class Cart_rule_model extends MY_Model
{
    protected $table = 'cart_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'name', 'description', 'status', 'sort_order', 'starts_at', 'ends_at',
        'min_subtotal', 'category_id', 'customer_group_id', 'action_type', 'discount_value', 'max_discount',
        'free_shipping', 'usage_limit', 'usage_limit_per_user', 'end_other_rules',
        'created_by', 'updated_by',
    ];

    // ================= Engine =================

    /**
     * Evaluate all active auto-rules against the cart.
     * @param float $subtotal  discounted-by-coupon-or-raw cart subtotal
     * @param array $identity  ['user_id'=>?, 'guest_token'=>?]
     * @param array $lines     each ['category_id'=>?int, 'line_total'=>float]
     * @return array ['discount'=>float, 'free_shipping'=>bool, 'applied'=>[['id','name','amount','free_shipping'], ...]]
     */
    public function evaluate($subtotal, $identity, $lines)
    {
        $subtotal = (float) $subtotal;
        $now = date('Y-m-d H:i:s');

        $rules = $this->db
            ->where('status', 'Active')->where('deleted_at', null)
            ->group_start()->where('starts_at', null)->or_where('starts_at <=', $now)->group_end()
            ->group_start()->where('ends_at', null)->or_where('ends_at >=', $now)->group_end()
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('cart_rules')->result_array();

        // Resolve the shopper's customer group: group_id for admin group-scoped
        // rules, and the LIVE group discount (read from customer_groups directly,
        // so admin edits to discount_percent take effect immediately).
        $group_id = null;
        $group = null;
        if ($this->db->field_exists('customer_group_id', 'users')) {
            $this->load->model('customer_group_model');
            $uid = !empty($identity['user_id']) ? (int) $identity['user_id'] : null;
            $group_id = $this->customer_group_model->group_for_user($uid);
            $group = $this->customer_group_model->active_group_discount($uid);
        }

        $total_discount = 0.0;
        $free_shipping = false;
        $applied = [];

        foreach ($rules as $r) {
            // Group-scoped rules apply only to members of that group.
            if (!empty($r['customer_group_id']) && (int) $r['customer_group_id'] !== $group_id) {
                continue;
            }
            // Base amount the discount applies to (whole cart or a category slice).
            if (!empty($r['category_id'])) {
                $base = 0.0;
                foreach ($lines as $ln) {
                    if ((int) ($ln['category_id'] ?? 0) === (int) $r['category_id']) {
                        $base += (float) $ln['line_total'];
                    }
                }
            } else {
                $base = $subtotal;
            }
            if ($base <= 0) {
                continue;
            }
            if ($subtotal < (float) $r['min_subtotal']) {
                continue;
            }
            if ($r['usage_limit'] !== null && (int) $r['used_count'] >= (int) $r['usage_limit']) {
                continue;
            }
            if ($r['usage_limit_per_user'] !== null
                && $this->_user_usage((int) $r['id'], $identity) >= (int) $r['usage_limit_per_user']) {
                continue;
            }

            $is_free_ship = ($r['action_type'] === 'free_shipping' || (int) $r['free_shipping'] === 1);
            $disc = 0.0;
            if ($r['action_type'] === 'percentage') {
                $disc = $base * ((float) $r['discount_value'] / 100);
                if ($r['max_discount'] !== null && $disc > (float) $r['max_discount']) {
                    $disc = (float) $r['max_discount'];
                }
            } elseif ($r['action_type'] === 'fixed') {
                $disc = min((float) $r['discount_value'], $base);
            }

            // Never discount more than the subtotal left after prior rules.
            $disc = round(min($disc, max(0, $subtotal - $total_discount)), 2);

            if ($disc > 0 || $is_free_ship) {
                $total_discount += $disc;
                if ($is_free_ship) {
                    $free_shipping = true;
                }
                $applied[] = ['id' => (int) $r['id'], 'name' => $r['name'], 'amount' => $disc, 'free_shipping' => $is_free_ship];
                if ((int) $r['end_other_rules'] === 1) {
                    break;
                }
            }
        }

        // Automatic customer-group discount — applied live from the group's
        // discount_percent. id=0 marks it as NOT a cart_rules row, so no usage
        // record is written for it (see Order_model::create_from_cart).
        if ($group) {
            $gdisc = round(min($subtotal * $group['discount_percent'] / 100, max(0, $subtotal - $total_discount)), 2);
            if ($gdisc > 0) {
                $total_discount += $gdisc;
                $applied[] = ['id' => 0, 'name' => $group['name'] . ' group discount', 'amount' => $gdisc, 'free_shipping' => false];
            }
        }

        return [
            'discount'      => round(min($total_discount, $subtotal), 2),
            'free_shipping' => $free_shipping,
            'applied'       => $applied,
        ];
    }

    private function _user_usage($rule_id, $identity)
    {
        $this->db->where('cart_rule_id', (int) $rule_id);
        if (!empty($identity['user_id'])) {
            $this->db->where('user_id', (int) $identity['user_id']);
        } elseif (!empty($identity['guest_token'])) {
            $this->db->where('guest_token', $identity['guest_token']);
        } else {
            return 0;
        }
        return (int) $this->db->count_all_results('cart_rule_usages');
    }

    public function record_usage($rule_id, $order_id, $identity, $amount)
    {
        $this->db->insert('cart_rule_usages', [
            'cart_rule_id'    => (int) $rule_id,
            'user_id'         => !empty($identity['user_id']) ? (int) $identity['user_id'] : null,
            'guest_token'     => empty($identity['user_id']) ? ($identity['guest_token'] ?? null) : null,
            'order_id'        => $order_id ? (int) $order_id : null,
            'discount_amount' => $amount,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $this->db->set('used_count', 'used_count + 1', false)->where('id', (int) $rule_id)->update('cart_rules');
    }

    // ================= Admin CRUD =================

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('cart_rules')->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()->like('name', $search)->or_like('description', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }
}

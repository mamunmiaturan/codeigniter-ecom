<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @author   : Mamun Mia Turan
 * @filename : Catalog_rule_model.php
 *
 * Catalog price rules — adjust product prices by scope (all / category /
 * product), materialised into `catalog_rule_product_prices` (one row per
 * affected product = the final, lowest rule price). The storefront, cart and
 * order engine read that index so a rule price behaves like a live special price.
 */
class Catalog_rule_model extends MY_Model
{
    protected $table = 'catalog_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'name', 'description', 'status', 'sort_order', 'starts_at', 'ends_at',
        'scope', 'category_id', 'product_id', 'action_type', 'discount_value', 'end_other_rules',
        'created_by', 'updated_by',
    ];

    // ================= Indexer =================

    /**
     * Rebuild the whole price index from currently-active, in-window rules.
     * @return int number of products with a rule price
     */
    public function reindex()
    {
        $now = date('Y-m-d H:i:s');

        $rules = $this->db
            ->where('status', 'Active')->where('deleted_at', null)
            ->group_start()->where('starts_at', null)->or_where('starts_at <=', $now)->group_end()
            ->group_start()->where('ends_at', null)->or_where('ends_at >=', $now)->group_end()
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('catalog_rules')->result_array();

        $this->db->truncate('catalog_rule_product_prices');
        if (empty($rules)) {
            return 0;
        }

        $products = $this->db->select('id, category_id, price, special_price')
            ->where('deleted_at', null)->where('status', 'Active')
            ->get('products')->result_array();

        $count = 0;
        foreach ($products as $p) {
            $base = ($p['special_price'] !== null && (float) $p['special_price'] > 0) ? (float) $p['special_price'] : (float) $p['price'];
            $price = $base;
            $winning = null;
            foreach ($rules as $r) {
                if (!$this->_matches($r, $p)) {
                    continue;
                }
                if ($r['action_type'] === 'percentage') {
                    $price = $price * (1 - (float) $r['discount_value'] / 100);
                } else { // fixed
                    $price = max(0, $price - (float) $r['discount_value']);
                }
                $winning = (int) $r['id'];
                if ((int) $r['end_other_rules'] === 1) {
                    break;
                }
            }
            $price = round(max(0, $price), 2);
            if ($winning && $price < $base) {
                $this->db->insert('catalog_rule_product_prices', [
                    'product_id'      => (int) $p['id'],
                    'catalog_rule_id' => $winning,
                    'price'           => $price,
                    'computed_at'     => $now,
                ]);
                $count++;
            }
        }
        return $count;
    }

    private function _matches($rule, $product)
    {
        if ($rule['scope'] === 'all') {
            return true;
        }
        if ($rule['scope'] === 'category') {
            return !empty($rule['category_id']) && (int) $product['category_id'] === (int) $rule['category_id'];
        }
        if ($rule['scope'] === 'product') {
            return !empty($rule['product_id']) && (int) $product['id'] === (int) $rule['product_id'];
        }
        return false;
    }

    public function price_for($product_id)
    {
        $row = $this->db->select('price')->where('product_id', (int) $product_id)->get('catalog_rule_product_prices')->row_array();
        return $row ? (float) $row['price'] : null;
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
            $this->db->from('catalog_rules')->where('deleted_at', null);
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
        $ok = $this->update($id, ['status' => $new]);
        return $ok ? $new : false;
    }
}

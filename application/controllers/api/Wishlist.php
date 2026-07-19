<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Customer wishlist API (requires a customer bearer token).
 *
 *  GET  /api/v1/wishlist            -> list wishlist items
 *  POST /api/v1/wishlist/add        {product_id}
 *  POST /api/v1/wishlist/remove     {product_id}
 *  POST /api/v1/wishlist/toggle     {product_id} -> {action: added|removed}
 */
class Wishlist extends Api_Controller
{
    private $_body_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['wishlist_model', 'product_model']);
    }

    public function index()
    {
        $uid = (int) $this->auth_user['id'];
        $items = array_map([$this, '_shape'], $this->wishlist_model->list_detailed($uid));
        $this->ok(['count' => count($items), 'items' => $items]);
    }

    public function add()
    {
        $uid = (int) $this->auth_user['id'];
        $pid = $this->_product_id();
        if (!$pid) {
            return;
        }
        $this->wishlist_model->add($uid, $pid);
        $this->ok(['in_wishlist' => true, 'count' => $this->wishlist_model->count_for($uid)]);
    }

    public function remove()
    {
        $uid = (int) $this->auth_user['id'];
        $pid = $this->_product_id(false);
        if (!$pid) {
            return;
        }
        $this->wishlist_model->remove($uid, $pid);
        $this->ok(['in_wishlist' => false, 'count' => $this->wishlist_model->count_for($uid)]);
    }

    public function toggle()
    {
        $uid = (int) $this->auth_user['id'];
        $pid = $this->_product_id();
        if (!$pid) {
            return;
        }
        $action = $this->wishlist_model->toggle($uid, $pid);
        $this->ok([
            'action'      => $action,
            'in_wishlist' => $action === 'added',
            'count'       => $this->wishlist_model->count_for($uid),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Resolve and validate the product_id from the body. When $check_active is
     * true the product must exist and be Active. Emits a failure and returns 0
     * on error.
     */
    private function _product_id($check_active = true)
    {
        $b   = $this->_json_body();
        $pid = (int) ($b['product_id'] ?? 0);
        if ($pid <= 0) {
            $this->fail('product_id is required', 422);
            return 0;
        }
        if ($check_active) {
            $p = $this->product_model->find($pid);
            if (!$p || $p['status'] !== 'Active') {
                $this->fail('Product not available', 404);
                return 0;
            }
        }
        return $pid;
    }

    private function _shape($r)
    {
        $special = ($r['special_price'] !== null && (float) $r['special_price'] > 0) ? (float) $r['special_price'] : null;
        return [
            'product_id'      => (int) $r['id'],
            'name'            => $r['name'],
            'slug'            => $r['slug'],
            'thumbnail'       => $r['thumbnail'] ? base_url('uploads/catalog/product/' . $r['thumbnail']) : null,
            'price'           => number_format((float) $r['price'], 2, '.', ''),
            'special_price'   => $special !== null ? number_format($special, 2, '.', '') : null,
            'effective_price' => number_format($special !== null ? $special : (float) $r['price'], 2, '.', ''),
            'currency'        => $r['currency'] ?: 'BDT',
            'stock_status'    => $r['stock_status'],
            'added_at'        => $r['created_at'],
        ];
    }

    private function _json_body()
    {
        if ($this->_body_cache !== null) {
            return $this->_body_cache;
        }
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        $this->_body_cache = is_array($decoded) ? $decoded : ($this->input->post() ?: []);
        return $this->_body_cache;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Customer order API (auth required — a customer views their own orders).
 *
 *  GET /api/v1/orders            -> paginated order history
 *  GET /api/v1/orders/{number}   -> order detail incl. items + status timeline
 */
class Order extends Api_Controller
{
    protected $require_auth = true;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('order_model');
    }

    public function index()
    {
        $uid      = (int) $this->auth_user['id'];
        $page     = max(1, (int) ($this->input->get('page') ?: 1));
        $per_page = min(50, max(1, (int) ($this->input->get('per_page') ?: 20)));

        $res = $this->order_model->get_customer_orders($uid, $page, $per_page);
        $total = $res['total'];

        $this->ok([
            'items'      => array_map([$this, '_shape_list'], $res['items']),
            'pagination' => [
                'page'        => $page,
                'per_page'    => $per_page,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $per_page),
                'has_more'    => ($page * $per_page) < $total,
            ],
        ]);
    }

    public function show($order_number = '')
    {
        $uid   = (int) $this->auth_user['id'];
        $order = $this->order_model->get_by_number(rawurldecode($order_number), $uid);
        if (!$order) {
            $this->fail('Order not found', 404);
            return;
        }
        $items   = $this->order_model->get_items($order['id']);
        $history = $this->order_model->get_history($order['id']);
        $this->ok($this->_shape_detail($order, $items, $history));
    }

    // ------------------------------------------------------------------

    private function _shape_list($o)
    {
        return [
            'order_number' => $o['order_number'],
            'status'       => $o['status'],
            'payment'      => ['method' => $o['payment_method'], 'status' => $o['payment_status']],
            'item_count'   => (int) $o['item_count'],
            'currency'     => $o['currency'],
            'total'        => $o['total'],
            'placed_at'    => $o['placed_at'],
        ];
    }

    private function _shape_detail($o, $items, $history)
    {
        return [
            'order_number' => $o['order_number'],
            'status'       => $o['status'],
            'payment'      => ['method' => $o['payment_method'], 'status' => $o['payment_status']],
            'currency'     => $o['currency'],
            'totals'       => [
                'subtotal'        => $o['subtotal'],
                'shipping_charge' => $o['shipping_charge'],
                'discount'        => $o['discount'],
                'tax'             => $o['tax'],
                'total'           => $o['total'],
            ],
            'coupon_code'  => $o['coupon_code'] ?? null,
            'item_count'   => (int) $o['item_count'],
            'shipping'     => [
                'name'     => $o['customer_name'],
                'phone'    => $o['customer_phone'],
                'email'    => $o['customer_email'],
                'division' => $o['shipping_division'],
                'district' => $o['shipping_district'],
                'area'     => $o['shipping_area'],
                'address'  => $o['shipping_address'],
                'landmark' => $o['shipping_landmark'],
                'postcode' => $o['shipping_postcode'],
            ],
            'note'         => $o['note'],
            'placed_at'    => $o['placed_at'],
            'items'        => array_map(function ($it) {
                return [
                    'product_name' => $it['product_name'],
                    'product_slug' => $it['product_slug'],
                    'sku'          => $it['sku'],
                    'variant_name' => $it['variant_name'],
                    'thumbnail'    => $it['thumbnail'] ? base_url('uploads/catalog/product/' . $it['thumbnail']) : null,
                    'unit_price'   => $it['unit_price'],
                    'quantity'     => (int) $it['quantity'],
                    'line_total'   => $it['line_total'],
                ];
            }, $items),
            'timeline'     => array_map(function ($h) {
                return ['status' => $h['status'], 'note' => $h['note'], 'at' => $h['created_at']];
            }, $history),
        ];
    }
}

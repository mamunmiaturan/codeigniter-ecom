<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Checkout API — converts the caller's active cart into an order.
 *
 *  POST /api/v1/checkout
 *    body: { name?, phone?, email?, division, district, area, address,
 *            landmark?, postcode?, payment_method?(cod|online), note? }
 *    identity: Bearer access token (customer) OR X-Cart-Token / cart_token (guest)
 *
 * For logged-in customers, name/phone/email default from the profile if omitted.
 * Server re-validates price + stock, decrements stock, closes the cart, and
 * returns the created order (COD; online payment is a later phase).
 */
class Checkout extends Api_Controller
{
    protected $require_auth = false;
    private $_body_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['cart_model', 'order_model', 'customer_model', 'payment_model']);
        $this->load->library('jwt');
    }

    public function index()
    {
        $identity = $this->_identity();
        $cart_id  = $this->cart_model->get_active_cart_id($identity['user_id'], $identity['guest_token'], false);
        if (!$cart_id) {
            $this->fail('Your cart is empty', 400);
            return;
        }

        $b = $this->_json_body();
        $email = trim((string) ($b['email'] ?? ''));

        // Option A — a saved address id (logged-in customers only, ownership-scoped).
        $saved = null;
        $addr_id = (int) ($b['address_id'] ?? 0);
        if ($identity['user_id'] && $addr_id > 0) {
            $this->load->model('customer_address_model');
            $saved = $this->customer_address_model->get($addr_id, $identity['user_id']);
            if (!$saved) {
                $this->fail('Saved address not found', 404);
                return;
            }
        }

        if ($saved) {
            $name  = $saved['name'];
            $phone = $saved['phone'];
            $shipping = [
                'division' => $saved['division'],
                'district' => $saved['district'],
                'area'     => $saved['area'],
                'address'  => $saved['address'],
                'landmark' => $saved['landmark'],
                'postcode' => $saved['postcode'],
            ];
        } else {
            // Option B — inline fields (defaulted from profile for logged-in users).
            $name  = trim((string) ($b['name'] ?? ''));
            $phone = trim((string) ($b['phone'] ?? ''));
            if ($identity['user_id'] && ($name === '' || $phone === '' || $email === '')) {
                $p = $this->customer_model->get_profile($identity['user_id']);
                if ($p) {
                    $name  = $name  !== '' ? $name  : $p['name'];
                    $phone = $phone !== '' ? $phone : $p['mobile_no'];
                    $email = $email !== '' ? $email : $p['email'];
                }
            }
            if ($name === '' || $phone === '') {
                $this->fail('name and phone are required', 422);
                return;
            }
            $shipping = [
                'division' => trim((string) ($b['division'] ?? '')),
                'district' => trim((string) ($b['district'] ?? '')),
                'area'     => trim((string) ($b['area'] ?? '')),
                'address'  => trim((string) ($b['address'] ?? '')),
                'landmark' => trim((string) ($b['landmark'] ?? '')),
                'postcode' => trim((string) ($b['postcode'] ?? '')),
            ];
            if ($shipping['address'] === '') {
                $this->fail('Shipping address is required', 422);
                return;
            }
        }

        $payment_method = trim((string) ($b['payment_method'] ?? 'cod')) ?: 'cod';
        if (!$this->payment_model->is_valid_method($payment_method)) {
            $payment_method = 'cod';
        }
        $shipping_method_id = (int) ($b['shipping_method'] ?? 0) ?: null;
        $note = trim((string) ($b['note'] ?? '')) ?: null;

        $res = $this->order_model->create_from_cart(
            $cart_id,
            $identity,
            ['name' => $name, 'phone' => $phone, 'email' => $email ?: null],
            $shipping,
            $payment_method,
            $note,
            $shipping_method_id
        );

        if (!$res['ok']) {
            $status = in_array($res['code'], ['insufficient_stock', 'unavailable'], true) ? 409 : 400;
            $this->fail($res['message'], $status, ['code_detail' => $res['code']]);
            return;
        }

        $order = $this->order_model->find_order($res['order_id']);
        $items = $this->order_model->get_items($res['order_id']);
        $resp  = $this->_shape_order($order, $items);

        // For online methods, hand back the gateway redirect URL (mobile clients open it).
        $gw = $this->payment_model->gateway($payment_method);
        if ($gw && $gw->is_online()) {
            $begin = $gw->begin($order);
            if (!empty($begin['redirect'])) {
                $resp['payment']['redirect_url'] = $begin['redirect'];
            } elseif (!empty($begin['error'])) {
                $resp['payment']['error'] = $begin['error'];
            }
        }
        $this->ok($resp, 201);
    }

    // ------------------------------------------------------------------

    private function _shape_order($o, $items)
    {
        return [
            'order_number' => $o['order_number'],
            'status'       => $o['status'],
            'payment'      => ['method' => $o['payment_method'], 'status' => $o['payment_status']],
            'currency'     => $o['currency'],
            'totals'       => [
                'subtotal'        => $o['subtotal'],
                'shipping_charge' => $o['shipping_charge'],
                'shipping_method' => $o['shipping_method_label'] ?? null,
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
        ];
    }

    private function _identity()
    {
        $token = Jwt::extract_bearer($this->input->get_request_header('Authorization', true) ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($token) {
            try {
                $claims = $this->jwt->decode($token);
                if (($claims['type'] ?? '') === 'access') {
                    return ['user_id' => (int) $claims['sub'], 'guest_token' => null];
                }
            } catch (Throwable $e) {
                // fall through to guest
            }
        }
        $gt = $this->input->get_request_header('X-Cart-Token', true);
        if (!$gt) {
            $b = $this->_json_body();
            $gt = $b['cart_token'] ?? ($this->input->get('cart_token', true) ?: '');
        }
        return ['user_id' => null, 'guest_token' => $gt ?: null];
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

<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Shopping cart API. Works for both anonymous guests and logged-in customers.
 *
 * Identity resolution per request:
 *   - Valid Bearer access token  -> cart bound to user_id
 *   - else X-Cart-Token header / cart_token field -> guest cart
 *   - else a new guest token is minted and returned as `cart_token`
 *
 *  GET  /api/v1/cart
 *  POST /api/v1/cart/add     {product_id, variant_id?, quantity}
 *  POST /api/v1/cart/update  {item_id, quantity}   (quantity<=0 removes)
 *  POST /api/v1/cart/remove  {item_id}
 *  POST /api/v1/cart/clear
 *  POST /api/v1/cart/merge   (Bearer) {cart_token}  -> fold guest cart into user cart
 */
class Cart extends Api_Controller
{
    protected $require_auth = false;
    private $_body_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['cart_model', 'product_model', 'coupon_model']);
        $this->load->library('jwt');
    }

    public function index()
    {
        $id = $this->_identity();
        $cart_id = $this->cart_model->get_active_cart_id($id['user_id'], $id['guest_token'], true);
        $this->_respond_cart($cart_id, $id);
    }

    public function add()
    {
        $id = $this->_identity();
        $b  = $this->_json_body();
        $product_id = (int) ($b['product_id'] ?? 0);
        $variant_id = (int) ($b['variant_id'] ?? 0) ?: null;
        $qty        = max(1, (int) ($b['quantity'] ?? 1));

        if ($product_id <= 0) {
            $this->fail('product_id is required', 422);
            return;
        }
        $product = $this->product_model->find($product_id);
        if (!$product || $product['status'] !== 'Active') {
            $this->fail('Product not available', 404);
            return;
        }
        $ptype = $product['product_type'] ?? 'simple';

        // Composite parents can't be added as a plain priced line here — grouped +
        // bundle need option selection (storefront), configurable needs a variant.
        if (in_array($ptype, ['grouped', 'bundle'], true)) {
            $this->fail('This product must be configured before it can be added to the cart.', 422);
            return;
        }
        if ($ptype === 'configurable' && !$variant_id) {
            $this->fail('Please choose the product options before adding to cart.', 422);
            return;
        }

        $price = $this->_effective_price($product);
        $avail = (int) $product['stock_quantity'];

        if ($variant_id) {
            $variant = $this->product_model->get_variant($variant_id, $product_id);
            if (!$variant) {
                $this->fail('Variant not available', 404);
                return;
            }
            if ($variant['price'] !== null && $variant['price'] !== '' && (float) $variant['price'] > 0) {
                $price = (float) $variant['price'];
            } elseif ($ptype === 'configurable') {
                // A configurable variant must carry its own price (parent is 0).
                $this->fail('This option is not available for purchase.', 409);
                return;
            }
            $avail = (int) $variant['stock_quantity'];
        }

        if ($product['stock_status'] === 'out_of_stock') {
            $this->fail('Product is out of stock', 409);
            return;
        }

        $cart_id  = $this->cart_model->get_active_cart_id($id['user_id'], $id['guest_token'], true);
        $existing = $this->cart_model->find_item($cart_id, $product_id, $variant_id);
        $already  = $existing ? (int) $existing->quantity : 0;

        if ($product['stock_status'] !== 'pre_order' && ($already + $qty) > $avail) {
            $this->fail('Insufficient stock. Available: ' . $avail, 409, ['available' => $avail]);
            return;
        }

        $this->cart_model->add_or_increment($cart_id, $product_id, $variant_id, $qty, $price);
        $this->_respond_cart($cart_id, $id);
    }

    public function update()
    {
        $id  = $this->_identity();
        $b   = $this->_json_body();
        $item_id = (int) ($b['item_id'] ?? 0);
        $qty     = (int) ($b['quantity'] ?? 0);

        $cart_id = $this->cart_model->get_active_cart_id($id['user_id'], $id['guest_token'], false);
        if (!$cart_id) {
            $this->fail('Cart is empty', 404);
            return;
        }
        $item = $this->cart_model->get_item($cart_id, $item_id);
        if (!$item) {
            $this->fail('Item not found in cart', 404);
            return;
        }
        if ($qty <= 0) {
            $this->cart_model->remove_item($cart_id, $item_id);
        } else {
            $this->cart_model->update_qty($cart_id, $item_id, $qty);
        }
        $this->_respond_cart($cart_id, $id);
    }

    public function remove()
    {
        $id  = $this->_identity();
        $b   = $this->_json_body();
        $item_id = (int) ($b['item_id'] ?? 0);

        $cart_id = $this->cart_model->get_active_cart_id($id['user_id'], $id['guest_token'], false);
        if (!$cart_id) {
            $this->fail('Cart is empty', 404);
            return;
        }
        $this->cart_model->remove_item($cart_id, $item_id);
        $this->_respond_cart($cart_id, $id);
    }

    public function clear()
    {
        $id = $this->_identity();
        $cart_id = $this->cart_model->get_active_cart_id($id['user_id'], $id['guest_token'], false);
        if ($cart_id) {
            $this->cart_model->clear($cart_id);
        }
        $this->_respond_cart($cart_id, $id);
    }

    public function merge()
    {
        $claims = $this->_auth();
        if (!$claims) {
            return;
        }
        $b = $this->_json_body();
        $guest_token = $this->input->get_request_header('X-Cart-Token', true) ?: ($b['cart_token'] ?? '');
        if ($guest_token) {
            $this->cart_model->merge_guest_into_user($guest_token, (int) $claims['sub']);
        }
        $identity = ['user_id' => (int) $claims['sub'], 'guest_token' => null, 'new_token' => false];
        $cart_id  = $this->cart_model->get_active_cart_id((int) $claims['sub'], null, true);
        $this->_respond_cart($cart_id, $identity);
    }

    public function apply_coupon()
    {
        $id   = $this->_identity();
        $b    = $this->_json_body();
        $code = strtoupper(trim((string) ($b['code'] ?? '')));
        if ($code === '') {
            $this->fail('Coupon code is required', 422);
            return;
        }
        $cart_id  = $this->cart_model->get_active_cart_id($id['user_id'], $id['guest_token'], true);
        $subtotal = $this->_cart_subtotal($cart_id);
        if ($subtotal <= 0) {
            $this->fail('Add items to your cart before applying a coupon', 400);
            return;
        }
        $v = $this->coupon_model->validate($code, $subtotal, $id);
        if (!$v['ok']) {
            $this->fail($v['message'], 422, ['code_detail' => $v['code']]);
            return;
        }
        $this->cart_model->set_coupon($cart_id, $v['coupon']['code']);
        $this->_respond_cart($cart_id, $id);
    }

    public function remove_coupon()
    {
        $id = $this->_identity();
        $cart_id = $this->cart_model->get_active_cart_id($id['user_id'], $id['guest_token'], false);
        if ($cart_id) {
            $this->cart_model->clear_coupon($cart_id);
        }
        $this->_respond_cart($cart_id, $id);
    }

    // ------------------------------------------------------------------

    private function _cart_subtotal($cart_id)
    {
        $subtotal = 0.0;
        foreach ($this->cart_model->get_items_detailed($cart_id) as $r) {
            $subtotal += (float) $r['effective_price'] * (int) $r['quantity'];
        }
        return $subtotal;
    }

    private function _effective_price($product)
    {
        // Catalog-rule-aware effective price for the add path (variant handled by caller).
        $this->load->model('catalog_rule_model');
        $base = ($product['special_price'] !== null && $product['special_price'] !== '' && (float) $product['special_price'] > 0)
            ? (float) $product['special_price'] : (float) $product['price'];
        $rule = $this->catalog_rule_model->price_for($product['id']);
        if ($rule !== null && $rule > 0 && $rule < $base) {
            $base = $rule;
        }
        return $base;
    }

    private function _respond_cart($cart_id, $identity)
    {
        $rows = $cart_id ? $this->cart_model->get_items_detailed($cart_id) : [];
        $items = [];
        $subtotal = 0.0;
        $count = 0;
        $currency = 'BDT';
        $rule_lines = [];

        foreach ($rows as $r) {
            $base  = (float) $r['effective_price'];
            $qty   = (int) $r['quantity'];
            $line  = $base * $qty;
            $rule_lines[] = ['category_id' => $r['category_id'] ?? null, 'line_total' => $line];
            // Bundle availability comes from its components; every other line from
            // the product/variant stock.
            if (($r['product_type'] ?? '') === 'bundle') {
                $avail = (int) ($r['bundle_available_qty'] ?? 0);
            } else {
                $avail = $r['variant_id'] ? (int) $r['variant_stock'] : (int) $r['stock_quantity'];
            }
            $currency = $r['currency'] ?: 'BDT';

            $items[] = [
                'id'      => (int) $r['id'],
                'product' => [
                    'id'        => (int) $r['product_id'],
                    'name'      => $r['name'],
                    'slug'      => $r['slug'],
                    'thumbnail' => $r['thumbnail'] ? base_url('uploads/catalog/product/' . $r['thumbnail']) : null,
                ],
                'variant'       => $r['variant_id'] ? ['id' => (int) $r['variant_id'], 'name' => $r['variant_name']] : null,
                'quantity'      => $qty,
                'unit_price'    => number_format($base, 2, '.', ''),
                'line_total'    => number_format($line, 2, '.', ''),
                'in_stock'      => ($r['product_status'] === 'Active' && ($r['stock_status'] === 'pre_order' || $avail >= $qty)),
                'available_qty' => $avail,
            ];
            $subtotal += $line;
            $count += $qty;
        }

        // Coupon (optional) — re-validate against the current subtotal; auto-remove if no longer valid.
        $coupon = null;
        $coupon_discount = 0.0;
        $code = $cart_id ? $this->cart_model->get_coupon_code($cart_id) : null;
        if ($code) {
            $v = $this->coupon_model->validate($code, $subtotal, $identity);
            if ($v['ok']) {
                $coupon_discount = (float) $v['discount'];
                $coupon = [
                    'code'          => $v['coupon']['code'],
                    'type'          => $v['coupon']['type'],
                    'free_shipping' => (bool) $v['free_shipping'],
                    'discount'      => number_format($coupon_discount, 2, '.', ''),
                ];
            } else {
                $this->cart_model->clear_coupon($cart_id);
            }
        }

        // Auto cart-price rules + customer-group discount — parity with the web cart
        // and the order engine. Shipping + tax need an address, so are quoted at checkout.
        $this->load->model('cart_rule_model');
        $cr = $this->cart_rule_model->evaluate($subtotal, $identity, $rule_lines);
        $cart_rule_discount = (float) $cr['discount'];
        $auto_discounts = array_map(function ($a) {
            return ['name' => $a['name'], 'amount' => number_format((float) $a['amount'], 2, '.', '')];
        }, $cr['applied']);

        $discount = round(min($coupon_discount + $cart_rule_discount, $subtotal), 2);
        $total = max(0, $subtotal - $discount);

        $data = [
            'cart_id'        => $cart_id ? (int) $cart_id : null,
            'item_count'     => $count,
            'currency'       => $currency,
            'subtotal'       => number_format($subtotal, 2, '.', ''),
            'discount'       => number_format($discount, 2, '.', ''),
            'coupon'         => $coupon,
            'auto_discounts' => $auto_discounts,
            'total'          => number_format($total, 2, '.', ''),
            'items'          => $items,
        ];
        if (!empty($identity['guest_token'])) {
            $data['cart_token'] = $identity['guest_token'];
        }
        $this->ok($data);
    }

    private function _identity()
    {
        $token = Jwt::extract_bearer($this->input->get_request_header('Authorization', true) ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($token) {
            try {
                $claims = $this->jwt->decode($token);
                if (($claims['type'] ?? '') === 'access') {
                    return ['user_id' => (int) $claims['sub'], 'guest_token' => null, 'new_token' => false];
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
        $new = false;
        if (!$gt) {
            $gt = 'g_' . app_generate_hash();
            $new = true;
        }
        return ['user_id' => null, 'guest_token' => $gt, 'new_token' => $new];
    }

    private function _auth()
    {
        $token = Jwt::extract_bearer($this->input->get_request_header('Authorization', true) ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if (!$token) {
            $this->fail('Missing bearer token', 401);
            return null;
        }
        try {
            $claims = $this->jwt->decode($token);
        } catch (Throwable $e) {
            $this->fail('Invalid token', 401);
            return null;
        }
        if (($claims['type'] ?? '') !== 'access') {
            $this->fail('Wrong token type', 401);
            return null;
        }
        return $claims;
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

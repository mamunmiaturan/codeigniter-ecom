<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Payment
 * @author   : Mamun Mia Turan
 * @filename : Payment_gateways.php
 *
 * Provider-agnostic payment gateway classes. Resolved by Payment_model from the
 * config/payment_methods.php registry merged with `payment_settings` overrides.
 *
 * Flow is "order-first": the order is created (payment_status=pending), then
 * begin($order) either returns ['inline'=>true] (offline — COD, nothing more to
 * do) or ['redirect'=>url] (online — send the customer to the gateway). The
 * gateway callback marks the order paid via Payment_model::mark_paid().
 */

abstract class Payment_gateway
{
    protected $code;
    protected $meta;

    public function __construct($code, array $meta)
    {
        $this->code = $code;
        $this->meta = $meta;
    }

    public function code()        { return $this->code; }
    public function title()       { return $this->meta['title'] ?? ucfirst($this->code); }
    public function description() { return $this->meta['description'] ?? ''; }
    public function is_online()   { return !empty($this->meta['is_online']); }
    public function is_active()   { return !empty($this->meta['is_active']); }
    public function sort()        { return (int) ($this->meta['sort'] ?? 0); }

    protected function cfg($key, $default = null)
    {
        return isset($this->meta['config'][$key]) ? $this->meta['config'][$key] : $default;
    }

    /**
     * Begin payment for a freshly created order row.
     * @return array ['inline'=>true] | ['redirect'=>url] | ['error'=>message]
     */
    abstract public function begin(array $order);

    protected function post($url, array $fields)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        return ['body' => $body, 'error' => $err];
    }

    protected function get($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        return ['body' => $body, 'error' => $err];
    }
}

/**
 * Cash on Delivery — offline, no redirect. The order is placed pending payment.
 */
class Cod_gateway extends Payment_gateway
{
    public function begin(array $order)
    {
        return ['inline' => true];
    }
}

/**
 * Mock gateway — simulates an online redirect to a local fake gateway page so
 * the whole redirect→callback→paid flow can be tested without credentials.
 */
class Mock_gateway extends Payment_gateway
{
    public function begin(array $order)
    {
        return ['redirect' => base_url('payment/mock/' . rawurlencode($order['order_number']))];
    }
}

/**
 * SSLCommerz — Bangladesh aggregator (cards + bKash/Nagad/Rocket + net banking).
 * Redirect + IPN. Needs store_id / store_passwd (sandbox or live) set in admin.
 */
class Sslcommerz_gateway extends Payment_gateway
{
    private function base_url_api()
    {
        return $this->cfg('sandbox', true)
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    public function begin(array $order)
    {
        $store_id     = trim((string) $this->cfg('store_id'));
        $store_passwd = trim((string) $this->cfg('store_passwd'));
        if ($store_id === '' || $store_passwd === '') {
            return ['error' => 'SSLCommerz is not configured. Add store credentials in admin.'];
        }

        $fields = [
            'store_id'         => $store_id,
            'store_passwd'     => $store_passwd,
            'total_amount'     => number_format((float) $order['total'], 2, '.', ''),
            'currency'         => $order['currency'] ?: 'BDT',
            'tran_id'          => $order['order_number'],
            'success_url'      => base_url('payment/success'),
            'fail_url'         => base_url('payment/fail'),
            'cancel_url'       => base_url('payment/cancel'),
            'ipn_url'          => base_url('payment/ipn'),
            'cus_name'         => $order['customer_name'],
            'cus_email'        => $order['customer_email'] ?: 'guest@example.com',
            'cus_phone'        => $order['customer_phone'],
            'cus_add1'         => $order['shipping_address'] ?: 'N/A',
            'cus_city'         => $order['shipping_district'] ?: 'Dhaka',
            'cus_country'      => 'Bangladesh',
            'shipping_method'  => 'NO',
            'num_of_item'      => (int) $order['item_count'],
            'product_name'     => 'Order ' . $order['order_number'],
            'product_category' => 'general',
            'product_profile'  => 'general',
            'value_a'          => $order['order_number'], // echoed back on callback
        ];

        $res = $this->post($this->base_url_api() . '/gwprocess/v4/api.php', $fields);
        if ($res['error']) {
            return ['error' => 'SSLCommerz connection failed: ' . $res['error']];
        }
        $json = json_decode((string) $res['body'], true);
        if (is_array($json) && ($json['status'] ?? '') === 'SUCCESS' && !empty($json['GatewayPageURL'])) {
            return ['redirect' => $json['GatewayPageURL']];
        }
        return ['error' => 'Could not start SSLCommerz payment: ' . ($json['failedreason'] ?? 'gateway error')];
    }

    /**
     * Validate a transaction against SSLCommerz (called from success + IPN).
     * @return array ['valid'=>bool, 'tran_id'=>?string, 'amount'=>float, 'raw'=>array]
     */
    public function validate($val_id)
    {
        $store_id     = trim((string) $this->cfg('store_id'));
        $store_passwd = trim((string) $this->cfg('store_passwd'));
        $url = $this->base_url_api() . '/validator/api/validationserverAPI.php?'
            . http_build_query(['val_id' => $val_id, 'store_id' => $store_id, 'store_passwd' => $store_passwd, 'format' => 'json']);
        $res = $this->get($url);
        $json = json_decode((string) $res['body'], true);
        $status = is_array($json) ? ($json['status'] ?? '') : '';
        return [
            'valid'   => in_array($status, ['VALID', 'VALIDATED'], true),
            'tran_id' => is_array($json) ? ($json['tran_id'] ?? null) : null,
            'amount'  => is_array($json) ? (float) ($json['amount'] ?? 0) : 0.0,
            'raw'     => is_array($json) ? $json : [],
        ];
    }
}

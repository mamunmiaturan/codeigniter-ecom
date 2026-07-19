<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Payment
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Payment.php
 *
 * Gateway front controller (public). Handles the mock gateway hosted page and
 * the SSLCommerz return + IPN callbacks. Order-first flow: the order already
 * exists (payment_status=pending); these endpoints transition it to paid/failed.
 *
 * NOTE: payment/success, payment/fail, payment/cancel and payment/ipn receive
 * external POSTs from the gateway and are CSRF-exempt (see config.php
 * csrf_exclude_uris).
 */
class Payment extends Frontend_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['payment_model', 'order_model', 'landing_model']);
        $this->load->helper(['url', 'landing']);
    }

    // ---------------------------------------------------------------- mock gateway

    /**
     * The mock gateway is a QA harness, not a payment method: mock_pay() marks an
     * order paid with no verification whatsoever. Every mock endpoint therefore
     * fails closed unless the gateway is explicitly enabled AND we are outside
     * production.
     *
     * The registry's `is_active` alone is NOT a sufficient gate here: it is only
     * consulted by Payment_model::available_for_checkout() (the checkout selector),
     * and the config default is overridable by a payment_settings DB row.
     */
    private function _mock_available()
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            return false;
        }
        $methods = $this->payment_model->methods();
        return !empty($methods['mock']['is_active']);
    }

    public function mock($num = '')
    {
        if (!$this->_mock_available()) {
            show_404();
            return;
        }
        $order = $this->payment_model->order_by_number(rawurldecode($num));
        if (!$order) {
            show_404();
            return;
        }
        if ($order['payment_status'] === 'paid') {
            redirect(base_url('order/' . rawurlencode($order['order_number'])));
            return;
        }
        $this->load->view('landing/pages/pay_mock', ['order' => $order]);
    }

    public function mock_pay()
    {
        if (!$this->_mock_available()) {
            show_404();
            return;
        }
        $order = $this->payment_model->order_by_number((string) $this->input->post('order_number'));
        if ($order) {
            $this->payment_model->mark_paid($order['id'], 'mock', 'MOCK-' . $order['order_number'], (float) $order['total'], ['via' => 'mock']);
            set_alert('success', 'Payment successful! Your order is confirmed.');
            redirect(base_url('order/' . rawurlencode($order['order_number'])));
            return;
        }
        redirect(base_url('/'));
    }

    public function mock_cancel()
    {
        if (!$this->_mock_available()) {
            show_404();
            return;
        }
        $order = $this->payment_model->order_by_number((string) $this->input->post('order_number'));
        if ($order) {
            $this->payment_model->mark_failed($order['id'], 'mock', ['via' => 'mock', 'cancelled' => true]);
            set_alert('error', 'Payment was cancelled. Your order is awaiting payment.');
            redirect(base_url('order/' . rawurlencode($order['order_number'])));
            return;
        }
        redirect(base_url('/'));
    }

    // ---------------------------------------------------------------- SSLCommerz

    public function success()
    {
        $tran_id = $this->input->post('tran_id') ?: $this->input->post('value_a');
        $val_id  = $this->input->post('val_id');
        $order   = $tran_id ? $this->payment_model->order_by_number($tran_id) : null;
        if (!$order) {
            redirect(base_url('/'));
            return;
        }
        $ok = false;
        $gw = $this->payment_model->gateway('sslcommerz');
        if ($gw && method_exists($gw, 'validate') && $val_id) {
            $v = $gw->validate($val_id);
            if ($v['valid']
                && (string) $v['tran_id'] === (string) $order['order_number']
                && $v['amount'] + 0.01 >= (float) $order['total']) {
                $this->payment_model->mark_paid($order['id'], 'sslcommerz', $v['tran_id'], $v['amount'], $v['raw']);
                $ok = true;
            }
        }
        set_alert($ok ? 'success' : 'error', $ok ? 'Payment successful! Your order is confirmed.' : 'Payment could not be verified. Please contact support.');
        redirect(base_url('order/' . rawurlencode($order['order_number'])));
    }

    public function fail()
    {
        $this->_terminal('failed', 'Payment failed. Your order is awaiting payment.');
    }

    public function cancel()
    {
        $this->_terminal('cancelled', 'Payment was cancelled. Your order is awaiting payment.');
    }

    private function _terminal($kind, $message)
    {
        $tran_id = $this->input->post('tran_id') ?: $this->input->post('value_a');
        $order   = $tran_id ? $this->payment_model->order_by_number($tran_id) : null;
        if ($order) {
            $this->payment_model->mark_failed($order['id'], 'sslcommerz', ['kind' => $kind]);
            set_alert('error', $message);
            redirect(base_url('order/' . rawurlencode($order['order_number'])));
            return;
        }
        redirect(base_url('/'));
    }

    /**
     * Server-to-server IPN (independent confirmation). CSRF-exempt.
     */
    public function ipn()
    {
        $tran_id = $this->input->post('tran_id');
        $val_id  = $this->input->post('val_id');
        $status  = $this->input->post('status');
        $order   = $tran_id ? $this->payment_model->order_by_number($tran_id) : null;

        if ($order && in_array($status, ['VALID', 'VALIDATED'], true) && $val_id) {
            $gw = $this->payment_model->gateway('sslcommerz');
            if ($gw && method_exists($gw, 'validate')) {
                $v = $gw->validate($val_id);
                if ($v['valid'] && (string) $v['tran_id'] === (string) $order['order_number']) {
                    $this->payment_model->mark_paid($order['id'], 'sslcommerz', $v['tran_id'], $v['amount'], $v['raw']);
                    echo 'OK';
                    return;
                }
            }
        }
        echo 'IGNORED';
    }
}

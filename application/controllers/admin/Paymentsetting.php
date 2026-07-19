<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Payment
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Paymentsetting.php
 *
 * Settings editor over the payment method registry (config/payment_methods.php
 * merged with `payment_settings` overrides). This is NOT a DataGrid CRUD — a
 * single form lists every registered method and saves them all at once.
 * Permission module prefix: `payment`.
 */
class Paymentsetting extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('payment_model');
    }

    /**
     * GET  -> render the settings form for every registered payment method.
     * POST -> persist every method via payment_model->save_setting().
     */
    public function index()
    {
        if (!get_permission('payment_method', 'is_view')) {
            access_denied();
        }

        if ($this->input->post('save_payment_settings')) {
            if (!get_permission('payment_method', 'is_edit')) {
                access_denied();
            }
            $this->_save_settings();
            $this->log_activity('update', 'payment', 0, 'Updated payment settings');
            set_alert('success', translate('the_configuration_has_been_updated') ?: 'The configuration has been updated.');
            redirect(base_url('payment-settings'));
            return;
        }

        $this->data['methods']   = $this->payment_model->methods();
        $this->data['title']     = translate('payment_settings') ?: 'Payment Settings';
        $this->data['sub_page']  = 'payment/settings';
        $this->data['main_menu'] = 'payment';
        $this->load->view('layout/index', $this->data);
    }

    /** Payment transaction history — audit of every gateway transition. */
    public function transactions()
    {
        if (!get_permission('payment_transaction', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('payment_transactions') ?: 'Payment Transactions';
        $this->data['sub_page']  = 'payment/transactions';
        $this->data['main_menu'] = 'payment';
        $this->load->view('layout/index', $this->data);
    }

    public function get_transactions_server_side()
    {
        if (!get_permission('payment_transaction', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $total = $this->payment_model->transactions_count($status);
        $res   = $this->payment_model->transactions_datatable($search, $start, $length, $status);
        $sym   = get_global_setting('currency_symbol') ?: '৳';
        $badge = ['paid' => 'success', 'success' => 'success', 'failed' => 'danger', 'pending' => 'warning', 'refunded' => 'info', 'cancelled' => 'secondary'];

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $r) {
            $cls = $badge[strtolower($r->status)] ?? 'secondary';
            $order_cell = $r->order_number
                ? '<a href="' . base_url('order/view/' . encrypt_id($r->order_id)) . '"><strong>' . html_escape($r->order_number) . '</strong></a>'
                : ('#' . (int) $r->order_id);
            $data[] = [
                $i++,
                time_ago($r->created_at),
                $order_cell,
                html_escape($r->customer_name ?: '—'),
                '<span class="badge badge-info">' . html_escape(strtoupper($r->gateway)) . '</span>',
                html_escape($r->transaction_id ?: '—'),
                html_escape($sym) . ' ' . number_format((float) $r->amount, 2),
                '<span class="badge badge-' . $cls . '">' . html_escape(ucfirst($r->status)) . '</span>',
            ];
        }
        return $this->jsonResponse([
            'draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $res['filtered'],
            'data' => $data, 'csrfHash' => $this->security->get_csrf_hash(),
        ]);
    }

    // ---- helpers ----

    /**
     * Loop every method from the registry and save its posted override. The
     * editable config block is only assembled for the sslcommerz gateway.
     */
    private function _save_settings()
    {
        $methods = $this->payment_model->methods();

        // Checkboxes only post when checked; text/number fields always post.
        $active = (array) $this->input->post('is_active');
        $titles = (array) $this->input->post('title');
        $sorts  = (array) $this->input->post('sort_order');

        foreach ($methods as $code => $meta) {
            $data = [
                'is_active'  => !empty($active[$code]),
                'title'      => isset($titles[$code]) ? trim((string) $titles[$code]) : ($meta['title'] ?? ''),
                'sort_order' => isset($sorts[$code]) ? (int) $sorts[$code] : (int) ($meta['sort'] ?? 0),
            ];

            // Only SSLCommerz exposes editable credentials.
            if ($code === 'sslcommerz') {
                $data['config'] = [
                    'sandbox'      => $this->input->post('ssl_sandbox') ? true : false,
                    'store_id'     => trim((string) $this->input->post('ssl_store_id')),
                    'store_passwd' => trim((string) $this->input->post('ssl_store_passwd')),
                ];
            }

            $this->payment_model->save_setting($code, $data);
        }
    }
}

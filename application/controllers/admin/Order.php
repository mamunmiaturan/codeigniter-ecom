<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Orders (OMS)
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Order.php
 *
 * Admin order management: list, detail, and status transitions.
 * Permission module prefix: `order`.
 */
class Order extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['order_model', 'sales_model', 'return_model', 'email_model', 'sms_model']);
    }

    public function index()
    {
        if (!get_permission('order', 'is_view')) {
            access_denied();
        }
        $this->data['statuses']  = Order_model::STATUSES;
        $this->data['title']     = translate('orders') ?: 'Orders';
        $this->data['sub_page']  = 'order/index';
        $this->data['main_menu'] = 'orders';
        $this->load->view('layout/index', $this->data);
    }

    public function view($hash = '')
    {
        if (!get_permission('order', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $order = $this->order_model->find_order($id);
        if (empty($order)) {
            show_404();
            return;
        }
        $this->data['order']     = $order;
        $this->data['items']     = $this->order_model->get_items($id);
        $this->data['history']   = $this->order_model->get_history($id);
        $this->data['statuses']  = Order_model::STATUSES;
        $this->data['invoice']   = $this->sales_model->get_invoice($id);
        $this->data['shipments'] = $this->sales_model->get_shipments($id);
        $this->data['refunds']   = $this->sales_model->get_refunds($id);
        $this->data['returns']   = $this->return_model->get_for_order($id);
        $this->data['title']     = translate('order') . ' #' . $order['order_number'];
        $this->data['sub_page']  = 'order/view';
        $this->data['main_menu'] = 'orders';
        $this->load->view('layout/index', $this->data);
    }

    // ---------------------------------------------------------------- ops

    public function generate_invoice($hash = '')
    {
        if (!get_permission('order', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id || !$this->order_model->find_order($id)) {
            show_404();
            return;
        }
        $this->sales_model->create_invoice($id, (int) get_loggedin_user_id());
        $this->log_activity('create', 'order', $id, 'Generated invoice');
        redirect(base_url('order/print_invoice/' . encrypt_id($id)));
    }

    /** Print-friendly invoice (standalone page). */
    public function print_invoice($hash = '')
    {
        if (!get_permission('order', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $order = $id ? $this->order_model->find_order($id) : null;
        if (!$order) {
            show_404();
            return;
        }
        $this->load->view('order/invoice', [
            'order'   => $order,
            'items'   => $this->order_model->get_items($id),
            'invoice' => $this->sales_model->create_invoice($id, (int) get_loggedin_user_id()),
        ]);
    }

    /** Print-friendly packing slip (products + quantities, no prices). */
    public function print_packing_slip($hash = '')
    {
        if (!get_permission('order', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $order = $id ? $this->order_model->find_order($id) : null;
        if (!$order) {
            show_404();
            return;
        }
        $this->load->view('order/packing_slip', [
            'order' => $order,
            'items' => $this->order_model->get_items($id),
        ]);
    }

    /** Print-friendly shipping label (address block + COD amount + carrier). */
    public function print_shipping_label($hash = '')
    {
        if (!get_permission('order', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $order = $id ? $this->order_model->find_order($id) : null;
        if (!$order) {
            show_404();
            return;
        }
        $this->load->view('order/shipping_label', [
            'order'    => $order,
            'shipment' => $this->sales_model->latest_shipment($id),
        ]);
    }

    public function add_shipment()
    {
        if (!get_permission('order', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('order_id'));
        if (!$id || !$this->order_model->find_order($id)) {
            show_404();
            return;
        }
        $carrier = trim((string) $this->input->post('carrier'));
        if ($carrier === '') {
            set_alert('error', translate('carrier_is_required') ?: 'Carrier is required.');
            redirect(base_url('order/view/' . encrypt_id($id)));
            return;
        }
        $this->sales_model->create_shipment($id, [
            'carrier'         => $carrier,
            'tracking_number' => $this->input->post('tracking_number'),
            'tracking_url'    => $this->input->post('tracking_url'),
            'note'            => $this->input->post('note'),
        ], (int) get_loggedin_user_id());
        $this->log_activity('update', 'order', $id, 'Added shipment via ' . $carrier);
        // create_shipment() transitions the order to "shipped" — notify the customer.
        $order = $this->order_model->find_order($id);
        if (!empty($order)) {
            $tracking = trim((string) $this->input->post('tracking_number'));
            $note = 'Shipped via ' . $carrier . ($tracking !== '' ? ' — tracking ' . $tracking : '');
            $this->_notify_status_change($order, 'shipped', $note);
        }
        set_alert('success', translate('information_has_been_saved_successfully'));
        redirect(base_url('order/view/' . encrypt_id($id)));
    }

    public function add_refund()
    {
        if (!get_permission('order', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('order_id'));
        $order = $id ? $this->order_model->find_order($id) : null;
        if (!$order) {
            show_404();
            return;
        }
        $amount = (float) $this->input->post('amount');
        $already = $this->sales_model->refunded_total($id);
        if ($amount <= 0) {
            set_alert('error', translate('enter_a_valid_amount') ?: 'Enter a valid refund amount.');
            redirect(base_url('order/view/' . encrypt_id($id)));
            return;
        }
        if ($already + $amount > (float) $order['total'] + 0.01) {
            set_alert('error', translate('refund_exceeds_order_total') ?: 'Refund exceeds the order total.');
            redirect(base_url('order/view/' . encrypt_id($id)));
            return;
        }
        $this->sales_model->create_refund($id, [
            'amount' => $amount,
            'reason' => $this->input->post('reason'),
            'note'   => $this->input->post('note'),
        ], (int) get_loggedin_user_id());
        $this->log_activity('update', 'order', $id, 'Refunded ' . number_format($amount, 2));
        set_alert('success', translate('information_has_been_saved_successfully'));
        redirect(base_url('order/view/' . encrypt_id($id)));
    }

    public function update_status()
    {
        if (!get_permission('order', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('order_id'));
        if (!$id) {
            show_404();
            return;
        }
        $status = (string) $this->input->post('status');
        $note   = trim((string) $this->input->post('note')) ?: null;

        if (!in_array($status, Order_model::STATUSES, true)) {
            set_alert('error', translate('information_could_not_be_saved'));
            redirect(base_url('order/view/' . encrypt_id($id)));
            return;
        }

        if ($this->order_model->update_status($id, $status, $note, (int) get_loggedin_user_id())) {
            $this->log_activity('update', 'order', $id, 'Order status → ' . $status);
            $order = $this->order_model->find_order($id);
            if (!empty($order)) {
                $this->_notify_status_change($order, $status, $note);
            }
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('order/view/' . encrypt_id($id)));
    }

    /**
     * Best-effort customer notification (email + SMS) when an order status
     * changes. Reads the snapshot contact columns on the order row so it also
     * covers guest orders. Never throws: a delivery failure must not block the
     * status update itself. Toggle off by setting the `order_status_notify`
     * global setting to '0'.
     */
    private function _notify_status_change(array $order, $status, $note = null)
    {
        if ((string) get_global_setting('order_status_notify') === '0') {
            return;
        }
        $site   = get_global_setting('site_name') ?: 'Our Store';
        $name   = $order['customer_name'] ?: 'Customer';
        $number = $order['order_number'];
        $nice   = ucfirst((string) $status);

        // Email — only if a valid address was captured on the order.
        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $subject = 'Order ' . $number . ' update: ' . $nice;
                $body  = '<p>Hi ' . html_escape($name) . ',</p>';
                $body .= '<p>Your order <strong>' . html_escape($number) . '</strong> status has been updated to <strong>' . html_escape($nice) . '</strong>.</p>';
                if (!empty($note)) {
                    $body .= '<p><em>' . html_escape($note) . '</em></p>';
                }
                $body .= '<p>Thank you for shopping with ' . html_escape($site) . '.</p>';
                $this->email_model->sendMail($email, $subject, $body);
            } catch (\Throwable $e) {
                log_message('error', 'Order status email failed: ' . $e->getMessage());
            }
        }

        // SMS — only if a phone number was captured.
        $phone = trim((string) ($order['customer_phone'] ?? ''));
        if ($phone !== '') {
            try {
                $msg = 'Hi ' . $name . ', your order ' . $number . ' is now ' . $nice . '. - ' . $site;
                $uid = isset($order['user_id']) ? (int) $order['user_id'] : null;
                $this->sms_model->send($phone, $msg, $uid, 'Order Status');
            } catch (\Throwable $e) {
                log_message('error', 'Order status SMS failed: ' . $e->getMessage());
            }
        }
    }

    public function get_orders_server_side()
    {
        if (!get_permission('order', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [
            0 => 'id',
            1 => 'order_number',
            2 => 'customer_name',
            4 => 'total',
            5 => 'payment_method',
            6 => 'status',
            7 => 'created_at',
        ];
        $order_idx = $this->input->post('order')[0]['column'] ?? 7;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'created_at';

        $total = $this->order_model->count_all($status);
        $res   = $this->order_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $symbol = get_global_setting('currency_symbol') ?: '৳';

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $data[] = [
                $i++,
                '<a href="' . base_url('order/view/' . encrypt_id($row->id)) . '"><strong>' . html_escape($row->order_number) . '</strong></a>',
                html_escape($row->customer_name) . '<br><small class="text-muted">' . html_escape($row->customer_phone) . '</small>',
                (int) $row->item_count,
                html_escape($symbol) . ' ' . number_format((float) $row->total, 2),
                '<span class="badge badge-' . ($row->payment_method === 'cod' ? 'secondary' : 'info') . '">' . strtoupper($row->payment_method) . '</span>',
                $this->_status_badge($row->status),
                time_ago($row->created_at),
                '<a href="' . base_url('order/view/' . encrypt_id($row->id)) . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . (translate('view') ?: 'View') . '"><i class="far fa-eye"></i></a>',
            ];
        }

        return $this->jsonResponse([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $res['filtered'],
            'data'            => $data,
            'csrfHash'        => $this->security->get_csrf_hash(),
        ]);
    }

    private function _status_badge($status)
    {
        $map = [
            'pending'    => 'warning',
            'confirmed'  => 'info',
            'processing' => 'primary',
            'shipped'    => 'primary',
            'delivered'  => 'success',
            'completed'  => 'success',
            'cancelled'  => 'danger',
            'returned'   => 'danger',
        ];
        $cls = $map[$status] ?? 'secondary';
        return '<span class="badge badge-' . $cls . '">' . ucfirst($status) . '</span>';
    }

    /** Export all orders as a downloadable CSV. */
    public function export()
    {
        if (!get_permission('order', 'is_view')) {
            access_denied();
        }
        $rows = $this->db->select('order_number, customer_name, customer_phone, customer_email, item_count, subtotal, discount, shipping_charge, tax, total, payment_method, payment_status, status, created_at')
            ->order_by('id', 'DESC')->get('orders')->result_array();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order', 'Customer', 'Phone', 'Email', 'Items', 'Subtotal', 'Discount', 'Shipping', 'Tax', 'Total', 'Payment', 'Payment Status', 'Status', 'Date']);
        foreach ($rows as $r) {
            fputcsv($out, array_values($r));
        }
        fclose($out);
        exit;
    }
}

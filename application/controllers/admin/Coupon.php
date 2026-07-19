<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Coupon.php
 *
 * Admin CRUD for discount coupons. Permission module prefix: `coupon`.
 */
class Coupon extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('coupon_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('coupon', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('coupons') ?: 'Coupons';
        $this->data['sub_page']  = 'catalog/coupon/index';
        $this->data['main_menu'] = 'coupons';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('coupon', 'is_add')) {
            access_denied();
        }
        $this->data['coupon']    = null;
        $this->data['title']     = translate('add_coupon') ?: 'Add Coupon';
        $this->data['sub_page']  = 'catalog/coupon/form';
        $this->data['main_menu'] = 'coupons';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('coupon', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('coupon'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $data = $this->_collect();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->coupon_model->insert($data);
        if ($id) {
            $this->log_activity('create', 'coupon', $id, 'Created coupon: ' . $data['code']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('coupon'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('coupon/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('coupon', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $coupon = $this->coupon_model->find($id);
        if (empty($coupon)) {
            show_404();
            return;
        }
        $this->data['coupon']    = $coupon;
        $this->data['title']     = translate('edit_coupon') ?: 'Edit Coupon';
        $this->data['sub_page']  = 'catalog/coupon/form';
        $this->data['main_menu'] = 'coupons';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('coupon', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('coupon'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            show_404();
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->edit($this->input->post('id'));
            return;
        }
        $data = $this->_collect();
        $data['updated_by'] = get_loggedin_user_id();
        if ($this->coupon_model->update($id, $data)) {
            $this->log_activity('update', 'coupon', $id, 'Updated coupon: ' . $data['code']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('coupon'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('coupon', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->coupon_model->delete($id)) {
            $this->log_activity('delete', 'coupon', $id, 'Deleted coupon');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('coupon'));
    }

    public function status()
    {
        if (!get_permission('coupon', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->coupon_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'coupon', $id, 'Toggled coupon status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function unique_code($code)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->coupon_model->unique_code($code, $ignore)) {
            $this->form_validation->set_message('unique_code', translate('coupon_code_already_exists') ?: 'This coupon code already exists.');
            return false;
        }
        return true;
    }

    public function get_coupons_server_side()
    {
        if (!get_permission('coupon', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [1 => 'code', 2 => 'type', 3 => 'value', 4 => 'min_order_amount', 5 => 'used_count', 6 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'code';

        $total = $this->coupon_model->count_all($status);
        $res   = $this->coupon_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('coupon', 'is_edit');
        $can_delete = get_permission('coupon', 'is_delete');
        $sym        = get_global_setting('currency_symbol') ?: '৳';

        $type_labels = ['percentage' => 'Percentage', 'fixed' => 'Fixed', 'free_shipping' => 'Free Shipping'];

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            if ($row->type === 'percentage') {
                $val = rtrim(rtrim(number_format((float) $row->value, 2), '0'), '.') . '%';
            } elseif ($row->type === 'fixed') {
                $val = html_escape($sym) . ' ' . number_format((float) $row->value, 2);
            } else {
                $val = '—';
            }
            $used = (int) $row->used_count . ($row->usage_limit !== null ? ' / ' . (int) $row->usage_limit : '');

            $data[] = [
                $i++,
                '<strong>' . html_escape($row->code) . '</strong>' . ($row->description ? '<br><small class="text-muted">' . html_escape($row->description) . '</small>' : ''),
                '<span class="badge badge-info">' . ($type_labels[$row->type] ?? $row->type) . '</span>',
                $val,
                html_escape($sym) . ' ' . number_format((float) $row->min_order_amount, 2),
                $used,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('coupon', $row->id, $can_edit, $can_delete),
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

    // ---- helpers ----

    private function _set_rules()
    {
        $this->form_validation->set_rules('code', 'Code', 'trim|required|max_length[50]|callback_unique_code');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|in_list[percentage,fixed,free_shipping]');
        $this->form_validation->set_rules('value', 'Value', 'trim|numeric');
        $this->form_validation->set_rules('min_order_amount', 'Min Order', 'trim|numeric');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
    }

    private function _collect()
    {
        return [
            'code'                 => strtoupper(trim((string) $this->input->post('code'))),
            'description'          => $this->input->post('description'),
            'type'                 => $this->input->post('type'),
            'value'                => (float) $this->input->post('value'),
            'min_order_amount'     => (float) $this->input->post('min_order_amount'),
            'max_discount_amount'  => $this->_ndec($this->input->post('max_discount_amount')),
            'usage_limit'          => $this->_nint($this->input->post('usage_limit')),
            'usage_limit_per_user' => $this->_nint($this->input->post('usage_limit_per_user')),
            'starts_at'            => $this->_ndate($this->input->post('starts_at'), false),
            'expires_at'           => $this->_ndate($this->input->post('expires_at'), true),
            'status'               => $this->input->post('status') ?: 'Active',
        ];
    }

    private function _ndec($v)
    {
        $v = trim((string) $v);
        return ($v === '' || !is_numeric($v)) ? null : (float) $v;
    }

    private function _nint($v)
    {
        $v = trim((string) $v);
        return ($v === '' || !ctype_digit($v)) ? null : (int) $v;
    }

    private function _ndate($v, $end_of_day)
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        // Date-only input -> pad the time so day-level windows behave intuitively.
        if (strlen($v) === 10) {
            $v .= $end_of_day ? ' 23:59:59' : ' 00:00:00';
        }
        $ts = strtotime($v);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}

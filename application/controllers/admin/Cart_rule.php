<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Cart_rule.php
 *
 * Admin CRUD for auto-applied cart price rules. Permission module prefix: `cart_rule`.
 */
class Cart_rule extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cart_rule_model');
        $this->load->model('category_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('cart_rule', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('cart_price_rules') ?: 'Cart Price Rules';
        $this->data['sub_page']  = 'promotion/cart_rule_index';
        $this->data['main_menu'] = 'promotions';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('cart_rule', 'is_add')) {
            access_denied();
        }
        $this->_form_data(null);
        $this->data['title']     = translate('add_cart_rule') ?: 'Add Cart Price Rule';
        $this->data['sub_page']  = 'promotion/cart_rule_form';
        $this->data['main_menu'] = 'promotions';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('cart_rule', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('cart_rule'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $data = $this->_collect();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->cart_rule_model->insert($data);
        if ($id) {
            $this->log_activity('create', 'cart_rule', $id, 'Created cart price rule: ' . $data['name']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('cart_rule'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('cart_rule/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('cart_rule', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $cart_rule = $this->cart_rule_model->find($id);
        if (empty($cart_rule)) {
            show_404();
            return;
        }
        $this->_form_data($cart_rule);
        $this->data['title']     = translate('edit_cart_rule') ?: 'Edit Cart Price Rule';
        $this->data['sub_page']  = 'promotion/cart_rule_form';
        $this->data['main_menu'] = 'promotions';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('cart_rule', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('cart_rule'));
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
        if ($this->cart_rule_model->update($id, $data)) {
            $this->log_activity('update', 'cart_rule', $id, 'Updated cart price rule: ' . $data['name']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('cart_rule'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('cart_rule', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->cart_rule_model->delete($id)) {
            $this->log_activity('delete', 'cart_rule', $id, 'Deleted cart price rule');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('cart_rule'));
    }

    public function status()
    {
        if (!get_permission('cart_rule', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->cart_rule_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'cart_rule', $id, 'Toggled cart price rule status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    public function get_cart_rules_server_side()
    {
        if (!get_permission('cart_rule', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [1 => 'name', 2 => 'action_type', 3 => 'discount_value', 4 => 'min_subtotal', 5 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'name';

        $total = $this->cart_rule_model->count_all($status);
        $res   = $this->cart_rule_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('cart_rule', 'is_edit');
        $can_delete = get_permission('cart_rule', 'is_delete');
        $sym        = get_global_setting('currency_symbol') ?: '৳';

        $action_labels = [
            'percentage'    => 'Percentage',
            'fixed'         => 'Fixed',
            'free_shipping' => 'Free Shipping',
        ];

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            if ($row->action_type === 'percentage') {
                $disc = rtrim(rtrim(number_format((float) $row->discount_value, 2), '0'), '.') . '%';
            } elseif ($row->action_type === 'fixed') {
                $disc = html_escape($sym) . ' ' . number_format((float) $row->discount_value, 2);
            } else {
                $disc = '—';
            }
            if ((int) $row->free_shipping === 1 && $row->action_type !== 'free_shipping') {
                $disc .= ' <span class="badge badge-info">' . (translate('free_shipping') ?: 'Free Shipping') . '</span>';
            }

            $data[] = [
                $i++,
                '<strong>' . html_escape($row->name) . '</strong>' . ($row->description ? '<br><small class="text-muted">' . html_escape($row->description) . '</small>' : ''),
                '<span class="badge badge-info">' . ($action_labels[$row->action_type] ?? $row->action_type) . '</span>',
                $disc,
                html_escape($sym) . ' ' . number_format((float) $row->min_subtotal, 2),
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('cart_rule', $row->id, $can_edit, $can_delete),
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

    private function _form_data($cart_rule)
    {
        $this->load->model('customer_group_model');
        $this->data['cart_rule']  = $cart_rule;
        $this->data['categories'] = $this->category_model->get_dropdown();
        $this->data['groups']     = $this->customer_group_model->get_dropdown();
    }

    private function _set_rules()
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[150]');
        $this->form_validation->set_rules('action_type', 'Action Type', 'trim|required|in_list[percentage,fixed,free_shipping]');
        $this->form_validation->set_rules('discount_value', 'Discount Value', 'trim|numeric');
        $this->form_validation->set_rules('min_subtotal', 'Min Subtotal', 'trim|numeric');
        $this->form_validation->set_rules('sort_order', 'Sort Order', 'trim|is_natural');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
    }

    private function _collect()
    {
        return [
            'name'                 => trim((string) $this->input->post('name')),
            'description'          => $this->input->post('description'),
            'status'               => $this->input->post('status') ?: 'Active',
            'sort_order'           => (int) $this->input->post('sort_order'),
            'starts_at'            => $this->_ndate($this->input->post('starts_at'), false),
            'ends_at'              => $this->_ndate($this->input->post('ends_at'), true),
            'min_subtotal'         => (float) $this->input->post('min_subtotal'),
            'category_id'          => $this->_nint($this->input->post('category_id')),
            'customer_group_id'    => $this->_nint($this->input->post('customer_group_id')),
            'action_type'          => $this->input->post('action_type') ?: 'percentage',
            'discount_value'       => (float) $this->input->post('discount_value'),
            'max_discount'         => $this->_ndec($this->input->post('max_discount')),
            'free_shipping'        => $this->input->post('free_shipping') ? 1 : 0,
            'usage_limit'          => $this->_nint($this->input->post('usage_limit')),
            'usage_limit_per_user' => $this->_nint($this->input->post('usage_limit_per_user')),
            'end_other_rules'      => $this->input->post('end_other_rules') ? 1 : 0,
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

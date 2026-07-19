<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customers
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Customer_group.php
 *
 * Admin CRUD for customer groups. Permission module prefix: `customer_group`.
 * A group carries an optional automatic discount_percent applied to its members
 * through a group-scoped cart rule. Exactly one group may be flagged default.
 */
class Customer_group extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('customer_group_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('customer_group', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('customer_groups') ?: 'Customer Groups';
        $this->data['sub_page']  = 'customer_group/index';
        $this->data['main_menu'] = 'customers';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('customer_group', 'is_add')) {
            access_denied();
        }
        $this->data['group']     = null;
        $this->data['title']     = translate('add_customer_group') ?: 'Add Customer Group';
        $this->data['sub_page']  = 'customer_group/form';
        $this->data['main_menu'] = 'customers';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('customer_group', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('customer_group'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $data = $this->_collect();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->customer_group_model->insert($data);
        if ($id) {
            if (!empty($data['is_default'])) {
                $this->customer_group_model->clear_other_defaults($id);
            }
            $this->log_activity('create', 'customer_group', $id, 'Created customer group: ' . $data['name']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('customer_group'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('customer_group/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('customer_group', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $group = $this->customer_group_model->find($id);
        if (empty($group)) {
            show_404();
            return;
        }
        $this->data['group']     = $group;
        $this->data['title']     = translate('edit_customer_group') ?: 'Edit Customer Group';
        $this->data['sub_page']  = 'customer_group/form';
        $this->data['main_menu'] = 'customers';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('customer_group', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('customer_group'));
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
        if ($this->customer_group_model->update($id, $data)) {
            if (!empty($data['is_default'])) {
                $this->customer_group_model->clear_other_defaults($id);
            }
            $this->log_activity('update', 'customer_group', $id, 'Updated customer group: ' . $data['name']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('customer_group'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('customer_group', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->customer_group_model->delete($id)) {
            $this->log_activity('delete', 'customer_group', $id, 'Deleted customer group');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('customer_group'));
    }

    public function status()
    {
        if (!get_permission('customer_group', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->customer_group_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'customer_group', $id, 'Toggled customer group status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function unique_code($code)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->customer_group_model->unique_code($code, $ignore)) {
            $this->form_validation->set_message('unique_code', translate('customer_group_code_already_exists') ?: 'This code already exists.');
            return false;
        }
        return true;
    }

    public function get_customer_groups_server_side()
    {
        if (!get_permission('customer_group', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [1 => 'name', 2 => 'code', 3 => 'discount_percent', 4 => 'is_default', 5 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'name';

        $total = $this->customer_group_model->count_all($status);
        $res   = $this->customer_group_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('customer_group', 'is_edit');
        $can_delete = get_permission('customer_group', 'is_delete');

        $default_label = translate('default') ?: 'Default';

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $pct = rtrim(rtrim(number_format((float) $row->discount_percent, 2), '0'), '.') . '%';
            $default_badge = !empty($row->is_default)
                ? '<span class="badge badge-primary">' . html_escape($default_label) . '</span>'
                : '<span class="text-muted">—</span>';

            $data[] = [
                $i++,
                '<strong>' . html_escape($row->name) . '</strong>',
                '<code>' . html_escape($row->code) . '</code>',
                $pct,
                $default_badge,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('customer_group', $row->id, $can_edit, $can_delete),
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
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('code', 'Code', 'trim|required|max_length[50]|callback_unique_code');
        $this->form_validation->set_rules('discount_percent', 'Discount %', 'trim|required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
    }

    private function _collect()
    {
        return [
            'name'             => trim((string) $this->input->post('name')),
            'code'             => trim((string) $this->input->post('code')),
            'discount_percent' => (float) $this->input->post('discount_percent'),
            'is_default'       => $this->input->post('is_default') ? 1 : 0,
            'status'           => $this->input->post('status') ?: 'Active',
        ];
    }
}

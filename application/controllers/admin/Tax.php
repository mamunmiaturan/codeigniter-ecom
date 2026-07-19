<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Tax
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Tax.php
 *
 * Admin CRUD for tax categories + tax rates (with category<->rate mapping).
 * Permission module prefix: `tax`.
 */
class Tax extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('tax_model');
        $this->load->helper('catalog');
    }

    // =====================================================================
    // Index (two DataTables: categories + rates)
    // =====================================================================
    public function index()
    {
        // Default landing for the Tax menu -> the Tax Category list.
        redirect(base_url('tax/categories'));
    }

    // Dedicated Tax Category list page (sidebar: Tax > Tax Category)
    public function categories()
    {
        if (!get_permission('tax_category', 'is_view')) {
            access_denied();
        }
        $this->data['tab']       = 'categories';
        $this->data['title']     = translate('tax_categories') ?: 'Tax Categories';
        $this->data['sub_page']  = 'tax/categories';
        $this->data['main_menu'] = 'tax';
        $this->load->view('layout/index', $this->data);
    }

    // Dedicated Tax Rates list page (sidebar: Tax > Tax Rates)
    public function rates()
    {
        if (!get_permission('tax_rate', 'is_view')) {
            access_denied();
        }
        $this->data['tab']       = 'rates';
        $this->data['title']     = translate('tax_rates') ?: 'Tax Rates';
        $this->data['sub_page']  = 'tax/rates';
        $this->data['main_menu'] = 'tax';
        $this->load->view('layout/index', $this->data);
    }

    public function get_categories_server_side()
    {
        if (!get_permission('tax_category', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';

        $columns_map = [1 => 'code', 2 => 'name', 3 => 'is_default', 5 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 2;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'name';

        $total = $this->tax_model->count_categories();
        $res   = $this->tax_model->categories_datatable($search, $start, $length, $order_col, $order_dir);

        $can_edit   = get_permission('tax_category', 'is_edit');
        $can_delete = get_permission('tax_category', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $default = $row->is_default
                ? '<span class="badge badge-primary">' . (translate('default') ?: 'Default') . '</span>'
                : '<span class="text-muted">&mdash;</span>';
            $rate_count = count($this->tax_model->rate_ids_for_category($row->id));

            $data[] = [
                $i++,
                '<strong>' . html_escape($row->code) . '</strong>',
                html_escape($row->name) . ($row->description ? '<br><small class="text-muted">' . html_escape($row->description) . '</small>' : ''),
                $default,
                '<span class="badge badge-info">' . $rate_count . '</span>',
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                $this->_row_actions('category', $row->id, $can_edit, $can_delete),
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

    public function get_rates_server_side()
    {
        if (!get_permission('tax_rate', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';

        $columns_map = [1 => 'identifier', 2 => 'name', 3 => 'country', 4 => 'state', 5 => 'postcode', 6 => 'rate', 7 => 'priority', 8 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'identifier';

        $total = $this->tax_model->count_rates();
        $res   = $this->tax_model->rates_datatable($search, $start, $length, $order_col, $order_dir);

        $can_edit   = get_permission('tax_rate', 'is_edit');
        $can_delete = get_permission('tax_rate', 'is_delete');
        $all        = translate('all') ?: 'All';

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $rate = rtrim(rtrim(number_format((float) $row->rate, 4, '.', ''), '0'), '.') . '%';

            $data[] = [
                $i++,
                '<strong>' . html_escape($row->identifier) . '</strong>',
                html_escape($row->name),
                html_escape($row->country),
                ($row->state === '*') ? '<span class="text-muted">' . $all . '</span>' : html_escape($row->state),
                ($row->postcode === '*') ? '<span class="text-muted">' . $all . '</span>' : html_escape($row->postcode),
                $rate,
                (int) $row->priority,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                $this->_row_actions('rate', $row->id, $can_edit, $can_delete),
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

    // =====================================================================
    // Category CRUD
    // =====================================================================
    public function category_create()
    {
        if (!get_permission('tax_category', 'is_add')) {
            access_denied();
        }
        $this->_category_form_data(null, []);
        $this->data['title']     = translate('add_tax_category') ?: 'Add Tax Category';
        $this->data['sub_page']  = 'tax/category_form';
        $this->data['main_menu'] = 'tax';
        $this->load->view('layout/index', $this->data);
    }

    public function category_store()
    {
        if (!get_permission('tax_category', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('tax'));
            return;
        }
        $this->_category_rules();
        if ($this->form_validation->run() === false) {
            $this->category_create();
            return;
        }
        $data = $this->_collect_category();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->tax_model->insert($data);
        if ($id) {
            if (!empty($data['is_default'])) {
                $this->tax_model->clear_other_defaults($id);
            }
            $this->tax_model->set_category_rates($id, $this->input->post('rate_ids'));
            $this->log_activity('create', 'tax', $id, 'Created tax category: ' . $data['code']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('tax'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('tax/category_create'));
    }

    public function category_edit($hash = '')
    {
        if (!get_permission('tax_category', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $category = $this->tax_model->find($id);
        if (empty($category)) {
            show_404();
            return;
        }
        $this->_category_form_data($category, $this->tax_model->rate_ids_for_category($id));
        $this->data['title']     = translate('edit_tax_category') ?: 'Edit Tax Category';
        $this->data['sub_page']  = 'tax/category_form';
        $this->data['main_menu'] = 'tax';
        $this->load->view('layout/index', $this->data);
    }

    public function category_update()
    {
        if (!get_permission('tax_category', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('tax'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            show_404();
            return;
        }
        $this->_category_rules();
        if ($this->form_validation->run() === false) {
            $this->category_edit($this->input->post('id'));
            return;
        }
        $data = $this->_collect_category();
        $data['updated_by'] = get_loggedin_user_id();
        if ($this->tax_model->update($id, $data)) {
            if (!empty($data['is_default'])) {
                $this->tax_model->clear_other_defaults($id);
            }
            $this->tax_model->set_category_rates($id, $this->input->post('rate_ids'));
            $this->log_activity('update', 'tax', $id, 'Updated tax category: ' . $data['code']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('tax'));
    }

    public function category_delete($hash = '')
    {
        if (!get_permission('tax_category', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->tax_model->delete($id)) {
            $this->log_activity('delete', 'tax', $id, 'Deleted tax category');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('tax'));
    }

    public function category_status()
    {
        if (!get_permission('tax_category', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->tax_model->toggle_category_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'tax', $id, 'Toggled tax category status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function unique_category_code($code)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->tax_model->unique_code($code, $ignore)) {
            $this->form_validation->set_message('unique_category_code', translate('tax_category_code_already_exists') ?: 'This tax category code already exists.');
            return false;
        }
        return true;
    }

    // =====================================================================
    // Rate CRUD
    // =====================================================================
    public function rate_create()
    {
        if (!get_permission('tax_rate', 'is_add')) {
            access_denied();
        }
        $this->data['rate']      = null;
        $this->data['title']     = translate('add_tax_rate') ?: 'Add Tax Rate';
        $this->data['sub_page']  = 'tax/rate_form';
        $this->data['main_menu'] = 'tax';
        $this->load->view('layout/index', $this->data);
    }

    public function rate_store()
    {
        if (!get_permission('tax_rate', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('tax'));
            return;
        }
        $this->_rate_rules();
        if ($this->form_validation->run() === false) {
            $this->rate_create();
            return;
        }
        $data = $this->_collect_rate();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->tax_model->insert_rate($data);
        if ($id) {
            $this->log_activity('create', 'tax', $id, 'Created tax rate: ' . $data['identifier']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('tax'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('tax/rate_create'));
    }

    public function rate_edit($hash = '')
    {
        if (!get_permission('tax_rate', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $rate = $this->tax_model->get_rate($id);
        if (empty($rate)) {
            show_404();
            return;
        }
        $this->data['rate']      = $rate;
        $this->data['title']     = translate('edit_tax_rate') ?: 'Edit Tax Rate';
        $this->data['sub_page']  = 'tax/rate_form';
        $this->data['main_menu'] = 'tax';
        $this->load->view('layout/index', $this->data);
    }

    public function rate_update()
    {
        if (!get_permission('tax_rate', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('tax'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            show_404();
            return;
        }
        $this->_rate_rules();
        if ($this->form_validation->run() === false) {
            $this->rate_edit($this->input->post('id'));
            return;
        }
        $data = $this->_collect_rate();
        $data['updated_by'] = get_loggedin_user_id();
        if ($this->tax_model->update_rate($id, $data)) {
            $this->log_activity('update', 'tax', $id, 'Updated tax rate: ' . $data['identifier']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('tax'));
    }

    public function rate_delete($hash = '')
    {
        if (!get_permission('tax_rate', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->tax_model->delete_rate($id)) {
            $this->log_activity('delete', 'tax', $id, 'Deleted tax rate');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('tax'));
    }

    public function rate_status()
    {
        if (!get_permission('tax_rate', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->tax_model->toggle_rate_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'tax', $id, 'Toggled tax rate status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function unique_rate_identifier($identifier)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->tax_model->unique_identifier($identifier, $ignore)) {
            $this->form_validation->set_message('unique_rate_identifier', translate('tax_rate_identifier_already_exists') ?: 'This tax rate identifier already exists.');
            return false;
        }
        return true;
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function _category_form_data($category, $selected_rate_ids)
    {
        $this->data['tax_category']       = $category;
        $this->data['rates_all']          = $this->tax_model->get_rates_all();
        $this->data['selected_rate_ids']  = $selected_rate_ids;
    }

    private function _category_rules()
    {
        $this->form_validation->set_rules('code', 'Code', 'trim|required|max_length[50]|callback_unique_category_code');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
    }

    private function _collect_category()
    {
        return [
            'code'        => strtoupper(trim((string) $this->input->post('code'))),
            'name'        => trim((string) $this->input->post('name')),
            'description' => ($this->input->post('description') !== '' ? $this->input->post('description') : null),
            'is_default'  => $this->input->post('is_default') ? 1 : 0,
            'status'      => $this->input->post('status') ?: 'Active',
        ];
    }

    private function _rate_rules()
    {
        $this->form_validation->set_rules('identifier', 'Identifier', 'trim|required|max_length[80]|callback_unique_rate_identifier');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('country', 'Country', 'trim|max_length[2]');
        $this->form_validation->set_rules('state', 'State', 'trim|max_length[100]');
        $this->form_validation->set_rules('postcode', 'Postcode', 'trim|max_length[30]');
        $this->form_validation->set_rules('rate', 'Rate', 'trim|required|numeric');
        $this->form_validation->set_rules('priority', 'Priority', 'trim|required|integer');
    }

    private function _collect_rate()
    {
        $country  = strtoupper(trim((string) $this->input->post('country')));
        $state    = trim((string) $this->input->post('state'));
        $postcode = trim((string) $this->input->post('postcode'));
        return [
            'identifier' => trim((string) $this->input->post('identifier')),
            'name'       => trim((string) $this->input->post('name')),
            'country'    => $country !== '' ? $country : 'BD',
            'state'      => $state !== '' ? $state : '*',
            'postcode'   => $postcode !== '' ? $postcode : '*',
            'rate'       => (float) $this->input->post('rate'),
            'priority'   => (int) $this->input->post('priority'),
            'status'     => $this->input->post('status') ?: 'Active',
        ];
    }

    /**
     * Edit + delete action buttons for a tax list row. The two entities use
     * distinct URL verbs (category_edit / rate_edit), so this can't reuse the
     * shared catalog_row_actions helper.
     */
    private function _row_actions($entity, $id, $can_edit, $can_delete)
    {
        $html = '';
        if ($can_edit) {
            $html .= '<a href="' . base_url('tax/' . $entity . '_edit/' . encrypt_id($id)) . '" '
                . 'class="btn btn-circle btn-default icon" data-toggle="tooltip" '
                . 'data-original-title="' . (translate('edit') ?: 'Edit') . '"><i class="fas fa-pen-nib"></i></a> ';
        }
        if ($can_delete) {
            $html .= btn_delete('tax/' . $entity . '_delete/' . encrypt_id($id));
        }
        return $html;
    }
}

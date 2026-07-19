<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV)
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Attributes.php
 *
 * Admin CRUD for EAV attribute definitions. Permission module prefix: `attribute`.
 * Each attribute carries a `type` (one of eav_types()) that decides how product
 * values are stored. Option-based types (select/multiselect/checkbox) additionally
 * own a set of attribute_options, synced through Attribute_model::save_options().
 * System attributes (is_user_defined = 0) are protected from deletion.
 *
 * NOTE: named `Attributes` (plural) because `Attribute` is a PHP 8 core class
 * (the #[Attribute] marker) — declaring it fatals with "Cannot redeclare class
 * Attribute". Routes map the original `attribute/...` URLs to this controller,
 * so all links keep working.
 */
class Attributes extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attribute_model');
        $this->load->helper('catalog');
        $this->load->helper('eav');
    }

    public function index()
    {
        if (!get_permission('attribute', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('attributes') ?: 'Attributes';
        $this->data['sub_page']  = 'catalog/attribute/index';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('attribute', 'is_add')) {
            access_denied();
        }
        $this->data['attribute'] = null;
        $this->data['options']   = [];
        $this->data['title']     = translate('add_attribute') ?: 'Add Attribute';
        $this->data['sub_page']  = 'catalog/attribute/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('attribute', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('attribute'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $data = $this->_collect();
        $data['is_user_defined'] = 1;
        $data['created_by']      = get_loggedin_user_id();
        $id = $this->attribute_model->insert($data);
        if ($id) {
            $options = eav_is_option_type($data['type']) ? $this->_collect_options() : [];
            $this->attribute_model->save_options($id, $options);
            $this->log_activity('create', 'attribute', $id, 'Created attribute: ' . $data['admin_name']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('attribute'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('attribute/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('attribute', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $attribute = $this->attribute_model->find($id);
        if (empty($attribute)) {
            show_404();
            return;
        }
        $this->data['attribute'] = $attribute;
        $this->data['options']   = $this->attribute_model->get_options($id);
        $this->data['title']     = translate('edit_attribute') ?: 'Edit Attribute';
        $this->data['sub_page']  = 'catalog/attribute/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('attribute', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('attribute'));
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
        if ($this->attribute_model->update($id, $data)) {
            $options = eav_is_option_type($data['type']) ? $this->_collect_options() : [];
            $this->attribute_model->save_options($id, $options);
            $this->log_activity('update', 'attribute', $id, 'Updated attribute: ' . $data['admin_name']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('attribute'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('attribute', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $attribute = $this->attribute_model->find($id);
        if (empty($attribute)) {
            show_404();
            return;
        }
        if (empty($attribute['is_user_defined'])) {
            set_alert('error', translate('system_attribute_cannot_be_deleted') ?: 'System attributes cannot be deleted.');
            redirect(base_url('attribute'));
            return;
        }
        if ($this->attribute_model->delete($id)) {
            $this->log_activity('delete', 'attribute', $id, 'Deleted attribute');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('attribute'));
    }

    public function status()
    {
        if (!get_permission('attribute', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->attribute_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'attribute', $id, 'Toggled attribute status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    public function unique_code($code)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->attribute_model->unique_code($code, $ignore)) {
            $this->form_validation->set_message('unique_code', translate('attribute_code_already_exists') ?: 'This code already exists.');
            return false;
        }
        return true;
    }

    public function get_attributes_server_side()
    {
        if (!get_permission('attribute', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [1 => 'admin_name', 2 => 'type', 4 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'admin_name';

        $total = $this->attribute_model->count_all($status);
        $res   = $this->attribute_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('attribute', 'is_edit');
        $can_delete = get_permission('attribute', 'is_delete');

        $yes_label = translate('yes') ?: 'Yes';

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $name_html = '<strong>' . html_escape($row->admin_name) . '</strong>'
                . '<br><small class="text-muted"><code>' . html_escape($row->code) . '</code></small>';

            $type_html = '<span class="badge badge-secondary">' . html_escape(ucfirst($row->type)) . '</span>';

            $filterable_html = !empty($row->is_filterable)
                ? '<span class="badge badge-success">' . html_escape($yes_label) . '</span>'
                : '<span class="text-muted">—</span>';

            // System attributes may not be deleted.
            $row_can_delete = $can_delete && !empty($row->is_user_defined);

            $data[] = [
                $i++,
                $name_html,
                $type_html,
                $filterable_html,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('attribute', $row->id, $can_edit, $row_can_delete),
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
        $this->form_validation->set_rules('code', 'Code', 'trim|required|max_length[100]|regex_match[/^[a-z0-9_]+$/]|callback_unique_code');
        $this->form_validation->set_rules('admin_name', 'Admin Name', 'trim|required|max_length[150]');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[150]');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|in_list[' . implode(',', eav_types()) . ']');
        $this->form_validation->set_rules('position', 'Position', 'trim|is_natural');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
        $this->form_validation->set_message('regex_match', translate('attribute_code_format') ?: 'Code may only contain lowercase letters, numbers and underscores.');
    }

    private function _collect()
    {
        $type = $this->input->post('type');
        if (!in_array($type, eav_types(), true)) {
            $type = 'text';
        }
        $is_option = eav_is_option_type($type);
        $is_text   = ($type === 'text');

        $swatch = $this->input->post('swatch_type');
        $swatch = ($is_option && $swatch !== '' && in_array($swatch, eav_swatch_types(), true)) ? $swatch : null;

        $validation = $this->input->post('validation');
        $validation = ($is_text && $validation !== '' && in_array($validation, eav_validations(), true)) ? $validation : null;

        $regex = ($validation === 'regex') ? trim((string) $this->input->post('regex')) : '';
        $regex = ($regex !== '') ? $regex : null;

        $default_value = trim((string) $this->input->post('default_value'));

        return [
            'code'                => trim((string) $this->input->post('code')),
            'admin_name'          => trim((string) $this->input->post('admin_name')),
            'name'                => trim((string) $this->input->post('name')),
            'type'                => $type,
            'swatch_type'         => $swatch,
            'validation'          => $validation,
            'regex'               => $regex,
            'position'            => (int) $this->input->post('position'),
            'is_required'         => $this->input->post('is_required') ? 1 : 0,
            'is_unique'           => $this->input->post('is_unique') ? 1 : 0,
            'is_filterable'       => $this->input->post('is_filterable') ? 1 : 0,
            'is_comparable'       => $this->input->post('is_comparable') ? 1 : 0,
            'is_configurable'     => $this->input->post('is_configurable') ? 1 : 0,
            'is_visible_on_front' => $this->input->post('is_visible_on_front') ? 1 : 0,
            'default_value'       => ($default_value !== '') ? $default_value : null,
            'status'              => $this->input->post('status') ?: 'Active',
        ];
    }

    /**
     * Read the parallel option[] POST arrays into assoc rows for save_options().
     */
    private function _collect_options()
    {
        $ids      = $this->input->post('option_id');
        $labels   = $this->input->post('option_label');
        $admins   = $this->input->post('option_admin_name');
        $swatches = $this->input->post('option_swatch');
        $sorts    = $this->input->post('option_sort');

        if (!is_array($labels)) {
            return [];
        }

        $out = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $out[] = [
                'id'           => isset($ids[$i]) ? (int) $ids[$i] : 0,
                'label'        => $label,
                'admin_name'   => isset($admins[$i]) ? trim((string) $admins[$i]) : '',
                'swatch_value' => isset($swatches[$i]) ? trim((string) $swatches[$i]) : '',
                'sort_order'   => isset($sorts[$i]) ? $sorts[$i] : '',
            ];
        }
        return $out;
    }
}

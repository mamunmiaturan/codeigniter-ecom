<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV)
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Attribute_family.php
 *
 * Admin CRUD for attribute families. Permission module prefix: `attribute_family`.
 * A family owns a tree of attribute groups; each group buckets attributes across
 * two columns (1 = Main, 2 = Right). The form persists BOTH the family row and its
 * group tree (via Attribute_family_model::save_tree). The `default` family is a
 * system row (is_user_defined = 0) and cannot be deleted; neither can a family that
 * is still assigned to products. Family status is a TINYINT (1 = Active, 0 = Inactive).
 */
class Attribute_family extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('attribute_family_model');
        $this->load->model('attribute_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('attribute_family', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('attribute_families') ?: 'Attribute Families';
        $this->data['sub_page']  = 'catalog/attribute_family/index';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('attribute_family', 'is_add')) {
            access_denied();
        }
        $this->data['family']         = null;
        $this->data['groups']         = [];
        $this->data['all_attributes'] = $this->attribute_model->get_active();
        $this->data['title']          = translate('add_attribute_family') ?: 'Add Attribute Family';
        $this->data['sub_page']       = 'catalog/attribute_family/form';
        $this->data['main_menu']      = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('attribute_family', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('attribute_family'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $data = $this->_collect();
        $data['is_user_defined'] = 1;
        $id = $this->attribute_family_model->insert($data);
        if ($id) {
            $ok = $this->attribute_family_model->save_tree($id, $this->_build_tree());
            $this->log_activity('create', 'attribute_family', $id, 'Created attribute family: ' . $data['name']);
            if ($ok) {
                set_alert('success', translate('information_has_been_saved_successfully'));
            } else {
                set_alert('warning', translate('family_saved_groups_partial') ?: 'Family saved, but some groups could not be saved (duplicate group names are not allowed).');
            }
            redirect(base_url('attribute_family'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('attribute_family/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('attribute_family', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $family = $this->attribute_family_model->find($id);
        if (empty($family)) {
            show_404();
            return;
        }
        $this->data['family']         = $family;
        $this->data['groups']         = $this->attribute_family_model->grouped_attributes($id);
        $this->data['all_attributes'] = $this->attribute_model->get_active();
        $this->data['title']          = translate('edit_attribute_family') ?: 'Edit Attribute Family';
        $this->data['sub_page']       = 'catalog/attribute_family/form';
        $this->data['main_menu']      = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('attribute_family', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('attribute_family'));
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
        if ($this->attribute_family_model->update($id, $data)) {
            $ok = $this->attribute_family_model->save_tree($id, $this->_build_tree());
            $this->log_activity('update', 'attribute_family', $id, 'Updated attribute family: ' . $data['name']);
            if ($ok) {
                set_alert('success', translate('information_has_been_updated_successfully'));
            } else {
                set_alert('warning', translate('family_saved_groups_partial') ?: 'Family saved, but some groups could not be saved (duplicate group names are not allowed).');
            }
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('attribute_family'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('attribute_family', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $family = $this->attribute_family_model->find($id);
        if (empty($family)) {
            show_404();
            return;
        }
        if ((int) $family['is_user_defined'] === 0) {
            set_alert('error', translate('system_attribute_family_cannot_be_deleted') ?: 'The default attribute family cannot be deleted.');
            redirect(base_url('attribute_family'));
            return;
        }
        if ($this->attribute_family_model->product_count($id) > 0) {
            set_alert('error', translate('attribute_family_in_use') ?: 'This attribute family is assigned to products and cannot be deleted.');
            redirect(base_url('attribute_family'));
            return;
        }
        if ($this->attribute_family_model->delete($id)) {
            $this->log_activity('delete', 'attribute_family', $id, 'Deleted attribute family');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('attribute_family'));
    }

    public function status()
    {
        if (!get_permission('attribute_family', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->attribute_family_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $label = ((int) $new === 1) ? 'Active' : 'Inactive';
        $this->log_activity('status', 'attribute_family', $id, 'Toggled attribute family status to ' . $label);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $label, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function unique_code($code)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->attribute_family_model->unique_code($code, $ignore)) {
            $this->form_validation->set_message('unique_code', translate('attribute_family_code_already_exists') ?: 'This code already exists.');
            return false;
        }
        return true;
    }

    public function get_families_server_side()
    {
        if (!get_permission('attribute_family', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';

        $columns_map = [1 => 'name', 4 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'name';

        $total = $this->attribute_family_model->count_all();
        $res   = $this->attribute_family_model->datatable($search, $start, $length, $order_col, $order_dir);

        $can_edit   = get_permission('attribute_family', 'is_edit');
        $can_delete = get_permission('attribute_family', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $name_html = '<strong>' . html_escape($row->name) . '</strong>'
                . '<br><small class="text-muted"><code>' . html_escape($row->code) . '</code></small>';

            $groups_count  = count($this->attribute_family_model->get_groups($row->id));
            $product_count = $this->attribute_family_model->product_count($row->id);

            $groups_html = '<span class="badge badge-info">' . $groups_count . '</span>';
            $products_html = $product_count > 0
                ? '<span class="badge badge-primary">' . $product_count . '</span>'
                : '<span class="text-muted">0</span>';

            // A system family (is_user_defined = 0) or a family in use cannot be deleted.
            $row_can_delete = $can_delete && ((int) $row->is_user_defined === 1) && ($product_count === 0);

            $status_str = ((int) $row->status === 1) ? 'Active' : 'Inactive';

            $data[] = [
                $i++,
                $name_html,
                $groups_html,
                $products_html,
                catalog_status_html($status_str, encrypt_id($row->id), $can_edit),
                catalog_row_actions('attribute_family', $row->id, $can_edit, $row_can_delete),
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
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[0,1]');
    }

    private function _collect()
    {
        return [
            'name'   => trim((string) $this->input->post('name')),
            'code'   => trim((string) $this->input->post('code')),
            'status' => ($this->input->post('status') === '0') ? 0 : 1,
        ];
    }

    /**
     * Build the group tree from the builder's parallel POST arrays.
     *
     * The group_* fields are flat arrays (group_id[], group_code[], group_name[],
     * group_column[], group_position[]) that PHP re-indexes 0..N-1 in DOM order,
     * so index $i identifies the $i-th group card. Each card's assigned attribute
     * ids arrive under the EXPLICIT index group_attributes[$i][], kept aligned by
     * the view's reindex() step. We iterate the flat arrays by $i and read
     * $group_attributes[$i] for that group's attributes.
     *
     * @return array save_tree()-shaped tree
     */
    private function _build_tree()
    {
        $group_id       = (array) $this->input->post('group_id');
        $group_code     = (array) $this->input->post('group_code');
        $group_name     = $this->input->post('group_name');
        $group_column   = (array) $this->input->post('group_column');
        $group_position = (array) $this->input->post('group_position');
        $group_attrs    = (array) $this->input->post('group_attributes');

        $tree = [];
        if (!is_array($group_name)) {
            return $tree;
        }
        foreach ($group_name as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $ids = (isset($group_attrs[$i]) && is_array($group_attrs[$i])) ? $group_attrs[$i] : [];
            $attributes = [];
            $pos = 1;
            foreach ($ids as $aid) {
                $aid = (int) $aid;
                if ($aid <= 0) {
                    continue;
                }
                $attributes[] = ['id' => $aid, 'position' => $pos++];
            }
            $tree[] = [
                'id'         => (int) ($group_id[$i] ?? 0),
                'code'       => trim((string) ($group_code[$i] ?? '')),
                'name'       => $name,
                'column'     => ((int) ($group_column[$i] ?? 1) === 2) ? 2 : 1,
                'position'   => (isset($group_position[$i]) && $group_position[$i] !== '') ? (int) $group_position[$i] : ($i + 1),
                'attributes' => $attributes,
            ];
        }
        return $tree;
    }
}

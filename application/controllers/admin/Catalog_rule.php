<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Catalog_rule.php
 *
 * Admin CRUD for catalog price rules. Permission module prefix: `catalog_rule`.
 * Every mutation rebuilds the materialised price index, and a manual "Reindex
 * prices" action is exposed from the list panel header.
 */
class Catalog_rule extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('catalog_rule_model');
        $this->load->model('category_model');
        $this->load->model('product_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('catalog_rule', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('catalog_price_rules') ?: 'Catalog Price Rules';
        $this->data['sub_page']  = 'promotion/catalog_rule_index';
        $this->data['main_menu'] = 'promotions';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('catalog_rule', 'is_add')) {
            access_denied();
        }
        $this->_form_data(null);
        $this->data['title']     = translate('add_catalog_rule') ?: 'Add Catalog Price Rule';
        $this->data['sub_page']  = 'promotion/catalog_rule_form';
        $this->data['main_menu'] = 'promotions';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('catalog_rule', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('catalog_rule'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $data = $this->_collect();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->catalog_rule_model->insert($data);
        if ($id) {
            $this->catalog_rule_model->reindex();
            $this->log_activity('create', 'catalog_rule', $id, 'Created catalog rule: ' . $data['name']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('catalog_rule'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('catalog_rule/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('catalog_rule', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $rule = $this->catalog_rule_model->find($id);
        if (empty($rule)) {
            show_404();
            return;
        }
        $this->_form_data($rule);
        $this->data['title']     = translate('edit_catalog_rule') ?: 'Edit Catalog Price Rule';
        $this->data['sub_page']  = 'promotion/catalog_rule_form';
        $this->data['main_menu'] = 'promotions';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('catalog_rule', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('catalog_rule'));
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
        if ($this->catalog_rule_model->update($id, $data)) {
            $this->catalog_rule_model->reindex();
            $this->log_activity('update', 'catalog_rule', $id, 'Updated catalog rule: ' . $data['name']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('catalog_rule'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('catalog_rule', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->catalog_rule_model->delete($id)) {
            $this->catalog_rule_model->reindex();
            $this->log_activity('delete', 'catalog_rule', $id, 'Deleted catalog rule');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('catalog_rule'));
    }

    public function status()
    {
        if (!get_permission('catalog_rule', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->catalog_rule_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->catalog_rule_model->reindex();
        $this->log_activity('status', 'catalog_rule', $id, 'Toggled catalog rule status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    /**
     * Manually rebuild the catalog price index, then bounce back to the list.
     */
    public function reindex()
    {
        if (!get_permission('catalog_rule', 'is_edit')) {
            access_denied();
        }
        $count = $this->catalog_rule_model->reindex();
        $this->log_activity('update', 'catalog_rule', 0, 'Reindexed catalog prices (' . (int) $count . ' products)');
        set_alert('success', (translate('price_index_rebuilt') ?: 'Price index rebuilt') . ': ' . (int) $count);
        redirect(base_url('catalog_rule'));
    }

    public function get_catalog_rules_server_side()
    {
        if (!get_permission('catalog_rule', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [1 => 'name', 2 => 'scope', 3 => 'action_type', 4 => 'discount_value', 5 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'name';

        $total = $this->catalog_rule_model->count_all($status);
        $res   = $this->catalog_rule_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('catalog_rule', 'is_edit');
        $can_delete = get_permission('catalog_rule', 'is_delete');
        $sym        = get_global_setting('currency_symbol') ?: '৳';

        $categories = $this->category_model->get_dropdown();
        $products   = $this->_product_dropdown();

        $scope_labels  = ['all' => translate('all') ?: 'All', 'category' => translate('category') ?: 'Category', 'product' => translate('product') ?: 'Product'];
        $action_labels = ['percentage' => translate('percentage') ?: 'Percentage', 'fixed' => translate('fixed') ?: 'Fixed'];

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $scope_html = '<span class="badge badge-info">' . html_escape($scope_labels[$row->scope] ?? $row->scope) . '</span>';
            if ($row->scope === 'category' && $row->category_id && isset($categories[$row->category_id])) {
                $scope_html .= '<br><small class="text-muted">' . html_escape($categories[$row->category_id]) . '</small>';
            } elseif ($row->scope === 'product' && $row->product_id && isset($products[$row->product_id])) {
                $scope_html .= '<br><small class="text-muted">' . html_escape($products[$row->product_id]) . '</small>';
            }

            if ($row->action_type === 'percentage') {
                $discount = rtrim(rtrim(number_format((float) $row->discount_value, 2), '0'), '.') . '%';
            } else {
                $discount = html_escape($sym) . ' ' . number_format((float) $row->discount_value, 2);
            }

            $data[] = [
                $i++,
                '<strong>' . html_escape($row->name) . '</strong>' . ($row->description ? '<br><small class="text-muted">' . html_escape($row->description) . '</small>' : ''),
                $scope_html,
                '<span class="badge badge-default">' . html_escape($action_labels[$row->action_type] ?? $row->action_type) . '</span>',
                $discount,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('catalog_rule', $row->id, $can_edit, $can_delete),
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

    private function _form_data($rule)
    {
        $this->data['rule']       = $rule;
        $this->data['categories'] = $this->category_model->get_dropdown();
        $this->data['products']   = $this->_product_dropdown();
    }

    /**
     * Simple id => name list of non-deleted products for the scope dropdown.
     */
    private function _product_dropdown()
    {
        $rows = $this->db->select('id, name')
            ->where('deleted_at', null)
            ->order_by('name', 'ASC')
            ->get('products')->result();
        $options = [];
        foreach ($rows as $row) {
            $options[$row->id] = $row->name;
        }
        return $options;
    }

    private function _set_rules()
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[150]');
        $this->form_validation->set_rules('scope', 'Scope', 'trim|required|in_list[all,category,product]');
        $this->form_validation->set_rules('action_type', 'Action Type', 'trim|required|in_list[percentage,fixed]');
        $this->form_validation->set_rules('discount_value', 'Discount Value', 'trim|required|numeric');
        $this->form_validation->set_rules('sort_order', 'Sort Order', 'trim|is_natural');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
    }

    private function _collect()
    {
        $scope = $this->input->post('scope') ?: 'all';
        return [
            'name'            => trim((string) $this->input->post('name')),
            'description'     => $this->input->post('description'),
            'status'          => $this->input->post('status') ?: 'Active',
            'sort_order'      => (int) $this->input->post('sort_order'),
            'starts_at'       => $this->_ndate($this->input->post('starts_at'), false),
            'ends_at'         => $this->_ndate($this->input->post('ends_at'), true),
            'scope'           => $scope,
            'category_id'     => $scope === 'category' ? $this->_nint($this->input->post('category_id')) : null,
            'product_id'      => $scope === 'product' ? $this->_nint($this->input->post('product_id')) : null,
            'action_type'     => $this->input->post('action_type') ?: 'percentage',
            'discount_value'  => (float) $this->input->post('discount_value'),
            'end_other_rules' => $this->input->post('end_other_rules') ? 1 : 0,
        ];
    }

    private function _nint($v)
    {
        $v = (int) $v;
        return $v > 0 ? $v : null;
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

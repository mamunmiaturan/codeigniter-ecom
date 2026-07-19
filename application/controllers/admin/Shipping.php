<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Shipping
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Shipping.php
 *
 * Admin CRUD for shipping zones + methods. Permission module prefix: `shipping`.
 * A zone matches an address by division list ('*' = all divisions / fallback).
 * Each method computes a rate (flat / per-unit / free) with an optional
 * free-over threshold. Two server-side DataTables drive the index; the methods
 * table can be filtered by zone.
 */
class Shipping extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shipping_model');
        $this->load->helper('catalog');
    }

    // =====================================================================
    // Index (two DataTables)
    // =====================================================================

    public function index()
    {
        // Default landing for the Shipping menu -> the Shipping Zones list.
        redirect(base_url('shipping/zones'));
    }

    // Dedicated Shipping Zones list page (sidebar: Shipping > Shipping Zones)
    public function zones()
    {
        if (!get_permission('shipping_zone', 'is_view')) {
            access_denied();
        }
        $this->data['tab']       = 'zones';
        $this->data['zones']     = $this->shipping_model->get_zones_dropdown();
        $this->data['title']     = translate('shipping_zones') ?: 'Shipping Zones';
        $this->data['sub_page']  = 'shipping/zones';
        $this->data['main_menu'] = 'shipping';
        $this->load->view('layout/index', $this->data);
    }

    // Dedicated Shipping Methods list page (sidebar: Shipping > Shipping Methods)
    public function methods()
    {
        if (!get_permission('shipping_method', 'is_view')) {
            access_denied();
        }
        $this->data['tab']       = 'methods';
        $this->data['zones']     = $this->shipping_model->get_zones_dropdown();
        $this->data['title']     = translate('shipping_methods') ?: 'Shipping Methods';
        $this->data['sub_page']  = 'shipping/methods';
        $this->data['main_menu'] = 'shipping';
        $this->load->view('layout/index', $this->data);
    }

    public function get_zones_server_side()
    {
        if (!get_permission('shipping_zone', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';

        $columns_map = [1 => 'name', 2 => 'divisions', 3 => 'sort_order', 4 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 3;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'sort_order';

        $total = $this->shipping_model->count_zones();
        $res   = $this->shipping_model->zones_datatable($search, $start, $length, $order_col, $order_dir);

        $can_edit   = get_permission('shipping_zone', 'is_edit');
        $can_delete = get_permission('shipping_zone', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $data[] = [
                $i++,
                html_escape($row->name),
                $this->_divisions_html($row->divisions),
                (int) $row->sort_order,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                $this->_row_actions('zone', $row->id, $can_edit, $can_delete),
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

    public function get_methods_server_side()
    {
        if (!get_permission('shipping_method', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw    = intval($this->input->post('draw'));
        $start   = intval($this->input->post('start'));
        $length  = intval($this->input->post('length'));
        $search  = $this->input->post('search')['value'] ?? '';
        $zone_id = $this->input->post('zone_id') ?? '';

        $columns_map = [
            1 => 'z.name',
            2 => 'm.code',
            3 => 'm.title',
            4 => 'm.type',
            5 => 'm.base_rate',
            7 => 'm.sort_order',
            8 => 'm.status',
        ];
        $order_idx = $this->input->post('order')[0]['column'] ?? 7;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'm.sort_order';

        $total = $this->shipping_model->count_methods();
        $res   = $this->shipping_model->methods_datatable($search, $start, $length, $order_col, $order_dir, $zone_id);

        $can_edit   = get_permission('shipping_method', 'is_edit');
        $can_delete = get_permission('shipping_method', 'is_delete');
        $symbol     = get_global_setting('currency_symbol') ?: '৳';

        $type_labels = [
            'flat'     => translate('flat') ?: 'Flat',
            'per_unit' => translate('per_unit') ?: 'Per Unit',
            'free'     => translate('free') ?: 'Free',
        ];

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $title_html = '<strong>' . html_escape($row->title) . '</strong>'
                . ($row->description ? '<br><small class="text-muted">' . html_escape($row->description) . '</small>' : '');

            $data[] = [
                $i++,
                html_escape($row->zone_name ?: '—'),
                '<code>' . html_escape($row->code) . '</code>',
                $title_html,
                '<span class="badge badge-info">' . html_escape($type_labels[$row->type] ?? $row->type) . '</span>',
                $this->_rate_html($row, $symbol),
                $this->_delivery_html($row),
                (int) $row->sort_order,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                $this->_row_actions('method', $row->id, $can_edit, $can_delete),
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
    // Zone CRUD
    // =====================================================================

    public function zone_create()
    {
        if (!get_permission('shipping_zone', 'is_add')) {
            access_denied();
        }
        $this->data['zone']      = null;
        $this->data['title']     = translate('add_shipping_zone') ?: 'Add Shipping Zone';
        $this->data['sub_page']  = 'shipping/zone_form';
        $this->data['main_menu'] = 'shipping';
        $this->load->view('layout/index', $this->data);
    }

    public function zone_store()
    {
        if (!get_permission('shipping_zone', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('shipping'));
            return;
        }
        $this->_set_zone_rules();
        if ($this->form_validation->run() === false) {
            $this->zone_create();
            return;
        }
        $data = $this->_collect_zone();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->shipping_model->insert($data);
        if ($id) {
            $this->log_activity('create', 'shipping', $id, 'Created shipping zone: ' . $data['name']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('shipping'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('shipping/zone_create'));
    }

    public function zone_edit($hash = '')
    {
        if (!get_permission('shipping_zone', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $zone = $this->shipping_model->find($id);
        if (empty($zone)) {
            show_404();
            return;
        }
        $this->data['zone']      = $zone;
        $this->data['title']     = translate('edit_shipping_zone') ?: 'Edit Shipping Zone';
        $this->data['sub_page']  = 'shipping/zone_form';
        $this->data['main_menu'] = 'shipping';
        $this->load->view('layout/index', $this->data);
    }

    public function zone_update()
    {
        if (!get_permission('shipping_zone', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('shipping'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            show_404();
            return;
        }
        $this->_set_zone_rules();
        if ($this->form_validation->run() === false) {
            $this->zone_edit($this->input->post('id'));
            return;
        }
        $data = $this->_collect_zone();
        $data['updated_by'] = get_loggedin_user_id();
        if ($this->shipping_model->update($id, $data)) {
            $this->log_activity('update', 'shipping', $id, 'Updated shipping zone: ' . $data['name']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('shipping'));
    }

    public function zone_delete($hash = '')
    {
        if (!get_permission('shipping_zone', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->shipping_model->delete($id)) {
            $this->log_activity('delete', 'shipping', $id, 'Deleted shipping zone');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('shipping'));
    }

    public function zone_status()
    {
        if (!get_permission('shipping_zone', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->shipping_model->toggle_zone_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'shipping', $id, 'Toggled shipping zone status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    // =====================================================================
    // Method CRUD
    // =====================================================================

    public function method_create()
    {
        if (!get_permission('shipping_method', 'is_add')) {
            access_denied();
        }
        $this->data['method']    = null;
        $this->data['zones']     = $this->shipping_model->get_zones_dropdown();
        $this->data['title']     = translate('add_shipping_method') ?: 'Add Shipping Method';
        $this->data['sub_page']  = 'shipping/method_form';
        $this->data['main_menu'] = 'shipping';
        $this->load->view('layout/index', $this->data);
    }

    public function method_store()
    {
        if (!get_permission('shipping_method', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('shipping'));
            return;
        }
        $this->_set_method_rules();
        if ($this->form_validation->run() === false) {
            $this->method_create();
            return;
        }
        $data = $this->_collect_method();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->shipping_model->insert_method($data);
        if ($id) {
            $this->log_activity('create', 'shipping', $id, 'Created shipping method: ' . $data['title']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('shipping'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('shipping/method_create'));
    }

    public function method_edit($hash = '')
    {
        if (!get_permission('shipping_method', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $method = $this->shipping_model->get_method($id);
        if (empty($method)) {
            show_404();
            return;
        }
        $this->data['method']    = $method;
        $this->data['zones']     = $this->shipping_model->get_zones_dropdown();
        $this->data['title']     = translate('edit_shipping_method') ?: 'Edit Shipping Method';
        $this->data['sub_page']  = 'shipping/method_form';
        $this->data['main_menu'] = 'shipping';
        $this->load->view('layout/index', $this->data);
    }

    public function method_update()
    {
        if (!get_permission('shipping_method', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('shipping'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            show_404();
            return;
        }
        $this->_set_method_rules();
        if ($this->form_validation->run() === false) {
            $this->method_edit($this->input->post('id'));
            return;
        }
        $data = $this->_collect_method();
        $data['updated_by'] = get_loggedin_user_id();
        if ($this->shipping_model->update_method($id, $data)) {
            $this->log_activity('update', 'shipping', $id, 'Updated shipping method: ' . $data['title']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('shipping'));
    }

    public function method_delete($hash = '')
    {
        if (!get_permission('shipping_method', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->shipping_model->delete_method($id)) {
            $this->log_activity('delete', 'shipping', $id, 'Deleted shipping method');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('shipping'));
    }

    public function method_status()
    {
        if (!get_permission('shipping_method', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->shipping_model->toggle_method_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'shipping', $id, 'Toggled shipping method status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    // =====================================================================
    // Validation callbacks
    // =====================================================================

    // Callback: the chosen zone must exist (and not be trashed).
    public function zone_exists($zone_id)
    {
        if (!$zone_id || empty($this->shipping_model->find((int) $zone_id))) {
            $this->form_validation->set_message('zone_exists', translate('please_select_a_valid_zone') ?: 'Please select a valid zone.');
            return false;
        }
        return true;
    }

    // =====================================================================
    // Helpers — validation rules
    // =====================================================================

    private function _set_zone_rules()
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('divisions', 'Divisions', 'trim|required|max_length[500]');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
        $this->form_validation->set_rules('sort_order', 'Sort Order', 'trim|is_natural');
    }

    private function _set_method_rules()
    {
        $this->form_validation->set_rules('zone_id', 'Zone', 'trim|required|callback_zone_exists');
        $this->form_validation->set_rules('code', 'Code', 'trim|required|max_length[60]');
        $this->form_validation->set_rules('title', 'Title', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|in_list[flat,per_unit,free]');
        $this->form_validation->set_rules('base_rate', 'Base Rate', 'trim|numeric');
        $this->form_validation->set_rules('per_unit_rate', 'Per Unit Rate', 'trim|numeric');
        $this->form_validation->set_rules('free_over', 'Free Over', 'trim|numeric');
        $this->form_validation->set_rules('min_days', 'Min Days', 'trim|is_natural');
        $this->form_validation->set_rules('max_days', 'Max Days', 'trim|is_natural');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
        $this->form_validation->set_rules('sort_order', 'Sort Order', 'trim|is_natural');
    }

    // =====================================================================
    // Helpers — POST collection
    // =====================================================================

    private function _collect_zone()
    {
        return [
            'name'       => trim((string) $this->input->post('name')),
            'divisions'  => $this->_normalize_divisions($this->input->post('divisions')),
            'status'     => $this->input->post('status') ?: 'Active',
            'sort_order' => (int) $this->input->post('sort_order'),
        ];
    }

    private function _collect_method()
    {
        return [
            'zone_id'       => (int) $this->input->post('zone_id'),
            'code'          => trim((string) $this->input->post('code')),
            'title'         => trim((string) $this->input->post('title')),
            'description'   => $this->input->post('description') ?: null,
            'type'          => $this->input->post('type') ?: 'flat',
            'base_rate'     => (float) $this->input->post('base_rate'),
            'per_unit_rate' => (float) $this->input->post('per_unit_rate'),
            'free_over'     => $this->_nullable_dec($this->input->post('free_over')),
            'min_days'      => $this->_nullable_int($this->input->post('min_days')),
            'max_days'      => $this->_nullable_int($this->input->post('max_days')),
            'status'        => $this->input->post('status') ?: 'Active',
            'sort_order'    => (int) $this->input->post('sort_order'),
        ];
    }

    /**
     * Clean a comma-separated division list: trim members, drop blanks, dedupe.
     * A '*' anywhere collapses to the single fallback token '*'.
     */
    private function _normalize_divisions($raw)
    {
        $parts = array_map('trim', explode(',', (string) $raw));
        $out = [];
        foreach ($parts as $p) {
            if ($p === '') {
                continue;
            }
            if ($p === '*') {
                return '*';
            }
            if (!in_array($p, $out, true)) {
                $out[] = $p;
            }
        }
        return empty($out) ? '*' : implode(', ', $out);
    }

    private function _nullable_dec($v)
    {
        $v = trim((string) $v);
        return ($v === '' || !is_numeric($v)) ? null : (float) $v;
    }

    private function _nullable_int($v)
    {
        $v = trim((string) $v);
        return ($v === '' || !ctype_digit($v)) ? null : (int) $v;
    }

    // =====================================================================
    // Helpers — row rendering
    // =====================================================================

    private function _row_actions($entity, $id, $can_edit, $can_delete)
    {
        $html = '';
        if ($can_edit) {
            $html .= '<a href="' . base_url('shipping/' . $entity . '_edit/' . encrypt_id($id)) . '" '
                . 'class="btn btn-circle btn-default icon" data-toggle="tooltip" '
                . 'data-original-title="' . (translate('edit') ?: 'Edit') . '"><i class="fas fa-pen-nib"></i></a> ';
        }
        if ($can_delete) {
            $html .= btn_delete('shipping/' . $entity . '_delete/' . encrypt_id($id));
        }
        return $html;
    }

    private function _divisions_html($divisions)
    {
        $parts = array_map('trim', explode(',', (string) $divisions));
        $badges = '';
        foreach ($parts as $p) {
            if ($p === '') {
                continue;
            }
            if ($p === '*') {
                $badges .= '<span class="badge badge-primary">' . (translate('all_divisions') ?: 'All divisions') . '</span> ';
            } else {
                $badges .= '<span class="badge badge-secondary">' . html_escape($p) . '</span> ';
            }
        }
        return $badges ?: '—';
    }

    private function _rate_html($row, $symbol)
    {
        if ($row->type === 'free') {
            $html = '<span class="badge badge-success">' . (translate('free') ?: 'Free') . '</span>';
        } elseif ($row->type === 'per_unit') {
            $html = html_escape($symbol) . ' ' . number_format((float) $row->base_rate, 2)
                . ' + ' . html_escape($symbol) . ' ' . number_format((float) $row->per_unit_rate, 2)
                . '/' . (translate('unit') ?: 'unit');
        } else {
            $html = html_escape($symbol) . ' ' . number_format((float) $row->base_rate, 2);
        }
        if ($row->free_over !== null && $row->free_over !== '') {
            $html .= '<br><small class="text-muted">' . (translate('free_over') ?: 'Free over') . ' '
                . html_escape($symbol) . ' ' . number_format((float) $row->free_over, 2) . '</small>';
        }
        return $html;
    }

    private function _delivery_html($row)
    {
        $min = ($row->min_days !== null && $row->min_days !== '') ? (int) $row->min_days : null;
        $max = ($row->max_days !== null && $row->max_days !== '') ? (int) $row->max_days : null;
        $days = translate('days') ?: 'days';
        if ($min !== null && $max !== null) {
            return ($min === $max) ? ($min . ' ' . $days) : ($min . '–' . $max . ' ' . $days);
        }
        if ($min !== null) {
            return $min . '+ ' . $days;
        }
        if ($max !== null) {
            return '≤ ' . $max . ' ' . $days;
        }
        return '—';
    }
}

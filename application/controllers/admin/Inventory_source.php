<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Multi-Source Inventory)
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Inventory_source.php
 *
 * Admin CRUD for inventory sources (warehouses). Permission module prefix:
 * `inventory_source`. Exactly one source may be flagged default; the default
 * source cannot be deleted so a store always keeps at least one warehouse.
 */
class Inventory_source extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('inventory_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('inventory_source', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('inventory_sources') ?: 'Inventory Sources';
        $this->data['sub_page']  = 'inventory/index';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('inventory_source', 'is_add')) {
            access_denied();
        }
        $this->data['source']    = null;
        $this->data['title']     = translate('add_inventory_source') ?: 'Add Inventory Source';
        $this->data['sub_page']  = 'inventory/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('inventory_source', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('inventory_source'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $data = $this->_collect();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->inventory_model->insert($data);
        if ($id) {
            if (!empty($data['is_default'])) {
                $this->inventory_model->clear_other_defaults($id);
            }
            $this->log_activity('create', 'inventory_source', $id, 'Created inventory source: ' . $data['name']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('inventory_source'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('inventory_source/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('inventory_source', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $source = $this->inventory_model->find($id);
        if (empty($source)) {
            show_404();
            return;
        }
        $this->data['source']    = $source;
        $this->data['title']     = translate('edit_inventory_source') ?: 'Edit Inventory Source';
        $this->data['sub_page']  = 'inventory/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('inventory_source', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('inventory_source'));
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
        if ($this->inventory_model->update($id, $data)) {
            if (!empty($data['is_default'])) {
                $this->inventory_model->clear_other_defaults($id);
            }
            // A status change (or activation) alters availability — re-sync the
            // cached rollup of every product stocked at this source.
            $this->inventory_model->recompute_source($id);
            $this->log_activity('update', 'inventory_source', $id, 'Updated inventory source: ' . $data['name']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('inventory_source'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('inventory_source', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $source = $this->inventory_model->find($id);
        if (empty($source)) {
            show_404();
            return;
        }
        // A store must keep at least the default warehouse.
        if (!empty($source['is_default'])) {
            set_alert('error', translate('default_source_cannot_be_deleted') ?: 'The default inventory source cannot be deleted.');
            redirect(base_url('inventory_source'));
            return;
        }
        if ($this->inventory_model->delete($id)) {
            // Soft-deleting a source removes its qty from availability — re-sync
            // the rollup of every product it stocked (rows still exist; recompute
            // excludes the now-deleted source).
            $this->inventory_model->recompute_source($id);
            $this->log_activity('delete', 'inventory_source', $id, 'Deleted inventory source');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('inventory_source'));
    }

    public function status()
    {
        if (!get_permission('inventory_source', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->inventory_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'inventory_source', $id, 'Toggled inventory source status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function unique_code($code)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->inventory_model->unique_code($code, $ignore)) {
            $this->form_validation->set_message('unique_code', translate('inventory_source_code_already_exists') ?: 'This code already exists.');
            return false;
        }
        return true;
    }

    public function get_inventory_sources_server_side()
    {
        if (!get_permission('inventory_source', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [1 => 'name', 2 => 'priority', 3 => 'city', 4 => 'is_default', 5 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'name';

        $total = $this->inventory_model->count_all($status);
        $res   = $this->inventory_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('inventory_source', 'is_edit');
        $can_delete = get_permission('inventory_source', 'is_delete');

        $default_label = translate('default') ?: 'Default';

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $default_badge = !empty($row->is_default)
                ? '<span class="badge badge-primary">' . html_escape($default_label) . '</span>'
                : '<span class="text-muted">—</span>';

            $loc_parts = array_filter([$row->city, $row->country], function ($v) {
                return $v !== null && $v !== '';
            });
            $location = $loc_parts
                ? html_escape(implode(', ', $loc_parts))
                : '<span class="text-muted">—</span>';

            $data[] = [
                $i++,
                '<strong>' . html_escape($row->name) . '</strong><br><code>' . html_escape($row->code) . '</code>',
                (int) $row->priority,
                $location,
                $default_badge,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('inventory_source', $row->id, $can_edit, $can_delete),
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

    // ---- warehouse operations: ledger, transfer, reports ----

    /** Stock movement ledger (server-side datatable page). */
    public function movements()
    {
        if (!get_permission('inventory_source', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('stock_movements') ?: 'Stock Movements';
        $this->data['sub_page']  = 'inventory/movements';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function get_movements_server_side()
    {
        if (!get_permission('inventory_source', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $type   = $this->input->post('type') ?? '';

        $total = $this->inventory_model->movements_count($type);
        $res   = $this->inventory_model->movements_datatable($search, $start, $length, $type);

        $badge = [
            'in' => 'success', 'out' => 'danger', 'adjust' => 'info',
            'transfer_in' => 'success', 'transfer_out' => 'warning', 'allocation' => 'secondary',
        ];
        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $r) {
            $cls = $badge[$r->type] ?? 'secondary';
            $qty = (int) $r->qty;
            $qty_html = '<strong class="text-' . ($qty < 0 ? 'danger' : 'success') . '">' . ($qty > 0 ? '+' : '') . $qty . '</strong>';
            $data[] = [
                $i++,
                time_ago($r->created_at),
                html_escape($r->product_name ?: ('#' . $r->product_id)),
                html_escape($r->source_name ?: '—'),
                '<span class="badge badge-' . $cls . '">' . html_escape(str_replace('_', ' ', $r->type)) . '</span>',
                $qty_html,
                html_escape($r->reason ?: '—') . ($r->reference ? ' <code>' . html_escape($r->reference) . '</code>' : ''),
            ];
        }
        return $this->jsonResponse([
            'draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $res['filtered'],
            'data' => $data, 'csrfHash' => $this->security->get_csrf_hash(),
        ]);
    }

    /** Warehouse-to-warehouse stock transfer form. */
    public function transfer()
    {
        if (!get_permission('inventory_source', 'is_edit')) {
            access_denied();
        }
        $this->load->model('product_model');
        $this->data['products']  = $this->product_model->simple_dropdown();
        $this->data['sources']   = $this->inventory_model->get_dropdown();
        $this->data['title']     = translate('stock_transfer') ?: 'Stock Transfer';
        $this->data['sub_page']  = 'inventory/transfer';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function transfer_save()
    {
        if (!get_permission('inventory_source', 'is_edit')) {
            access_denied();
        }
        $product_id  = (int) $this->input->post('product_id');
        $from_source = (int) $this->input->post('from_source');
        $to_source   = (int) $this->input->post('to_source');
        $qty         = (int) $this->input->post('qty');
        $note        = trim((string) $this->input->post('note')) ?: null;

        if ($product_id <= 0 || $from_source <= 0 || $to_source <= 0 || $qty <= 0) {
            set_alert('error', translate('please_fill_all_required_fields') ?: 'Select a product, both warehouses and a quantity.');
            redirect(base_url('inventory_source/transfer'));
            return;
        }
        if ($from_source === $to_source) {
            set_alert('error', translate('source_and_destination_must_differ') ?: 'Source and destination warehouses must differ.');
            redirect(base_url('inventory_source/transfer'));
            return;
        }
        $ok = $this->inventory_model->transfer_stock($product_id, $from_source, $to_source, $qty, 0, $note, (int) get_loggedin_user_id());
        if ($ok) {
            $this->log_activity('update', 'inventory_source', $from_source, 'Transferred ' . $qty . ' units of product #' . $product_id . ' to source #' . $to_source);
            set_alert('success', translate('stock_transferred_successfully') ?: 'Stock transferred successfully.');
        } else {
            set_alert('error', translate('insufficient_stock_at_source') ?: 'Insufficient stock at the source warehouse.');
        }
        redirect(base_url('inventory_source/transfer'));
    }

    /** Per-warehouse on-hand report. */
    public function report()
    {
        if (!get_permission('inventory_source', 'is_view')) {
            access_denied();
        }
        $this->data['by_source'] = $this->inventory_model->stock_by_source();
        $this->data['title']     = translate('warehouse_stock_report') ?: 'Warehouse Stock Report';
        $this->data['sub_page']  = 'inventory/report';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    /** Low-stock products list (threshold via ?threshold). */
    public function low_stock()
    {
        if (!get_permission('inventory_source', 'is_view')) {
            access_denied();
        }
        $threshold = (int) ($this->input->get('threshold') ?: 5);
        if ($threshold < 0) {
            $threshold = 5;
        }
        $this->data['threshold'] = $threshold;
        $this->data['items']     = $this->inventory_model->low_stock($threshold);
        $this->data['title']     = translate('low_stock_products') ?: 'Low Stock Products';
        $this->data['sub_page']  = 'inventory/low_stock';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    // ---- helpers ----

    private function _set_rules()
    {
        $this->form_validation->set_rules('code', 'Code', 'trim|required|max_length[50]|callback_unique_code');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('priority', 'Priority', 'trim|is_natural');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
    }

    private function _collect()
    {
        return [
            'code'           => trim((string) $this->input->post('code')),
            'name'           => trim((string) $this->input->post('name')),
            'priority'       => (int) $this->input->post('priority'),
            'is_default'     => $this->input->post('is_default') ? 1 : 0,
            'contact_name'   => $this->_nullable('contact_name'),
            'contact_email'  => $this->_nullable('contact_email'),
            'contact_number' => $this->_nullable('contact_number'),
            'country'        => $this->_nullable('country'),
            'state'          => $this->_nullable('state'),
            'city'           => $this->_nullable('city'),
            'street'         => $this->_nullable('street'),
            'postcode'       => $this->_nullable('postcode'),
            'status'         => $this->input->post('status') ?: 'Active',
        ];
    }

    private function _nullable($field)
    {
        $val = trim((string) $this->input->post($field));
        return $val === '' ? null : $val;
    }
}

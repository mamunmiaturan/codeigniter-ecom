<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Flash Sale
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Flash_sale.php
 *
 * Admin CRUD for time-boxed flash sales (a scheduled window + a set of products
 * each at a special sale price). Permission module prefix: `flash_sale`.
 */
class Flash_sale extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['flash_sale_model', 'product_model']);
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('flash_sale', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('flash_sale') ?: 'Flash Sale';
        $this->data['sub_page']  = 'flash_sale/index';
        $this->data['main_menu'] = 'flash_sale';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('flash_sale', 'is_add')) {
            access_denied();
        }
        $this->data['sale']      = null;
        $this->data['items']     = [];
        $this->data['products']  = $this->product_model->simple_dropdown();
        $this->data['title']     = translate('add_flash_sale') ?: 'Add Flash Sale';
        $this->data['sub_page']  = 'flash_sale/form';
        $this->data['main_menu'] = 'flash_sale';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('flash_sale', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('flash_sale'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }

        $data = $this->_collect();
        $data['created_by'] = get_loggedin_user_id();
        $id = $this->flash_sale_model->insert($data);
        if ($id) {
            $this->flash_sale_model->save_items($id, (array) $this->input->post('product_id'), (array) $this->input->post('sale_price'));
            $this->log_activity('create', 'flash_sale', $id, 'Created flash sale: ' . $data['title']);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('flash_sale'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('flash_sale/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('flash_sale', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $sale = $id ? $this->flash_sale_model->find($id) : null;
        if (empty($sale)) {
            show_404();
            return;
        }
        $this->data['sale']      = $sale;
        $this->data['items']     = $this->flash_sale_model->get_items($id);
        $this->data['products']  = $this->product_model->simple_dropdown();
        $this->data['title']     = translate('edit_flash_sale') ?: 'Edit Flash Sale';
        $this->data['sub_page']  = 'flash_sale/form';
        $this->data['main_menu'] = 'flash_sale';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('flash_sale', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('flash_sale'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id || !$this->flash_sale_model->find($id)) {
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
        if ($this->flash_sale_model->update($id, $data)) {
            $this->flash_sale_model->save_items($id, (array) $this->input->post('product_id'), (array) $this->input->post('sale_price'));
            $this->log_activity('update', 'flash_sale', $id, 'Updated flash sale: ' . $data['title']);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('flash_sale'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('flash_sale', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->flash_sale_model->delete($id)) {
            $this->log_activity('delete', 'flash_sale', $id, 'Deleted flash sale');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('flash_sale'));
    }

    public function status()
    {
        if (!get_permission('flash_sale', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->flash_sale_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'flash_sale', $id, 'Toggled flash sale status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    // Server-side DataTables processing
    public function get_flash_sales_server_side()
    {
        if (!get_permission('flash_sale', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';
        if ($status !== '' && !in_array($status, ['Active', 'Inactive'], true)) {
            $status = '';
        }

        $columns_map = [0 => 'fs.id', 1 => 'fs.title', 2 => 'fs.starts_at', 4 => 'fs.status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 2;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'fs.starts_at';

        $total = $this->flash_sale_model->count_all($status);
        $res   = $this->flash_sale_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('flash_sale', 'is_edit');
        $can_delete = get_permission('flash_sale', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $data[] = [
                $i++,
                html_escape($row->title),
                $this->_window_html($row->starts_at, $row->ends_at),
                '<span class="badge badge-info">' . (int) $row->items_count . '</span>',
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('flash_sale', $row->id, $can_edit, $can_delete),
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
        $this->form_validation->set_rules('title', 'Title', 'trim|required|max_length[200]');
        $this->form_validation->set_rules('starts_at', 'Starts At', 'trim|required');
        $this->form_validation->set_rules('ends_at', 'Ends At', 'trim|required|callback_after_start');
    }

    /** Validation callback: ends_at must be strictly after starts_at. */
    public function after_start($ends_at)
    {
        $start = $this->_clean_datetime($this->input->post('starts_at'));
        $end   = $this->_clean_datetime($ends_at);
        if ($start === null || $end === null) {
            return true; // required rule handles emptiness
        }
        if (strtotime($end) <= strtotime($start)) {
            $this->form_validation->set_message('after_start', translate('end_must_be_after_start') ?: 'The End date must be after the Start date.');
            return false;
        }
        return true;
    }

    private function _collect()
    {
        return [
            'title'     => $this->input->post('title'),
            'starts_at' => $this->_clean_datetime($this->input->post('starts_at')),
            'ends_at'   => $this->_clean_datetime($this->input->post('ends_at')),
            'status'    => $this->input->post('status') ?: 'Active',
        ];
    }

    /** Normalise a datetime-local value ("Y-m-dTH:i") to MySQL datetime, or null. */
    private function _clean_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function _window_html($starts_at, $ends_at)
    {
        $fmt = function ($v) {
            $v = trim((string) $v);
            return $v !== '' ? date('M j, Y g:i A', strtotime($v)) : '—';
        };
        return '<span class="text-nowrap">' . html_escape($fmt($starts_at)) . '</span>'
            . ' <i class="fas fa-arrow-right fa-xs text-muted"></i> '
            . '<span class="text-nowrap">' . html_escape($fmt($ends_at)) . '</span>';
    }
}

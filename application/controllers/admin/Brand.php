<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Brand.php
 *
 * Admin CRUD for product brands. Permission module prefix: `brand`.
 */
class Brand extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('brand_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('brand', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('brands') ?: 'Brands';
        $this->data['sub_page']  = 'catalog/brand/index';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('brand', 'is_add')) {
            access_denied();
        }
        $this->data['brand']     = null;
        $this->data['title']     = translate('add_brand') ?: 'Add Brand';
        $this->data['sub_page']  = 'catalog/brand/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('brand', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('brand'));
            return;
        }

        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }

        $upload = $this->_upload_image('logo', 'brand');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('brand/create'));
            return;
        }

        $name = $this->input->post('name');
        $data = [
            'name'             => $name,
            'slug'             => $this->brand_model->unique_slug($name),
            'description'      => $this->input->post('description'),
            'website'          => $this->input->post('website'),
            'is_featured'      => $this->input->post('is_featured') ? 1 : 0,
            'sort_order'       => (int) $this->input->post('sort_order'),
            'status'           => $this->input->post('status') ?: 'Active',
            'meta_title'       => $this->input->post('meta_title'),
            'meta_description' => $this->input->post('meta_description'),
            'created_by'       => get_loggedin_user_id(),
        ];
        if ($upload['file']) {
            $data['logo'] = $upload['file'];
        }

        $id = $this->brand_model->insert($data);
        if ($id) {
            $this->log_activity('create', 'brand', $id, 'Created brand: ' . $name);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('brand'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('brand/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('brand', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $brand = $this->brand_model->find($id);
        if (empty($brand)) {
            show_404();
            return;
        }
        $this->data['brand']     = $brand;
        $this->data['title']     = translate('edit_brand') ?: 'Edit Brand';
        $this->data['sub_page']  = 'catalog/brand/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('brand', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('brand'));
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

        $upload = $this->_upload_image('logo', 'brand');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('brand/edit/' . encrypt_id($id)));
            return;
        }

        $name = $this->input->post('name');
        $data = [
            'name'             => $name,
            'slug'             => $this->brand_model->unique_slug($name, $id),
            'description'      => $this->input->post('description'),
            'website'          => $this->input->post('website'),
            'is_featured'      => $this->input->post('is_featured') ? 1 : 0,
            'sort_order'       => (int) $this->input->post('sort_order'),
            'status'           => $this->input->post('status') ?: 'Active',
            'meta_title'       => $this->input->post('meta_title'),
            'meta_description' => $this->input->post('meta_description'),
            'updated_by'       => get_loggedin_user_id(),
        ];
        if ($upload['file']) {
            $data['logo'] = $upload['file'];
        }

        if ($this->brand_model->update($id, $data)) {
            $this->log_activity('update', 'brand', $id, 'Updated brand: ' . $name);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('brand'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('brand', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->brand_model->delete($id)) {
            $this->log_activity('delete', 'brand', $id, 'Deleted brand');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('brand'));
    }

    public function status()
    {
        if (!get_permission('brand', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->brand_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'brand', $id, 'Toggled brand status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    public function get_brands_server_side()
    {
        if (!get_permission('brand', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [
            0 => 'id',
            2 => 'name',
            3 => 'slug',
            4 => 'website',
            5 => 'sort_order',
            6 => 'status',
        ];
        $order_idx = $this->input->post('order')[0]['column'] ?? 2;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'name';

        $total = $this->brand_model->count_all($status);
        $res   = $this->brand_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('brand', 'is_edit');
        $can_delete = get_permission('brand', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $logo_html = $row->logo
                ? '<img class="rounded" src="' . base_url('uploads/catalog/brand/' . $row->logo) . '" width="40" height="40" />'
                : '<span class="text-muted"><i class="far fa-copyright"></i></span>';

            $website_html = $row->website
                ? '<a href="' . html_escape($row->website) . '" target="_blank" rel="noopener">' . html_escape($row->website) . '</a>'
                : '—';

            $data[] = [
                $i++,
                $logo_html,
                html_escape($row->name),
                html_escape($row->slug),
                $website_html,
                (int) $row->sort_order,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('brand', $row->id, $can_edit, $can_delete),
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
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[150]');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
        $this->form_validation->set_rules('sort_order', 'Sort Order', 'trim|is_natural');
    }

    private function _upload_image($field, $subdir)
    {
        if (empty($_FILES[$field]['name'])) {
            return ['ok' => true, 'file' => null];
        }
        $dir = FCPATH . 'uploads/catalog/' . $subdir . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|gif|webp|svg',
            'max_size'      => 4096,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload($field)) {
            return ['ok' => true, 'file' => $this->upload->data('file_name')];
        }
        return ['ok' => false, 'error' => strip_tags($this->upload->display_errors('', ' '))];
    }
}

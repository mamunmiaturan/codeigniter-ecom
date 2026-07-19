<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Category.php
 *
 * Admin CRUD for product categories (nested). Permission module prefix: `category`.
 */
class Category extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('category_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('category', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('categories') ?: 'Categories';
        $this->data['sub_page']  = 'catalog/category/index';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('category', 'is_add')) {
            access_denied();
        }
        $this->data['category']  = null;
        $this->data['parents']   = $this->category_model->get_dropdown();
        $this->data['title']     = translate('add_category') ?: 'Add Category';
        $this->data['sub_page']  = 'catalog/category/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('category', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('category'));
            return;
        }

        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }

        $upload = $this->_upload_image('image', 'category');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('category/create'));
            return;
        }

        $name = $this->input->post('name');
        $data = [
            'name'             => $name,
            'slug'             => $this->category_model->unique_slug($name),
            'parent_id'        => $this->_clean_parent($this->input->post('parent_id')),
            'description'      => $this->input->post('description'),
            'icon'             => $this->input->post('icon'),
            'is_featured'      => $this->input->post('is_featured') ? 1 : 0,
            'sort_order'       => (int) $this->input->post('sort_order'),
            'status'           => $this->input->post('status') ?: 'Active',
            'meta_title'       => $this->input->post('meta_title'),
            'meta_description' => $this->input->post('meta_description'),
            'created_by'       => get_loggedin_user_id(),
        ];
        if ($upload['file']) {
            $data['image'] = $upload['file'];
        }

        $id = $this->category_model->insert($data);
        if ($id) {
            $this->log_activity('create', 'category', $id, 'Created category: ' . $name);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('category'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('category/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('category', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $category = $this->category_model->find($id);
        if (empty($category)) {
            show_404();
            return;
        }
        $this->data['category']  = $category;
        $this->data['parents']   = $this->category_model->get_dropdown($id);
        $this->data['title']     = translate('edit_category') ?: 'Edit Category';
        $this->data['sub_page']  = 'catalog/category/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('category', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('category'));
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

        $upload = $this->_upload_image('image', 'category');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('category/edit/' . encrypt_id($id)));
            return;
        }

        $name   = $this->input->post('name');
        $parent = $this->_clean_parent($this->input->post('parent_id'));
        $data = [
            'name'             => $name,
            'slug'             => $this->category_model->unique_slug($name, $id),
            'parent_id'        => ($parent == $id) ? null : $parent, // cannot be own parent
            'description'      => $this->input->post('description'),
            'icon'             => $this->input->post('icon'),
            'is_featured'      => $this->input->post('is_featured') ? 1 : 0,
            'sort_order'       => (int) $this->input->post('sort_order'),
            'status'           => $this->input->post('status') ?: 'Active',
            'meta_title'       => $this->input->post('meta_title'),
            'meta_description' => $this->input->post('meta_description'),
            'updated_by'       => get_loggedin_user_id(),
        ];
        if ($upload['file']) {
            $data['image'] = $upload['file'];
        }

        if ($this->category_model->update($id, $data)) {
            $this->log_activity('update', 'category', $id, 'Updated category: ' . $name);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('category'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('category', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->category_model->delete($id)) {
            $this->log_activity('delete', 'category', $id, 'Deleted category');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('category'));
    }

    public function status()
    {
        if (!get_permission('category', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->category_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'category', $id, 'Toggled category status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    // Server-side DataTables processing
    public function get_categories_server_side()
    {
        if (!get_permission('category', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [
            0 => 'c.id',
            2 => 'c.name',
            3 => 'c.slug',
            4 => 'parent_name',
            5 => 'c.sort_order',
            6 => 'c.status',
        ];
        $order_idx = $this->input->post('order')[0]['column'] ?? 2;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'c.name';

        $total = $this->category_model->count_all($status);
        $res   = $this->category_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('category', 'is_edit');
        $can_delete = get_permission('category', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $img_html = $row->image
                ? '<img class="rounded" src="' . base_url('uploads/catalog/category/' . $row->image) . '" width="40" height="40" />'
                : '<span class="text-muted"><i class="fas fa-folder"></i></span>';

            $data[] = [
                $i++,
                $img_html,
                html_escape($row->name),
                html_escape($row->slug),
                html_escape($row->parent_name ?: '—'),
                (int) $row->sort_order,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('category', $row->id, $can_edit, $can_delete),
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

    private function _clean_parent($value)
    {
        $value = (int) $value;
        return $value > 0 ? $value : null;
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
            'allowed_types' => 'jpg|jpeg|png|gif|webp',
            'max_size'      => 4096,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload($field)) {
            return ['ok' => true, 'file' => $this->upload->data('file_name')];
        }
        return ['ok' => false, 'error' => strip_tags($this->upload->display_errors('', ' '))];
    }
}

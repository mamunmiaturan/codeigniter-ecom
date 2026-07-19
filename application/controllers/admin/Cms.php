<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / CMS
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Cms.php
 *
 * Admin CRUD for static content pages. Permission module prefix: `cms`.
 */
class Cms extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cms_page_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('cms', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('cms_pages') ?: 'CMS Pages';
        $this->data['sub_page']  = 'cms/index';
        $this->data['main_menu'] = 'cms';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('cms', 'is_add')) {
            access_denied();
        }
        $this->data['page']      = null;
        $this->data['title']     = translate('add_page') ?: 'Add Page';
        $this->data['sub_page']  = 'cms/form';
        $this->data['main_menu'] = 'cms';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('cms', 'is_add')) {
            access_denied();
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $title = $this->input->post('title');
        $data = $this->_collect($title);
        $data['slug']       = $this->cms_page_model->unique_slug($this->input->post('slug') ?: $title);
        $data['created_by'] = get_loggedin_user_id();
        if ($this->cms_page_model->insert($data)) {
            $this->log_activity('create', 'cms', 0, 'Created page: ' . $title);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('cms'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('cms/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('cms', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $page = $id ? $this->cms_page_model->find($id) : null;
        if (!$page) {
            show_404();
            return;
        }
        $this->data['page']      = $page;
        $this->data['title']     = translate('edit_page') ?: 'Edit Page';
        $this->data['sub_page']  = 'cms/form';
        $this->data['main_menu'] = 'cms';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('cms', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id || !$this->cms_page_model->find($id)) {
            show_404();
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->edit($this->input->post('id'));
            return;
        }
        $title = $this->input->post('title');
        $data = $this->_collect($title);
        $data['slug']       = $this->cms_page_model->unique_slug($this->input->post('slug') ?: $title, $id);
        $data['updated_by'] = get_loggedin_user_id();
        if ($this->cms_page_model->update($id, $data)) {
            $this->log_activity('update', 'cms', $id, 'Updated page: ' . $title);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('cms'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('cms', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->cms_page_model->delete($id)) {
            $this->log_activity('delete', 'cms', $id, 'Deleted page');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('cms'));
    }

    public function status()
    {
        if (!get_permission('cms', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        $new = $id ? $this->cms_page_model->toggle_status($id) : false;
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'cms', $id, 'Toggled page status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function get_pages_server_side()
    {
        if (!get_permission('cms', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [0 => 'id', 2 => 'title', 3 => 'slug', 5 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 2;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'title';

        $total = $this->cms_page_model->count_all($status);
        $res   = $this->cms_page_model->datatable($search, $start, $length, $order_col, $order_dir, $status);
        $can_edit   = get_permission('cms', 'is_edit');
        $can_delete = get_permission('cms', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $data[] = [
                $i++,
                html_escape($row->title),
                '<a href="' . base_url('page/' . rawurlencode($row->slug)) . '" target="_blank">' . html_escape($row->slug) . ' <i class="fas fa-external-link-alt fa-xs"></i></a>',
                $row->show_in_footer ? '<span class="badge badge-info">' . (translate('footer') ?: 'Footer') . '</span>' : '<span class="text-muted">—</span>',
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('cms', $row->id, $can_edit, $can_delete),
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
    }

    private function _collect($title)
    {
        return [
            'title'            => $title,
            'content'          => $this->input->post('content'),
            'meta_title'       => $this->input->post('meta_title') ?: $title,
            'meta_description' => $this->input->post('meta_description'),
            'status'           => $this->input->post('status') ?: 'Active',
            'show_in_footer'   => $this->input->post('show_in_footer') ? 1 : 0,
            'sort_order'       => (int) $this->input->post('sort_order'),
        ];
    }
}

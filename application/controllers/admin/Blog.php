<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Blog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Blog.php
 *
 * Admin CRUD for blog / news posts. Permission module prefix: `blog`.
 * Thumbnails are stored in uploads/blog/ (only the generated filename is persisted).
 */
class Blog extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('blog_post_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('blog', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('blog') ?: 'Blog';
        $this->data['sub_page']  = 'blog/index';
        $this->data['main_menu'] = 'blog';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('blog', 'is_add')) {
            access_denied();
        }
        $this->data['post']      = null;
        $this->data['title']     = translate('add_post') ?: 'Add Post';
        $this->data['sub_page']  = 'blog/form';
        $this->data['main_menu'] = 'blog';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('blog', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('blog'));
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }

        $upload = $this->_upload_thumbnail('thumbnail');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('blog/create'));
            return;
        }

        $title = $this->input->post('title');
        $data = $this->_collect($title);
        $data['slug']       = $this->blog_post_model->unique_slug($this->input->post('slug') ?: $title);
        $data['created_by'] = get_loggedin_user_id();
        if ($upload['file']) {
            $data['thumbnail'] = $upload['file'];
        }

        $id = $this->blog_post_model->insert($data);
        if ($id) {
            $this->log_activity('create', 'blog', $id, 'Created post: ' . $title);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('blog'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('blog/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('blog', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $post = $id ? $this->blog_post_model->find($id) : null;
        if (!$post) {
            show_404();
            return;
        }
        $this->data['post']      = $post;
        $this->data['title']     = translate('edit_post') ?: 'Edit Post';
        $this->data['sub_page']  = 'blog/form';
        $this->data['main_menu'] = 'blog';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('blog', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('blog'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id || !$this->blog_post_model->find($id)) {
            show_404();
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->edit($this->input->post('id'));
            return;
        }

        $upload = $this->_upload_thumbnail('thumbnail');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('blog/edit/' . encrypt_id($id)));
            return;
        }

        $title = $this->input->post('title');
        $data = $this->_collect($title);
        $data['slug']       = $this->blog_post_model->unique_slug($this->input->post('slug') ?: $title, $id);
        $data['updated_by'] = get_loggedin_user_id();
        if ($upload['file']) {
            $data['thumbnail'] = $upload['file'];
        }

        if ($this->blog_post_model->update($id, $data)) {
            $this->log_activity('update', 'blog', $id, 'Updated post: ' . $title);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('blog'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('blog', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->blog_post_model->delete($id)) {
            $this->log_activity('delete', 'blog', $id, 'Deleted post');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('blog'));
    }

    public function status()
    {
        if (!get_permission('blog', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->blog_post_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'blog', $id, 'Toggled post status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    // Server-side DataTables processing
    public function get_blogs_server_side()
    {
        if (!get_permission('blog', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [0 => 'id', 1 => 'title', 2 => 'category', 3 => 'status', 4 => 'published_at'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 4;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'published_at';

        $total = $this->blog_post_model->count_all($status);
        $res   = $this->blog_post_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('blog', 'is_edit');
        $can_delete = get_permission('blog', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $title_html = html_escape($row->title);
            if (!empty($row->is_featured)) {
                $title_html .= ' <i class="fas fa-star text-warning fa-xs" data-toggle="tooltip" data-original-title="'
                    . (translate('featured') ?: 'Featured') . '"></i>';
            }
            $title_html = '<a href="' . base_url('blogs/' . rawurlencode($row->slug)) . '" target="_blank">'
                . $title_html . ' <i class="fas fa-external-link-alt fa-xs"></i></a>';

            $published = !empty($row->published_at)
                ? '<span title="' . html_escape($row->published_at) . '">' . html_escape(date('d M Y', strtotime($row->published_at))) . '</span>'
                : '<span class="text-muted">—</span>';

            $data[] = [
                $i++,
                $title_html,
                html_escape($row->category ?: '—'),
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                $published,
                catalog_row_actions('blog', $row->id, $can_edit, $can_delete),
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
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Draft,Active,Inactive]');
    }

    private function _collect($title)
    {
        $status       = $this->input->post('status') ?: 'Draft';
        $published_at = $this->_clean_datetime($this->input->post('published_at'));
        // An Active post with no explicit date publishes immediately.
        if ($published_at === null && $status === 'Active') {
            $published_at = date('Y-m-d H:i:s');
        }

        return [
            'title'            => $title,
            'excerpt'          => $this->input->post('excerpt'),
            'content'          => $this->input->post('content'),
            'category'         => $this->input->post('category') ?: null,
            'tags'             => $this->input->post('tags') ?: null,
            'status'           => $status,
            'is_featured'      => $this->input->post('is_featured') ? 1 : 0,
            'published_at'     => $published_at,
            'meta_title'       => $this->input->post('meta_title') ?: $title,
            'meta_description' => $this->input->post('meta_description'),
        ];
    }

    private function _clean_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function _upload_thumbnail($field)
    {
        if (empty($_FILES[$field]['name'])) {
            return ['ok' => true, 'file' => null];
        }
        $dir = FCPATH . 'uploads/blog/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $dir,
            'allowed_types' => 'png|jpg|jpeg|webp',
            'max_size'      => 4096,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload($field)) {
            return ['ok' => true, 'file' => $this->upload->data('file_name')];
        }
        return ['ok' => false, 'error' => strip_tags($this->upload->display_errors('', ' '))];
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Banners
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Banner.php
 *
 * Admin CRUD for storefront banners (sliders, promos, popups, announcements).
 * Permission module prefix: `banner`.
 */
class Banner extends Admin_Controller
{
    /** Allowed banner types (kept in one place for validation + dropdowns). */
    private $types = ['slider', 'promo', 'popup', 'announcement'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('banner_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('banner', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('banners') ?: 'Banners';
        $this->data['sub_page']  = 'banner/index';
        $this->data['main_menu'] = 'banner';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('banner', 'is_add')) {
            access_denied();
        }
        $this->data['banner']    = null;
        $this->data['types']     = $this->types;
        $this->data['title']     = translate('add_banner') ?: 'Add Banner';
        $this->data['sub_page']  = 'banner/form';
        $this->data['main_menu'] = 'banner';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('banner', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('banner'));
            return;
        }

        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }

        $upload = $this->_upload_image('image');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('banner/create'));
            return;
        }

        $data = $this->_collect();
        $data['created_by'] = get_loggedin_user_id();
        if ($upload['file']) {
            $data['image'] = $upload['file'];
        }

        $id = $this->banner_model->insert($data);
        if ($id) {
            $this->log_activity('create', 'banner', $id, 'Created banner: ' . ($data['title'] ?: $data['type']));
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('banner'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('banner/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('banner', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $banner = $id ? $this->banner_model->find($id) : null;
        if (empty($banner)) {
            show_404();
            return;
        }
        $this->data['banner']    = $banner;
        $this->data['types']     = $this->types;
        $this->data['title']     = translate('edit_banner') ?: 'Edit Banner';
        $this->data['sub_page']  = 'banner/form';
        $this->data['main_menu'] = 'banner';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('banner', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('banner'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id || !$this->banner_model->find($id)) {
            show_404();
            return;
        }

        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->edit($this->input->post('id'));
            return;
        }

        $upload = $this->_upload_image('image');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('banner/edit/' . encrypt_id($id)));
            return;
        }

        $data = $this->_collect();
        $data['updated_by'] = get_loggedin_user_id();
        if ($upload['file']) {
            $data['image'] = $upload['file'];
        }

        if ($this->banner_model->update($id, $data)) {
            $this->log_activity('update', 'banner', $id, 'Updated banner: ' . ($data['title'] ?: $data['type']));
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('banner'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('banner', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->banner_model->delete($id)) {
            $this->log_activity('delete', 'banner', $id, 'Deleted banner');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('banner'));
    }

    public function status()
    {
        if (!get_permission('banner', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->banner_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'banner', $id, 'Toggled banner status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    // Server-side DataTables processing
    public function get_banners_server_side()
    {
        if (!get_permission('banner', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $type   = $this->input->post('type') ?? '';
        if ($type !== '' && !in_array($type, $this->types, true)) {
            $type = '';
        }

        $columns_map = [0 => 'id', 2 => 'title', 3 => 'type', 4 => 'position', 5 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 4;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'position';

        $total = $this->banner_model->count_all($type);
        $res   = $this->banner_model->datatable($search, $start, $length, $order_col, $order_dir, $type);

        $can_edit   = get_permission('banner', 'is_edit');
        $can_delete = get_permission('banner', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $img_html = $row->image
                ? '<img class="rounded" src="' . base_url('uploads/banner/' . rawurlencode($row->image)) . '" width="64" height="36" style="object-fit:cover;" />'
                : '<span class="text-muted"><i class="fas fa-image"></i></span>';

            $data[] = [
                $i++,
                $img_html,
                html_escape($row->title ?: '—'),
                $this->_type_badge($row->type),
                (int) $row->position,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('banner', $row->id, $can_edit, $can_delete),
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
        $this->form_validation->set_rules('title', 'Title', 'trim|max_length[200]');
        $this->form_validation->set_rules('subtitle', 'Subtitle', 'trim|max_length[300]');
        $this->form_validation->set_rules('button_text', 'Button Text', 'trim|max_length[80]');
        $this->form_validation->set_rules('link_url', 'Link URL', 'trim|max_length[255]');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|in_list[' . implode(',', $this->types) . ']');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Active,Inactive]');
        $this->form_validation->set_rules('position', 'Position', 'trim|is_natural');
    }

    private function _collect()
    {
        $type = $this->input->post('type');
        if (!in_array($type, $this->types, true)) {
            $type = 'slider';
        }
        return [
            'title'       => $this->input->post('title') ?: null,
            'subtitle'    => $this->input->post('subtitle') ?: null,
            'link_url'    => $this->input->post('link_url') ?: null,
            'button_text' => $this->input->post('button_text') ?: null,
            'type'        => $type,
            'position'    => (int) $this->input->post('position'),
            'status'      => $this->input->post('status') ?: 'Active',
            'starts_at'   => $this->_clean_datetime($this->input->post('starts_at')),
            'ends_at'     => $this->_clean_datetime($this->input->post('ends_at')),
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

    private function _type_badge($type)
    {
        $map = [
            'slider'       => ['info', translate('slider') ?: 'Slider'],
            'promo'        => ['primary', translate('promo') ?: 'Promo'],
            'popup'        => ['warning', translate('popup') ?: 'Popup'],
            'announcement' => ['success', translate('announcement') ?: 'Announcement'],
        ];
        [$class, $label] = $map[$type] ?? ['secondary', ucfirst((string) $type)];
        return '<span class="badge badge-' . $class . '">' . html_escape($label) . '</span>';
    }

    private function _upload_image($field)
    {
        if (empty($_FILES[$field]['name'])) {
            return ['ok' => true, 'file' => null];
        }
        $dir = FCPATH . 'uploads/banner/';
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

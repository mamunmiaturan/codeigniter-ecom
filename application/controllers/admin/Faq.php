<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / FAQ
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Faq.php
 *
 * Admin CRUD for storefront FAQs. Permission module prefix: `faq`.
 */
class Faq extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('faq_model');
        $this->load->helper('catalog');
    }

    public function index()
    {
        if (!get_permission('faq', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('faqs') ?: 'FAQs';
        $this->data['sub_page']  = 'faq/index';
        $this->data['main_menu'] = 'faq';
        $this->load->view('layout/index', $this->data);
    }

    public function create()
    {
        if (!get_permission('faq', 'is_add')) {
            access_denied();
        }
        $this->data['faq']       = null;
        $this->data['title']     = translate('add_faq') ?: 'Add FAQ';
        $this->data['sub_page']  = 'faq/form';
        $this->data['main_menu'] = 'faq';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('faq', 'is_add')) {
            access_denied();
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $question = $this->input->post('question');
        $data = $this->_collect($question);
        $data['created_by'] = get_loggedin_user_id();
        if ($this->faq_model->insert($data)) {
            $this->log_activity('create', 'faq', 0, 'Created FAQ: ' . $question);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('faq'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('faq/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('faq', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $faq = $id ? $this->faq_model->find($id) : null;
        if (!$faq) {
            show_404();
            return;
        }
        $this->data['faq']       = $faq;
        $this->data['title']     = translate('edit_faq') ?: 'Edit FAQ';
        $this->data['sub_page']  = 'faq/form';
        $this->data['main_menu'] = 'faq';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('faq', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id || !$this->faq_model->find($id)) {
            show_404();
            return;
        }
        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->edit($this->input->post('id'));
            return;
        }
        $question = $this->input->post('question');
        $data = $this->_collect($question);
        $data['updated_by'] = get_loggedin_user_id();
        if ($this->faq_model->update($id, $data)) {
            $this->log_activity('update', 'faq', $id, 'Updated FAQ: ' . $question);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('faq'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('faq', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->faq_model->delete($id)) {
            $this->log_activity('delete', 'faq', $id, 'Deleted FAQ');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('faq'));
    }

    public function status()
    {
        if (!get_permission('faq', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        $new = $id ? $this->faq_model->toggle_status($id) : false;
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'faq', $id, 'Toggled FAQ status to ' . $new);
        return $this->jsonResponse(['status' => 'success', 'new_status' => $new, 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function get_faqs_server_side()
    {
        if (!get_permission('faq', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [0 => 'id', 1 => 'question', 2 => 'category', 3 => 'status'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 1;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'question';

        $total = $this->faq_model->count_all($status);
        $res   = $this->faq_model->datatable($search, $start, $length, $order_col, $order_dir, $status);
        $can_edit   = get_permission('faq', 'is_edit');
        $can_delete = get_permission('faq', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $data[] = [
                $i++,
                html_escape($row->question),
                $row->category ? html_escape($row->category) : '<span class="text-muted">—</span>',
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('faq', $row->id, $can_edit, $can_delete),
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
        $this->form_validation->set_rules('question', 'Question', 'trim|required|max_length[255]');
    }

    private function _collect($question)
    {
        return [
            'question'   => $question,
            'answer'     => $this->input->post('answer'),
            'category'   => $this->input->post('category') ?: null,
            'status'     => $this->input->post('status') ?: 'Active',
            'sort_order' => (int) $this->input->post('sort_order'),
        ];
    }
}

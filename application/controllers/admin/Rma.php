<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Returns (RMA)
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Rma.php
 *
 * Admin management of customer return requests. Permission prefix: `rma`.
 */
class Rma extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['return_model', 'order_model']);
    }

    public function index()
    {
        if (!get_permission('rma', 'is_view')) {
            access_denied();
        }
        $this->data['statuses']  = Return_model::STATUSES;
        $this->data['title']     = translate('returns') ?: 'Returns / RMA';
        $this->data['sub_page']  = 'rma/index';
        $this->data['main_menu'] = 'rma';
        $this->load->view('layout/index', $this->data);
    }

    public function view($hash = '')
    {
        if (!get_permission('rma', 'is_view')) {
            access_denied();
        }
        $id  = decrypt_id($hash);
        $req = $id ? $this->return_model->get($id) : null;
        if (!$req) {
            show_404();
            return;
        }
        $this->data['req']       = $req;
        $this->data['ritems']    = $this->return_model->get_items($id);
        $this->data['order']     = $this->order_model->find_order($req['order_id']);
        $this->data['statuses']  = Return_model::STATUSES;
        $this->data['title']     = 'RMA ' . $req['rma_number'];
        $this->data['sub_page']  = 'rma/view';
        $this->data['main_menu'] = 'rma';
        $this->load->view('layout/index', $this->data);
    }

    public function update_status()
    {
        if (!get_permission('rma', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id || !$this->return_model->get($id)) {
            show_404();
            return;
        }
        $status = (string) $this->input->post('status');
        $note   = trim((string) $this->input->post('admin_note')) ?: null;
        if (!$this->return_model->set_status($id, $status, $note)) {
            set_alert('error', translate('information_could_not_be_saved'));
        } else {
            $this->log_activity('update', 'rma', $id, 'Return status → ' . $status);
            set_alert('success', translate('information_has_been_updated_successfully'));
        }
        redirect(base_url('rma/view/' . encrypt_id($id)));
    }

    public function get_rma_server_side()
    {
        if (!get_permission('rma', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [0 => 'r.id', 1 => 'r.rma_number', 2 => 'o.order_number', 4 => 'r.status', 5 => 'r.created_at'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 5;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'r.created_at';

        $total = $this->return_model->count_all($status);
        $res   = $this->return_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $badges = ['requested' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'received' => 'primary', 'refunded' => 'success', 'cancelled' => 'secondary'];
        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $sb = $badges[$row->status] ?? 'secondary';
            $data[] = [
                $i++,
                '<a href="' . base_url('rma/view/' . encrypt_id($row->id)) . '"><strong>' . html_escape($row->rma_number) . '</strong></a>'
                    . '<br><span class="badge badge-' . ((isset($row->type) && $row->type === 'exchange') ? 'info' : 'secondary') . '">' . ucfirst(isset($row->type) ? $row->type : 'return') . '</span>',
                html_escape($row->order_number ?: '—') . '<br><small class="text-muted">' . html_escape($row->customer_name ?: '') . '</small>',
                html_escape($row->reason ?: '—'),
                '<span class="badge badge-' . $sb . '">' . ucfirst($row->status) . '</span>',
                time_ago($row->created_at),
                '<a href="' . base_url('rma/view/' . encrypt_id($row->id)) . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . (translate('view') ?: 'View') . '"><i class="far fa-eye"></i></a>',
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
}

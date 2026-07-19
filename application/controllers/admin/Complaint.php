<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : Complaint.php
 *
 * Admin inbox for customer complaints. Permission prefix: `complaint`.
 * Customers file complaints from the storefront; staff review them and can open
 * a support Ticket on a complaint to work it (create_ticket).
 */
class Complaint extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['complaint_model', 'support_ticket_model']);
    }

    public function index()
    {
        if (!get_permission('complaint', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('complaints') ?: 'Complaints';
        $this->data['sub_page']  = 'complaint/index';
        $this->data['main_menu'] = 'support';
        $this->load->view('layout/index', $this->data);
    }

    public function get_complaints_server_side()
    {
        if (!get_permission('complaint', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';

        $columns_map = [1 => 'subject', 2 => 'name', 4 => 'status', 5 => 'created_at'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 5;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'created_at';

        $total = $this->complaint_model->count_all();
        $res   = $this->complaint_model->datatable($search, $start, $length, $order_col, $order_dir);

        $can_delete = get_permission('complaint', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $ticket = $row->ticket_number
                ? '<span class="badge badge-info">' . html_escape($row->ticket_number) . '</span>'
                : '<span class="text-muted">&mdash;</span>';
            $actions = '<a href="' . base_url('complaint/view/' . encrypt_id($row->id)) . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . (translate('view') ?: 'View') . '"><i class="fas fa-eye"></i></a> ';
            if ($can_delete) {
                $actions .= btn_delete('complaint/delete/' . encrypt_id($row->id));
            }
            $data[] = [
                $i++,
                '<strong>' . html_escape($row->subject) . '</strong>',
                html_escape($row->name) . '<br><small class="text-muted">' . html_escape($row->email) . '</small>',
                $ticket,
                $this->_status_badge($row->status),
                time_ago($row->created_at),
                $actions,
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

    public function view($hash = '')
    {
        if (!get_permission('complaint', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $complaint = $id ? $this->complaint_model->find($id) : null;
        if (!$complaint) {
            show_404();
            return;
        }
        $ticket = $this->db->where('complaint_id', $id)->get('support_tickets')->row_array();
        $this->data['complaint'] = $complaint;
        $this->data['ticket']    = $ticket;
        $this->data['statuses']  = Complaint_model::STATUSES;
        $this->data['title']     = translate('complaint') ?: 'Complaint';
        $this->data['sub_page']  = 'complaint/view';
        $this->data['main_menu'] = 'support';
        $this->load->view('layout/index', $this->data);
    }

    public function status()
    {
        if (!get_permission('complaint', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        $status = (string) $this->input->post('status');
        if (!$id || !$this->complaint_model->set_status($id, $status)) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'complaint', $id, 'Complaint status -> ' . $status);
        return $this->jsonResponse(['status' => 'success', 'message' => translate('information_has_been_updated_successfully')]);
    }

    /** Open a support ticket ON this complaint, then jump to the ticket thread. */
    public function create_ticket($hash = '')
    {
        if (!get_permission('ticket', 'is_add')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $complaint = $id ? $this->complaint_model->find($id) : null;
        if (!$complaint) {
            show_404();
            return;
        }
        $ticket_id = $this->support_ticket_model->open_from_complaint($complaint, get_loggedin_user_id());
        if ($ticket_id) {
            $this->complaint_model->set_status($id, 'Under Review');
            $this->log_activity('create', 'ticket', $ticket_id, 'Opened ticket from complaint #' . $id);
            set_alert('success', translate('ticket_created_successfully') ?: 'Ticket created.');
            redirect(base_url('ticket/view/' . encrypt_id($ticket_id)));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('complaint/view/' . $hash));
    }

    public function delete($hash = '')
    {
        if (!get_permission('complaint', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if ($id && $this->complaint_model->delete($id)) {
            $this->log_activity('delete', 'complaint', $id, 'Deleted complaint');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('complaint'));
    }

    private function _status_badge($status)
    {
        $map = ['New' => 'danger', 'Under Review' => 'warning', 'Resolved' => 'success', 'Closed' => 'secondary'];
        $cls = $map[$status] ?? 'secondary';
        return '<span class="badge badge-' . $cls . '">' . html_escape($status) . '</span>';
    }
}

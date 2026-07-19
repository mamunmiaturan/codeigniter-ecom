<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : Ticket.php
 *
 * Admin management of support tickets. Permission prefix: `ticket`.
 * Tickets are opened on a customer complaint (Complaint::create_ticket); staff
 * and the customer then converse via the reply thread until Closed.
 */
class Ticket extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['support_ticket_model', 'complaint_model']);
    }

    public function index()
    {
        if (!get_permission('ticket', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('tickets') ?: 'Tickets';
        $this->data['sub_page']  = 'ticket/index';
        $this->data['main_menu'] = 'support';
        $this->load->view('layout/index', $this->data);
    }

    public function get_tickets_server_side()
    {
        if (!get_permission('ticket', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';

        $columns_map = [1 => 'ticket_number', 2 => 'subject', 4 => 'priority', 5 => 'status', 6 => 'created_at'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 6;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'created_at';

        $total = $this->support_ticket_model->count_all();
        $res   = $this->support_ticket_model->datatable($search, $start, $length, $order_col, $order_dir);

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $actions = '<a href="' . base_url('ticket/view/' . encrypt_id($row->id)) . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . (translate('view') ?: 'View') . '"><i class="fas fa-eye"></i></a> ';
            if (get_permission('ticket', 'is_delete')) {
                $actions .= btn_delete('ticket/delete/' . encrypt_id($row->id));
            }
            $data[] = [
                $i++,
                '<strong>' . html_escape($row->ticket_number) . '</strong>',
                html_escape($row->subject),
                html_escape($row->customer_name ?: '—'),
                $this->_priority_badge($row->priority),
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
        if (!get_permission('ticket', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $ticket = $id ? $this->support_ticket_model->find($id) : null;
        if (!$ticket) {
            show_404();
            return;
        }
        $this->data['ticket']     = $ticket;
        $this->data['replies']    = $this->support_ticket_model->replies($id);
        $this->data['statuses']   = Support_ticket_model::STATUSES;
        $this->data['priorities'] = Support_ticket_model::PRIORITIES;
        $this->data['title']      = ($ticket['ticket_number'] ?? 'Ticket');
        $this->data['sub_page']   = 'ticket/view';
        $this->data['main_menu']  = 'support';
        $this->load->view('layout/index', $this->data);
    }

    public function reply()
    {
        if (!get_permission('ticket', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('id'));
        $message = trim((string) $this->input->post('message'));
        $ticket = $id ? $this->support_ticket_model->find($id) : null;
        if (!$ticket || $message === '') {
            set_alert('error', translate('information_could_not_be_saved'));
            redirect(base_url('ticket/view/' . $this->input->post('id')));
            return;
        }
        $admin_name = function_exists('get_loggedin_name') ? (get_loggedin_name() ?: 'Support') : 'Support';
        $this->support_ticket_model->add_reply($id, 'admin', get_loggedin_user_id(), $admin_name, $message);
        $this->log_activity('update', 'ticket', $id, 'Replied to ticket');
        set_alert('success', translate('reply_sent_successfully') ?: 'Reply sent.');
        redirect(base_url('ticket/view/' . encrypt_id($id)));
    }

    public function status()
    {
        if (!get_permission('ticket', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        $status = (string) $this->input->post('status');
        if (!$id || !$this->support_ticket_model->set_status($id, $status)) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'ticket', $id, 'Ticket status -> ' . $status);
        return $this->jsonResponse(['status' => 'success', 'message' => translate('information_has_been_updated_successfully')]);
    }

    public function delete($hash = '')
    {
        if (!get_permission('ticket', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if ($id && $this->support_ticket_model->delete($id)) {
            $this->log_activity('delete', 'ticket', $id, 'Deleted ticket');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('ticket'));
    }

    private function _status_badge($status)
    {
        $map = ['Open' => 'danger', 'In Progress' => 'warning', 'Answered' => 'info', 'Closed' => 'secondary'];
        $cls = $map[$status] ?? 'secondary';
        return '<span class="badge badge-' . $cls . '">' . html_escape($status) . '</span>';
    }

    private function _priority_badge($priority)
    {
        $map = ['Low' => 'secondary', 'Medium' => 'info', 'High' => 'danger'];
        $cls = $map[$priority] ?? 'secondary';
        return '<span class="badge badge-' . $cls . '">' . html_escape($priority) . '</span>';
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Contact.php
 *
 * Admin inbox for storefront "Contact Us" submissions. Permission prefix:
 * `contact`. Customers submit via the storefront (Landing::submit_contact),
 * so this is a read/view/reply/close/delete surface only — no admin create.
 */
class Contact extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['contact_message_model', 'email_model']);
    }

    public function index()
    {
        if (!get_permission('contact', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('contact_messages') ?: 'Contact Messages';
        $this->data['sub_page']  = 'contact/index';
        $this->data['main_menu'] = 'contact';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Message detail + reply form. Opening a New message marks it Read.
     */
    public function view($hash = '')
    {
        if (!get_permission('contact', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $message = $id ? $this->contact_message_model->find($id) : null;
        if (!$message) {
            show_404();
            return;
        }
        // Opening an unread message marks it Read (never downgrades Replied/Closed).
        if ($message['status'] === 'New' && $this->contact_message_model->mark_read($id)) {
            $message['status'] = 'Read';
        }
        $this->data['message']   = $message;
        $this->data['statuses']  = Contact_message_model::STATUSES;
        $this->data['title']     = translate('contact_message') ?: 'Contact Message';
        $this->data['sub_page']  = 'contact/view';
        $this->data['main_menu'] = 'contact';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Save the admin reply (status -> Replied) and best-effort email the customer.
     */
    public function reply()
    {
        if (!get_permission('contact', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('id'));
        $message = $id ? $this->contact_message_model->find($id) : null;
        if (!$message) {
            show_404();
            return;
        }
        $reply = trim((string) $this->input->post('reply'));
        if ($reply === '') {
            set_alert('error', translate('reply_message_is_required') ?: 'Reply message is required.');
            redirect(base_url('contact/view/' . encrypt_id($id)));
            return;
        }

        if ($this->contact_message_model->reply($id, $reply, (int) get_loggedin_user_id())) {
            $this->log_activity('update', 'contact', $id, 'Replied to contact message from ' . $message['name']);

            // Best-effort customer email — a delivery failure must never block the reply.
            $email = trim((string) ($message['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $site    = get_global_setting('site_name') ?: 'Our Store';
                    $subject = 'Re: ' . ($message['subject'] ?: 'Your message to ' . $site);
                    $body  = '<p>Hi ' . html_escape($message['name']) . ',</p>';
                    $body .= '<p>Thank you for contacting ' . html_escape($site) . '. Here is our reply:</p>';
                    $body .= '<blockquote style="border-left:3px solid #ddd;padding-left:12px;color:#444;">' . nl2br(html_escape($reply)) . '</blockquote>';
                    if (!empty($message['message'])) {
                        $body .= '<p style="color:#888;font-size:13px;">Your original message:<br><em>' . nl2br(html_escape($message['message'])) . '</em></p>';
                    }
                    $body .= '<p>Best regards,<br>' . html_escape($site) . '</p>';
                    $this->email_model->sendMail($email, $subject, $body);
                } catch (\Throwable $e) {
                    log_message('error', 'Contact reply email failed: ' . $e->getMessage());
                }
            }
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('contact/view/' . encrypt_id($id)));
    }

    /**
     * Set a message to a specific workflow status (New/Read/Replied/Closed).
     */
    public function status()
    {
        if (!get_permission('contact', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($this->input->post('id'));
        $message = $id ? $this->contact_message_model->find($id) : null;
        if (!$message) {
            show_404();
            return;
        }
        $status = (string) $this->input->post('status');
        if (!in_array($status, Contact_message_model::STATUSES, true)) {
            set_alert('error', translate('information_could_not_be_saved'));
            redirect(base_url('contact/view/' . encrypt_id($id)));
            return;
        }
        if ($this->contact_message_model->set_status($id, $status)) {
            $this->log_activity('status', 'contact', $id, 'Contact message #' . $id . ' status → ' . $status);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('contact/view/' . encrypt_id($id)));
    }

    public function delete($hash = '')
    {
        if (!get_permission('contact', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        $message = $id ? $this->contact_message_model->find($id) : null;
        if (!$message) {
            show_404();
            return;
        }
        if ($this->contact_message_model->delete($id)) {
            $this->log_activity('delete', 'contact', $id, 'Deleted contact message from ' . $message['name']);
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('contact'));
    }

    public function get_contacts_server_side()
    {
        if (!get_permission('contact', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';
        if (!in_array($status, Contact_message_model::STATUSES, true)) {
            $status = '';
        }

        $columns_map = [1 => 'name', 2 => 'subject', 3 => 'status', 4 => 'created_at'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 4;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'created_at';

        $total = $this->contact_message_model->count_all($status);
        $res   = $this->contact_message_model->datatable($search, $start, $length, $order_col, $order_dir, $status);
        $can_delete = get_permission('contact', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $view_url = base_url('contact/view/' . encrypt_id($row->id));
            $name = '<a href="' . $view_url . '"><strong>' . html_escape($row->name) . '</strong></a>';
            if (!empty($row->email)) {
                $name .= '<br><small class="text-muted">' . html_escape($row->email) . '</small>';
            }
            $actions = '<a href="' . $view_url . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . (translate('view') ?: 'View') . '"><i class="far fa-eye"></i></a> ';
            if ($can_delete) {
                $actions .= btn_delete('contact/delete/' . encrypt_id($row->id));
            }
            $data[] = [
                $i++,
                $name,
                html_escape($row->subject ?: '—'),
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

    private function _status_badge($status)
    {
        $map = ['New' => 'danger', 'Read' => 'info', 'Replied' => 'success', 'Closed' => 'secondary'];
        $cls = $map[$status] ?? 'secondary';
        return '<span class="badge badge-' . $cls . '">' . html_escape(translate(strtolower($status)) ?: $status) . '</span>';
    }
}

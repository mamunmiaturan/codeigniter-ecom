<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Notification extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('notification_model');
    }

    public function index()
    {
        if (!get_permission('notifications', 'is_view')) {
            access_denied();
        }

        $user_id = get_loggedin_user_id();
        $role_id = loggedin_role_id();

        $f_status    = $this->input->get('status')    ?: '';
        $f_search    = $this->input->get('search')    ?: '';
        $f_date_from = $this->input->get('date_from') ?: '';
        $f_date_to   = $this->input->get('date_to')   ?: '';

        $notifications = $this->notification_model->get_notifications($user_id, $role_id);

        if ($f_status === 'read') {
            $notifications = array_filter($notifications, function ($r) { return (int)$r['is_read'] === 1; });
        } elseif ($f_status === 'unread') {
            $notifications = array_filter($notifications, function ($r) { return (int)$r['is_read'] === 0; });
        }
        if ($f_search) {
            $notifications = array_filter($notifications, function ($r) use ($f_search) {
                return stripos($r['title'] ?? '', $f_search) !== false
                    || stripos($r['message'] ?? '', $f_search) !== false;
            });
        }
        if ($f_date_from) {
            $notifications = array_filter($notifications, function ($r) use ($f_date_from) {
                return date('Y-m-d', strtotime($r['created_at'])) >= $f_date_from;
            });
        }
        if ($f_date_to) {
            $notifications = array_filter($notifications, function ($r) use ($f_date_to) {
                return date('Y-m-d', strtotime($r['created_at'])) <= $f_date_to;
            });
        }

        $this->data['notifications'] = array_values($notifications);
        $this->data['filters']       = compact('f_status', 'f_search', 'f_date_from', 'f_date_to');
        $this->data['title']         = translate('notifications');
        $this->data['sub_page']      = 'notification/index';
        $this->data['main_menu']     = 'notifications';
        $this->load->view('layout/index', $this->data);
    }

    public function get_unread_notifications()
    {
        if (!get_permission('notifications', 'is_view')) {
            return $this->jsonResponse(['count' => 0, 'notifications' => []]);
        }

        $user_id = get_loggedin_user_id();
        $notifications = $this->notification_model->get_unread_notifications($user_id);
        
        return $this->jsonResponse([
            'count' => count($notifications),
            'notifications' => $notifications
        ]);
    }

    public function mark_notifications_as_read()
    {
        if (!get_permission('notifications', 'is_view')) {
            return $this->jsonResponse(['success' => false], 403);
        }

        $user_id = get_loggedin_user_id();
        $this->notification_model->mark_all_as_read($user_id);
        return $this->jsonResponse(['success' => true]);
    }

    public function mark_single_as_read($id = '')
    {
        if (!get_permission('notifications', 'is_view')) {
            access_denied();
        }

        $user_id = get_loggedin_user_id();
        $this->notification_model->mark_single_as_read($id, $user_id);

        if ($this->input->is_ajax_request()) {
            return $this->jsonResponse(['success' => true]);
        } else {
            redirect(base_url('notification'));
        }
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Smslog.php
 */

class Smslog extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('sms_model');
    }

    //--------------- SMS LOGS -------------------->
    public function index()
    {
        if (!get_permission('sms_logs', 'is_view')) {
            access_denied();
        }

        $this->data['date_from'] = trim($this->input->get('date_from') ?: '');
        $this->data['date_to']   = trim($this->input->get('date_to')   ?: '');
        $this->data['status']    = trim($this->input->get('status')    ?: '');
        $this->data['title']     = translate('sms_logs');
        $this->data['sub_page']  = 'audit/sms/log/index';
        $this->data['main_menu'] = 'audit';
        $this->load->view('layout/index', $this->data);
    }

    // Server-side processing for SMS Logs
    public function get_sms_logs_server_side()
    {
        if (!get_permission('sms_logs', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw   = (int) $this->input->post('draw');
        $start  = (int) $this->input->post('start');
        $length = (int) $this->input->post('length');
        $search = $this->input->post('search')['value'] ?? '';

        $order_col_idx = (int) ($this->input->post('order')[0]['column'] ?? 5);
        $order_dir     = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'], true)) {
            $order_dir = 'desc';
        }

        $columns_map = [
            0 => 'sms_logs.id',
            1 => 'users.name',
            2 => 'sms_logs.mobile_no',
            3 => 'sms_logs.sms_text',
            4 => 'sms_logs.status',
            5 => 'sms_logs.created_at',
            6 => 'creator.name',
        ];

        $order_col = $columns_map[$order_col_idx] ?? 'sms_logs.created_at';

        $date_from = trim($this->input->post('date_from') ?: '');
        $date_to   = trim($this->input->post('date_to')   ?: '');
        $f_status  = trim($this->input->post('filter_status') ?: '');

        $total_records = $this->sms_model->get_sms_logs_count(
            $date_from ?: null, $date_to ?: null, $f_status ?: null
        );

        $server_side_result = $this->sms_model->get_sms_logs_server_side_data(
            $search, $start, $length, $order_col, $order_dir,
            $date_from ?: null, $date_to ?: null, $f_status ?: null
        );
        $total_filtered     = $server_side_result['total_filtered'];
        $logs               = $server_side_result['data'];

        $data = [];
        $i    = $start + 1;

        foreach ($logs as $row) {
            $status = (string) ($row['status'] ?? '');
            $label  = 'default';
            if ($status === 'Success') {
                $label = 'success';
            } elseif ($status === 'Pending') {
                $label = 'warning';
            } elseif (stripos($status, 'Failed') !== false) {
                $label = 'danger';
            }

            $status_html = '<span class="label label-' . $label . '">' . html_escape($status) . '</span>';

            $created_at = $row['created_at'] ?? '';
            $date_html  = '-';
            if (!empty($created_at)) {
                $ts = strtotime($created_at);
                $date_html = time_ago($created_at);
            }

            $full_msg  = (string) ($row['sms_text'] ?? '');
            $short_msg = strlen($full_msg) > 60 ? substr($full_msg, 0, 60) . '...' : $full_msg;
            $msg_html  = '<span class="btn-view-msg" data-msg="' . htmlspecialchars($full_msg, ENT_QUOTES, 'UTF-8') . '">'
                       . html_escape($short_msg) . '</span>';

            $data[] = [
                $i++,
                html_escape($row['user_name'] ?? 'Unknown'),
                html_escape($row['mobile_no'] ?? '-'),
                $msg_html,
                $status_html,
                $date_html,
                html_escape($row['creator_name'] ?? 'System'),
            ];
        }

        return $this->jsonResponse([
            'draw'            => $draw,
            'recordsTotal'    => $total_records,
            'recordsFiltered' => $total_filtered,
            'data'            => $data,
        ]);
    }
}

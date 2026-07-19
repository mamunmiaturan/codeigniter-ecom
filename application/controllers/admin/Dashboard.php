<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Dashboard.php
 */

class Dashboard extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('activity_logs_model');
        // order_model is loaded so its Order_model::STATUSES constant is available
        // to Dashboard_model::orders_by_status().
        $this->load->model(['dashboard_model', 'order_model']);
    }

    public function index()
    {
        $this->data['title']        = translate('dashboard');
        $stats = $this->user_model->get_dashboard_stats();
        $this->data['total_users']           = $stats['total_users'];
        $this->data['active_users']          = $stats['active_users'];
        $this->data['inactive_users']        = $stats['inactive_users'];
        $this->data['this_month_new_users']  = $stats['this_month_new_users'];

        // Commerce analytics
        $this->data['sales']            = $this->dashboard_model->sales_summary();
        $this->data['orders_by_status'] = $this->dashboard_model->orders_by_status();
        $this->data['top_products']     = $this->dashboard_model->top_products(5);
        $this->data['low_stock']        = $this->dashboard_model->low_stock_products(10);
        $this->data['low_stock_count']  = $this->dashboard_model->low_stock_count();
        $this->data['recent_orders']    = $this->dashboard_model->recent_orders(8);
        $this->data['trend']            = $this->dashboard_model->daily_trend(14);
        $this->data['currency_symbol']  = get_global_setting('currency_symbol') ?: '৳';

        $this->data['sub_page']    = 'dashboard/index';
        $this->data['main_menu']   = 'dashboard';
        $this->load->view('layout/index', $this->data);
    }

    public function get_live_logs()
    {
        return $this->jsonResponse([
            'status' => 'success',
            'data'   => $this->_format_logs($this->_get_recent_logs()),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Fetch the 15 most recent activity log rows.
     * Reads from the DB when rows exist; falls back to the flat JSON files
     * so the dashboard is never blank before the async queue worker has run.
     */
    private function _get_recent_logs(): array
    {
        $rows = $this->activity_logs_model->get_recent(15);
        if (!empty($rows)) {
            return $rows;
        }
        // Flat-file fallback (populated synchronously on every request)
        return $this->_read_json_logs(15);
    }

    private function _format_logs(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'user'        => $row['user_name'] ?? 'System',
                'action'      => $row['action']      ?? 'activity',
                'description' => $row['description'] ?? '',
                'time'        => isset($row['created_at'])
                    ? time_ago($row['created_at'])
                    : date('H:i:s'),
            ];
        }
        return $out;
    }

    private function _read_json_logs(int $limit): array
    {
        $logs    = [];
        $log_dir = APPPATH . 'logs/activity/';
        if (!is_dir($log_dir)) {
            return $logs;
        }
        $files = glob($log_dir . '*.json');
        rsort($files);
        foreach ($files as $file) {
            foreach (array_reverse(file($file) ?: []) as $line) {
                $entry = json_decode($line, true);
                if ($entry) {
                    $logs[] = $entry;
                    if (count($logs) >= $limit) {
                        return $logs;
                    }
                }
            }
        }
        return $logs;
    }
}

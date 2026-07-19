<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Reports & Analytics
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Report.php
 *
 * Read-only admin reporting surface. Permission prefix: `report` (view only).
 *
 * Every dated page takes an optional GET date range (from / to, 'Y-m-d'),
 * defaulting to the last 30 days. Inventory is a point-in-time snapshot and
 * takes a low-stock `threshold` instead. export/{type} streams the matching
 * report as a CSV download (mirrors Newsletter::export()).
 */
class Report extends Admin_Controller
{
    /** Report types that may be exported as CSV. */
    private $export_types = ['sales', 'products', 'customers', 'inventory', 'payments'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('report_model');
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    /** Overview: global date range + headline summary + links to each report. */
    public function index()
    {
        if (!get_permission('report', 'is_view')) {
            access_denied();
        }
        list($from, $to) = $this->_range();
        $this->data['from']     = $from;
        $this->data['to']       = $to;
        $this->data['summary']  = $this->report_model->sales_summary($from, $to);
        $this->data['currency'] = get_global_setting('currency_symbol') ?: '৳';
        $this->_render('report/index', translate('reports') ?: 'Reports & Analytics');
    }

    /** Sales report: per-day revenue + payment/status breakdowns. */
    public function sales()
    {
        if (!get_permission('report', 'is_view')) {
            access_denied();
        }
        list($from, $to) = $this->_range();
        $this->data['from']     = $from;
        $this->data['to']       = $to;
        $this->data['summary']  = $this->report_model->sales_summary($from, $to);
        $this->data['rows']     = $this->report_model->sales_by_day($from, $to);
        $this->data['currency'] = get_global_setting('currency_symbol') ?: '৳';
        $this->_render('report/sales', translate('sales_report') ?: 'Sales Report');
    }

    /** Top products by units sold for the range. */
    public function products()
    {
        if (!get_permission('report', 'is_view')) {
            access_denied();
        }
        list($from, $to) = $this->_range();
        $this->data['from']     = $from;
        $this->data['to']       = $to;
        $this->data['rows']     = $this->report_model->top_products($from, $to, 100);
        $this->data['currency'] = get_global_setting('currency_symbol') ?: '৳';
        $this->_render('report/products', translate('products_report') ?: 'Products Report');
    }

    /** Top customers by spend for the range + new-registration count. */
    public function customers()
    {
        if (!get_permission('report', 'is_view')) {
            access_denied();
        }
        list($from, $to) = $this->_range();
        $this->data['from']          = $from;
        $this->data['to']            = $to;
        $this->data['rows']          = $this->report_model->customers_report($from, $to, 100);
        $this->data['new_customers'] = $this->report_model->new_customers_count($from, $to);
        $this->data['currency']      = get_global_setting('currency_symbol') ?: '৳';
        $this->_render('report/customers', translate('customers_report') ?: 'Customers Report');
    }

    /** Inventory snapshot: low-stock list (by threshold) + full valuation. */
    public function inventory()
    {
        if (!get_permission('report', 'is_view')) {
            access_denied();
        }
        $threshold = $this->input->get('threshold', true);
        $threshold = ($threshold !== null && $threshold !== '' && is_numeric($threshold))
            ? max(0, (int) $threshold)
            : Report_model::LOW_STOCK_THRESHOLD;

        $valuation = $this->report_model->inventory_valuation();
        $this->data['threshold']       = $threshold;
        $this->data['low_stock']       = $this->report_model->low_stock($threshold);
        $this->data['valuation']       = $valuation['rows'];
        $this->data['valuation_total'] = $valuation['total'];
        $this->data['currency']        = get_global_setting('currency_symbol') ?: '৳';
        $this->_render('report/inventory', translate('inventory_report') ?: 'Inventory Report');
    }

    /** Payments report: breakdown by method x status for the range. */
    public function payments()
    {
        if (!get_permission('report', 'is_view')) {
            access_denied();
        }
        list($from, $to) = $this->_range();
        $this->data['from']     = $from;
        $this->data['to']       = $to;
        $this->data['rows']     = $this->report_model->payments_report($from, $to);
        $this->data['currency'] = get_global_setting('currency_symbol') ?: '৳';
        $this->_render('report/payments', translate('payments_report') ?: 'Payments Report');
    }

    // -------------------------------------------------------------------------
    // CSV export
    // -------------------------------------------------------------------------

    /**
     * Stream one report as a CSV download. $type is validated against an
     * allowlist; dated reports honour the GET from/to range.
     */
    public function export($type = '')
    {
        if (!get_permission('report', 'is_view')) {
            access_denied();
        }
        $type = strtolower(trim((string) $type));
        if (!in_array($type, $this->export_types, true)) {
            show_404();
            return;
        }
        list($from, $to) = $this->_range();

        $header = [];
        $rows   = [];
        switch ($type) {
            case 'sales':
                $header = ['date', 'orders', 'revenue'];
                foreach ($this->report_model->sales_by_day($from, $to) as $r) {
                    $rows[] = [$r['date'], (int) $r['orders'], $this->_money($r['revenue'])];
                }
                break;

            case 'products':
                $header = ['product_id', 'product_name', 'units', 'revenue'];
                foreach ($this->report_model->top_products($from, $to, 1000) as $r) {
                    $rows[] = [(int) $r['product_id'], $r['product_name'], (int) $r['units'], $this->_money($r['revenue'])];
                }
                break;

            case 'customers':
                $header = ['name', 'email', 'orders', 'spend'];
                foreach ($this->report_model->customers_report($from, $to, 1000) as $r) {
                    $rows[] = [$r['name'], $r['email'], (int) $r['orders'], $this->_money($r['spend'])];
                }
                break;

            case 'inventory':
                $header = ['sku', 'name', 'stock_quantity', 'stock_status', 'price', 'stock_value'];
                foreach ($this->report_model->inventory_valuation()['rows'] as $r) {
                    $rows[] = [$r['sku'], $r['name'], (int) $r['stock_quantity'], $r['stock_status'], $this->_money($r['price']), $this->_money($r['stock_value'])];
                }
                break;

            case 'payments':
                $header = ['payment_method', 'payment_status', 'orders', 'amount'];
                foreach ($this->report_model->payments_report($from, $to) as $r) {
                    $rows[] = [$r['payment_method'], $r['payment_status'], (int) $r['orders'], $this->_money($r['amount'])];
                }
                break;
        }

        $this->log_activity('export', 'report', 0, 'Exported ' . $type . ' report (' . count($rows) . ' rows) to CSV');

        $filename = 'report_' . $type . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        fputcsv($output, $header, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        fclose($output);
        exit;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the GET date range, defaulting to the last 30 days (inclusive of
     * today). Invalid input falls back to the default; an inverted range is
     * swapped. Returns [from, to] as 'Y-m-d' strings.
     */
    private function _range()
    {
        $to   = $this->_valid_date($this->input->get('to', true))   ?: date('Y-m-d');
        $from = $this->_valid_date($this->input->get('from', true)) ?: date('Y-m-d', strtotime('-29 days'));
        if (strtotime($from) > strtotime($to)) {
            $tmp = $from;
            $from = $to;
            $to = $tmp;
        }
        return [$from, $to];
    }

    /** Strictly validate a 'Y-m-d' date; return the value or '' if malformed. */
    private function _valid_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $d = DateTime::createFromFormat('Y-m-d', $value);
        return ($d && $d->format('Y-m-d') === $value) ? $value : '';
    }

    /** Format a money value for CSV (plain 2dp, no thousands separator). */
    private function _money($value)
    {
        return number_format((float) $value, 2, '.', '');
    }

    /** Load a report sub-view inside the admin layout. */
    private function _render($sub_page, $title)
    {
        $this->data['title']     = $title;
        $this->data['sub_page']  = $sub_page;
        $this->data['main_menu'] = 'report';
        $this->load->view('layout/index', $this->data);
    }
}

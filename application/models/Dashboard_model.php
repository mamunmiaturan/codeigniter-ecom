<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Dashboard
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Dashboard_model.php
 *
 * Commerce analytics for the admin dashboard: revenue, order counts,
 * top sellers and low-stock products. All figures are read straight off
 * the `orders` / `order_items` / `products` tables (guest-safe — uses the
 * snapshot columns on the order row, so no user join is required).
 */
class Dashboard_model extends MY_Model
{
    /** Active products at or below this on-hand qty are treated as "low stock". */
    const LOW_STOCK_THRESHOLD = 5;

    /** Order statuses that do NOT count toward booked revenue. */
    private $non_revenue_statuses = ['cancelled', 'returned'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Headline sales figures: total / today / this-month revenue, amount
     * actually collected, order counts, and average order value.
     */
    public function sales_summary(): array
    {
        $today       = date('Y-m-d');
        $month_start = date('Y-m-01 00:00:00');

        $revenue_total = $this->_revenue_sum();
        $revenue_today = $this->_revenue_sum(['DATE(created_at)' => $today]);
        $revenue_month = $this->_revenue_sum(['created_at >=' => $month_start]);

        // Cash actually collected (paid orders only).
        $revenue_paid = (float) $this->db
            ->select('COALESCE(SUM(total),0) AS v', false)
            ->where('payment_status', 'paid')
            ->get('orders')->row()->v;

        $orders_total = (int) $this->db->count_all('orders');
        $orders_today = (int) $this->db->where('DATE(created_at)', $today)->count_all_results('orders');
        $orders_month = (int) $this->db->where('created_at >=', $month_start)->count_all_results('orders');

        $revenue_orders = (int) $this->db
            ->where_not_in('status', $this->non_revenue_statuses)
            ->count_all_results('orders');
        $aov = $revenue_orders > 0 ? $revenue_total / $revenue_orders : 0.0;

        return [
            'revenue_total' => $revenue_total,
            'revenue_today' => $revenue_today,
            'revenue_month' => $revenue_month,
            'revenue_paid'  => $revenue_paid,
            'orders_total'  => $orders_total,
            'orders_today'  => $orders_today,
            'orders_month'  => $orders_month,
            'aov'           => $aov,
        ];
    }

    /** SUM(total) of revenue-counting orders, with optional extra WHERE conditions. */
    private function _revenue_sum(array $extra = []): float
    {
        $this->db->select('COALESCE(SUM(total),0) AS v', false)
                 ->where_not_in('status', $this->non_revenue_statuses);
        foreach ($extra as $k => $v) {
            $this->db->where($k, $v);
        }
        return (float) $this->db->get('orders')->row()->v;
    }

    /** Order counts keyed by every known status (zero-filled). */
    public function orders_by_status(): array
    {
        $counts = array_fill_keys(Order_model::STATUSES, 0);
        $rows = $this->db->select('status, COUNT(*) AS cnt', false)
                         ->group_by('status')
                         ->get('orders')->result_array();
        foreach ($rows as $r) {
            if (array_key_exists($r['status'], $counts)) {
                $counts[$r['status']] = (int) $r['cnt'];
            }
        }
        return $counts;
    }

    /** Top-selling products by units sold (excludes cancelled/returned orders). */
    public function top_products(int $limit = 5): array
    {
        // MAX(product_name) keeps the query valid under MySQL ONLY_FULL_GROUP_BY
        // (only product_id is in GROUP BY).
        return $this->db
            ->select('oi.product_id, MAX(oi.product_name) AS product_name, SUM(oi.quantity) AS units, SUM(oi.line_total) AS revenue', false)
            ->from('order_items oi')
            ->join('orders o', 'o.id = oi.order_id')
            ->where_not_in('o.status', $this->non_revenue_statuses)
            ->group_by('oi.product_id')
            ->order_by('units', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    /** Active products at or below the low-stock threshold. */
    public function low_stock_products(int $limit = 10, ?int $threshold = null): array
    {
        $threshold = $threshold ?? self::LOW_STOCK_THRESHOLD;
        return $this->db
            ->select('id, name, sku, stock_quantity, stock_status', false)
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->where('stock_quantity <=', $threshold)
            ->order_by('stock_quantity', 'ASC')
            ->limit($limit)
            ->get('products')->result_array();
    }

    /** How many active products are currently low on stock. */
    public function low_stock_count(?int $threshold = null): int
    {
        $threshold = $threshold ?? self::LOW_STOCK_THRESHOLD;
        return (int) $this->db
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->where('stock_quantity <=', $threshold)
            ->count_all_results('products');
    }

    /** Most recent orders (guest-safe; reads the snapshot columns on the order row). */
    public function recent_orders(int $limit = 10): array
    {
        return $this->db
            ->select('id, order_number, customer_name, customer_phone, total, status, payment_status, payment_method, created_at', false)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('orders')->result_array();
    }

    /**
     * Per-day revenue and order counts for the last N days, with gaps filled so
     * the chart axis is continuous. Feeds the dashboard sales + orders charts.
     * Returns ['labels' => [...], 'revenue' => [...], 'orders' => [...]].
     */
    public function daily_trend(int $days = 14): array
    {
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $rows = $this->db
            ->select('DATE(created_at) AS d, COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue', false)
            ->where('DATE(created_at) >=', $start)
            ->where_not_in('status', $this->non_revenue_statuses)
            ->group_by('DATE(created_at)')
            ->get('orders')->result_array();

        $by_date = [];
        foreach ($rows as $r) {
            $by_date[$r['d']] = $r;
        }

        $labels = $revenue = $orders = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $labels[]  = date('d M', strtotime($d));
            $revenue[] = isset($by_date[$d]) ? round((float) $by_date[$d]['revenue'], 2) : 0;
            $orders[]  = isset($by_date[$d]) ? (int) $by_date[$d]['orders'] : 0;
        }
        return ['labels' => $labels, 'revenue' => $revenue, 'orders' => $orders];
    }
}

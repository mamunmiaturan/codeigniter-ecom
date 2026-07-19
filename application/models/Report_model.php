<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Reports & Analytics
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Report_model.php
 *
 * Read-only analytics for the admin Reports module. Every figure is read
 * straight off orders / order_items / products / users — no writes anywhere.
 *
 * "Revenue-counting" orders are those whose status is NOT cancelled/returned
 * (mirrors Dashboard_model). All date-range methods accept optional 'Y-m-d'
 * strings and filter on created_at, inclusive of the whole $to day. MAX() is
 * applied to non-grouped selected columns so the aggregates stay valid under
 * MySQL ONLY_FULL_GROUP_BY.
 */
class Report_model extends MY_Model
{
    /** Order statuses that do NOT count toward booked revenue. */
    private $non_revenue_statuses = ['cancelled', 'returned'];

    /** Active products at or below this on-hand qty are treated as "low stock". */
    const LOW_STOCK_THRESHOLD = 5;

    public function __construct()
    {
        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // Shared date-range helper
    // -------------------------------------------------------------------------

    /**
     * Apply an inclusive created_at range to the current query builder.
     *
     * @param string      $column e.g. 'created_at' or 'o.created_at' (aliased)
     * @param string|null $from   'Y-m-d' lower bound (open when null/empty)
     * @param string|null $to     'Y-m-d' upper bound (open when null/empty); the
     *                            whole $to day is included (< next day 00:00:00)
     */
    private function _apply_range($column = 'created_at', $from = null, $to = null)
    {
        if (!empty($from)) {
            $this->db->where($column . ' >=', $from . ' 00:00:00');
        }
        if (!empty($to)) {
            $this->db->where($column . ' <', date('Y-m-d 00:00:00', strtotime($to . ' +1 day')));
        }
    }

    // -------------------------------------------------------------------------
    // Sales
    // -------------------------------------------------------------------------

    /**
     * Revenue-counting orders grouped by calendar day.
     * @return array<int,array{date:string,orders:int,revenue:float}>
     */
    public function sales_by_day($from = null, $to = null)
    {
        $this->db->select('DATE(created_at) AS date, COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue', false)
                 ->from('orders')
                 ->where_not_in('status', $this->non_revenue_statuses);
        $this->_apply_range('created_at', $from, $to);
        return $this->db->group_by('DATE(created_at)', false)
                        ->order_by('date', 'ASC')
                        ->get()->result_array();
    }

    /**
     * Headline totals plus payment_status / status breakdowns for a range.
     * Headline figures cover revenue-counting orders only; the breakdowns cover
     * every order in range (so cancelled/returned remain visible).
     *
     * @return array{orders:int,revenue:float,items_sold:int,avg_order_value:float,by_payment_status:array,by_status:array}
     */
    public function sales_summary($from = null, $to = null)
    {
        // Revenue-counting headline figures.
        $this->db->select('COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue', false)
                 ->from('orders')
                 ->where_not_in('status', $this->non_revenue_statuses);
        $this->_apply_range('created_at', $from, $to);
        $head    = $this->db->get()->row_array();
        $orders  = (int) ($head['orders'] ?? 0);
        $revenue = (float) ($head['revenue'] ?? 0);

        // Units sold across revenue-counting orders.
        $this->db->select('COALESCE(SUM(oi.quantity),0) AS items', false)
                 ->from('order_items oi')
                 ->join('orders o', 'o.id = oi.order_id')
                 ->where_not_in('o.status', $this->non_revenue_statuses);
        $this->_apply_range('o.created_at', $from, $to);
        $items_sold = (int) $this->db->get()->row()->items;

        // Breakdown by payment_status (every order in range).
        $this->db->select('payment_status, COUNT(*) AS orders, COALESCE(SUM(total),0) AS amount', false)
                 ->from('orders');
        $this->_apply_range('created_at', $from, $to);
        $by_payment_status = $this->db->group_by('payment_status')
                                      ->order_by('payment_status', 'ASC')
                                      ->get()->result_array();

        // Breakdown by order status (every order in range).
        $this->db->select('status, COUNT(*) AS orders, COALESCE(SUM(total),0) AS amount', false)
                 ->from('orders');
        $this->_apply_range('created_at', $from, $to);
        $by_status = $this->db->group_by('status')
                              ->order_by('status', 'ASC')
                              ->get()->result_array();

        return [
            'orders'            => $orders,
            'revenue'           => $revenue,
            'items_sold'        => $items_sold,
            'avg_order_value'   => $orders > 0 ? $revenue / $orders : 0.0,
            'by_payment_status' => $by_payment_status,
            'by_status'         => $by_status,
        ];
    }

    /**
     * Top-selling products for a range (excludes cancelled/returned orders).
     * MAX(product_name) keeps the query valid under ONLY_FULL_GROUP_BY.
     * @return array<int,array{product_id:int,product_name:string,units:int,revenue:float}>
     */
    public function top_products($from = null, $to = null, $limit = 50)
    {
        $this->db->select('oi.product_id, MAX(oi.product_name) AS product_name, COALESCE(SUM(oi.quantity),0) AS units, COALESCE(SUM(oi.line_total),0) AS revenue', false)
                 ->from('order_items oi')
                 ->join('orders o', 'o.id = oi.order_id')
                 ->where_not_in('o.status', $this->non_revenue_statuses);
        $this->_apply_range('o.created_at', $from, $to);
        return $this->db->group_by('oi.product_id')
                        ->order_by('units', 'DESC')
                        ->limit((int) $limit)
                        ->get()->result_array();
    }

    // -------------------------------------------------------------------------
    // Inventory (point-in-time snapshots — no date range)
    // -------------------------------------------------------------------------

    /** Active products at or below the low-stock threshold. */
    public function low_stock($threshold = self::LOW_STOCK_THRESHOLD)
    {
        return $this->db->select('id, name, sku, stock_quantity, stock_status, price', false)
                        ->where('deleted_at', null)
                        ->where('status', 'Active')
                        ->where('stock_quantity <=', (int) $threshold)
                        ->order_by('stock_quantity', 'ASC')
                        ->get('products')->result_array();
    }

    /**
     * Per-product inventory valuation (stock_quantity * price) and grand total.
     * Covers every non-deleted product.
     * @return array{rows:array,total:float}
     */
    public function inventory_valuation()
    {
        $rows = $this->db->select('id, name, sku, stock_quantity, stock_status, price, (stock_quantity * price) AS stock_value', false)
                         ->where('deleted_at', null)
                         ->order_by('stock_value', 'DESC')
                         ->get('products')->result_array();
        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float) $r['stock_value'];
        }
        return ['rows' => $rows, 'total' => $total];
    }

    // -------------------------------------------------------------------------
    // Customers
    // -------------------------------------------------------------------------

    /**
     * Top customers by spend across revenue-counting orders. Guest orders
     * (user_id NULL) collapse into a single 'Guest' row.
     * @return array<int,array{name:string,email:string,orders:int,spend:float}>
     */
    public function customers_report($from = null, $to = null, $limit = 50)
    {
        $this->db->select("COALESCE(MAX(u.name), 'Guest') AS name, COALESCE(MAX(u.email), '') AS email, COUNT(*) AS orders, COALESCE(SUM(o.total),0) AS spend", false)
                 ->from('orders o')
                 ->join('users u', 'u.id = o.user_id', 'left')
                 ->where_not_in('o.status', $this->non_revenue_statuses);
        $this->_apply_range('o.created_at', $from, $to);
        return $this->db->group_by('o.user_id')
                        ->order_by('spend', 'DESC')
                        ->limit((int) $limit)
                        ->get()->result_array();
    }

    /** New user registrations within the range (users.created_at). */
    public function new_customers_count($from = null, $to = null)
    {
        $this->_apply_range('created_at', $from, $to);
        return (int) $this->db->count_all_results('users');
    }

    // -------------------------------------------------------------------------
    // Payments
    // -------------------------------------------------------------------------

    /**
     * Order breakdown by payment_method x payment_status for a range.
     * @return array<int,array{payment_method:string,payment_status:string,orders:int,amount:float}>
     */
    public function payments_report($from = null, $to = null)
    {
        $this->db->select('payment_method, payment_status, COUNT(*) AS orders, COALESCE(SUM(total),0) AS amount', false)
                 ->from('orders');
        $this->_apply_range('created_at', $from, $to);
        return $this->db->group_by(['payment_method', 'payment_status'])
                        ->order_by('payment_method', 'ASC')
                        ->order_by('payment_status', 'ASC')
                        ->get()->result_array();
    }
}

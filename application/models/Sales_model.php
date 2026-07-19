<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Sales Ops
 * @author   : Mamun Mia Turan
 * @filename : Sales_model.php
 *
 * Order documents: invoices, shipments (courier + tracking) and refunds.
 * Raw query builder (transactional order-attached writes).
 */
class Sales_model extends MY_Model
{
    protected $table = 'order_invoices';

    // ---- Invoices ----

    public function generate_invoice_number()
    {
        do {
            $num = 'INV' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->db->where('invoice_number', $num)->get('order_invoices')->num_rows() > 0);
        return $num;
    }

    public function get_invoice($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->get('order_invoices')->row_array();
    }

    /**
     * Create the order's invoice (idempotent — returns the existing one if any).
     */
    public function create_invoice($order_id, $created_by = null)
    {
        $existing = $this->get_invoice($order_id);
        if ($existing) {
            return $existing;
        }
        $order = $this->db->select('total')->where('id', (int) $order_id)->get('orders')->row_array();
        if (!$order) {
            return null;
        }
        $this->db->insert('order_invoices', [
            'order_id'       => (int) $order_id,
            'invoice_number' => $this->generate_invoice_number(),
            'total'          => (float) $order['total'],
            'created_by'     => $created_by ? (int) $created_by : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        return $this->get_invoice($order_id);
    }

    // ---- Shipments ----

    public function get_shipments($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->order_by('id', 'DESC')->get('order_shipments')->result_array();
    }

    public function latest_shipment($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->order_by('id', 'DESC')->limit(1)->get('order_shipments')->row_array();
    }

    /**
     * Record a shipment and move the order to 'shipped' (best-effort).
     */
    public function create_shipment($order_id, $data, $shipped_by = null)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('order_shipments', [
            'order_id'        => (int) $order_id,
            'carrier'         => trim((string) $data['carrier']),
            'tracking_number' => trim((string) ($data['tracking_number'] ?? '')) ?: null,
            'tracking_url'    => trim((string) ($data['tracking_url'] ?? '')) ?: null,
            'note'            => trim((string) ($data['note'] ?? '')) ?: null,
            'shipped_by'      => $shipped_by ? (int) $shipped_by : null,
            'created_at'      => $now,
        ]);
        $ship_id = (int) $this->db->insert_id();

        $order = $this->db->select('status')->where('id', (int) $order_id)->get('orders')->row_array();
        if ($order && in_array($order['status'], ['pending', 'confirmed', 'processing'], true)) {
            $this->db->where('id', (int) $order_id)->update('orders', ['status' => 'shipped', 'updated_at' => $now]);
            $this->db->insert('order_status_history', [
                'order_id'   => (int) $order_id,
                'status'     => 'shipped',
                'note'       => 'Shipped via ' . trim((string) $data['carrier']) . (($data['tracking_number'] ?? '') !== '' ? ' (' . $data['tracking_number'] . ')' : ''),
                'changed_by' => $shipped_by ? (int) $shipped_by : null,
                'created_at' => $now,
            ]);
        }
        $this->db->trans_complete();
        return $this->db->trans_status() !== false ? $ship_id : false;
    }

    // ---- Refunds ----

    public function get_refunds($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->order_by('id', 'DESC')->get('order_refunds')->result_array();
    }

    public function refunded_total($order_id)
    {
        $row = $this->db->select('COALESCE(SUM(amount),0) AS t', false)->where('order_id', (int) $order_id)->get('order_refunds')->row_array();
        return (float) ($row['t'] ?? 0);
    }

    /**
     * Record a refund. Marks the order payment_status refunded (full/partial by
     * comparison to the order total) and appends a status-history note.
     */
    public function create_refund($order_id, $data, $refunded_by = null)
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            return false;
        }
        $order = $this->db->select('total')->where('id', (int) $order_id)->get('orders')->row_array();
        if (!$order) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('order_refunds', [
            'order_id'    => (int) $order_id,
            'amount'      => $amount,
            'reason'      => trim((string) ($data['reason'] ?? '')) ?: null,
            'note'        => trim((string) ($data['note'] ?? '')) ?: null,
            'refunded_by' => $refunded_by ? (int) $refunded_by : null,
            'created_at'  => $now,
        ]);
        $refund_id = (int) $this->db->insert_id();

        $this->db->where('id', (int) $order_id)->update('orders', ['payment_status' => 'refunded', 'updated_at' => $now]);
        $total_refunded = $this->refunded_total($order_id);
        $label = ($total_refunded + 0.01 >= (float) $order['total']) ? 'Full refund' : 'Partial refund';
        $this->db->insert('order_status_history', [
            'order_id'   => (int) $order_id,
            'status'     => 'refunded',
            'note'       => $label . ' of ' . number_format($amount, 2) . (($data['reason'] ?? '') !== '' ? ' — ' . $data['reason'] : ''),
            'changed_by' => $refunded_by ? (int) $refunded_by : null,
            'created_at' => $now,
        ]);
        $this->db->trans_complete();
        return $this->db->trans_status() !== false ? $refund_id : false;
    }
}

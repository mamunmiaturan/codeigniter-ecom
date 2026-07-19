<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Marketing
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Newsletter.php
 *
 * Admin management of storefront newsletter subscribers. Permission prefix: `newsletter`.
 *
 * Subscribers self-register on the storefront, so this is a manage/list surface only
 * (no create/edit): toggle subscribed<->unsubscribed, delete, and CSV export.
 */
class Newsletter extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('newsletter_model');
    }

    public function index()
    {
        if (!get_permission('newsletter', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('newsletter_subscribers') ?: 'Newsletter Subscribers';
        $this->data['sub_page']  = 'marketing/newsletter_index';
        $this->data['main_menu'] = 'marketing';
        $this->load->view('layout/index', $this->data);
    }

    public function get_newsletter_server_side()
    {
        if (!get_permission('newsletter', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }
        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';
        if (!in_array($status, ['subscribed', 'unsubscribed'], true)) {
            $status = '';
        }

        $columns_map = [1 => 'email', 2 => 'source', 3 => 'status', 4 => 'created_at'];
        $order_idx = $this->input->post('order')[0]['column'] ?? 4;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'created_at';

        $total = $this->newsletter_model->count_all($status);
        $res   = $this->newsletter_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('newsletter', 'is_edit');
        $can_delete = get_permission('newsletter', 'is_delete');

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $data[] = [
                $i++,
                html_escape($row->email),
                html_escape($row->source ?: '—'),
                $this->_status_badge($row->status),
                time_ago($row->created_at),
                $this->_row_actions($row, $can_edit, $can_delete),
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

    /**
     * Flip a subscriber between subscribed <-> unsubscribed. POST, JSON.
     * Only `is_edit` may toggle.
     */
    public function status()
    {
        if (!get_permission('newsletter', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $row = $this->newsletter_model->find($id);
        if (empty($row)) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = ($row['status'] === 'subscribed') ? 'unsubscribed' : 'subscribed';
        if (!$this->newsletter_model->set_status($id, $new)) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'newsletter', $id, 'Newsletter subscriber ' . $row['email'] . ' → ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    /**
     * Hard-delete a subscriber. GET (confirm_modal AJAX). Only `is_delete` may delete.
     */
    public function delete($hash = '')
    {
        if (!get_permission('newsletter', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $row = $this->newsletter_model->find($id);
        if (empty($row)) {
            show_404();
            return;
        }
        if ($this->newsletter_model->remove($id)) {
            $this->log_activity('delete', 'newsletter', $id, 'Deleted newsletter subscriber: ' . $row['email']);
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('newsletter'));
    }

    /**
     * Stream all currently-subscribed rows as a CSV download.
     * Columns: email, source, subscribed_at.
     */
    public function export()
    {
        if (!get_permission('newsletter', 'is_view')) {
            access_denied();
        }
        $rows = $this->newsletter_model->all_subscribed();
        $this->log_activity('export', 'newsletter', 0, 'Exported ' . count($rows) . ' newsletter subscribers to CSV');

        $filename = 'newsletter_subscribers_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['email', 'source', 'subscribed_at'], ',', '"', '\\');
        foreach ($rows as $r) {
            fputcsv($output, [$r['email'], $r['source'], $r['created_at']], ',', '"', '\\');
        }
        fclose($output);
        exit;
    }

    // ---- render helpers ----

    private function _status_badge($status)
    {
        if ($status === 'subscribed') {
            return '<span class="badge badge-success">' . (translate('subscribed') ?: 'Subscribed') . '</span>';
        }
        return '<span class="badge badge-secondary">' . (translate('unsubscribed') ?: 'Unsubscribed') . '</span>';
    }

    private function _row_actions($row, $can_edit, $can_delete)
    {
        $html = '';
        if ($can_edit) {
            $is_sub  = ($row->status === 'subscribed');
            $icon    = $is_sub ? 'fa-toggle-on text-success' : 'fa-toggle-off text-danger';
            $tooltip = $is_sub ? (translate('unsubscribe') ?: 'Unsubscribe') : (translate('subscribe') ?: 'Subscribe');
            $html .= '<button type="button" class="btn btn-circle btn-default icon btn-nl-toggle" '
                . 'data-id="' . html_escape(encrypt_id($row->id)) . '" '
                . 'data-toggle="tooltip" data-original-title="' . $tooltip . '">'
                . '<i class="fas ' . $icon . '"></i></button> ';
        }
        if ($can_delete) {
            $html .= btn_delete('newsletter/delete/' . encrypt_id($row->id));
        }
        return $html;
    }
}

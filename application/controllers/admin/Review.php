<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Reviews
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Review.php
 *
 * Admin moderation for product reviews. Permission module prefix: `review`.
 */
class Review extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('review_model');
    }

    public function index()
    {
        if (!get_permission('review', 'is_view')) {
            access_denied();
        }
        $this->data['title']     = translate('reviews') ?: 'Reviews';
        $this->data['sub_page']  = 'catalog/review/index';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function get_reviews_server_side()
    {
        if (!get_permission('review', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw   = intval($this->input->post('draw'));
        $start  = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';
        $status = $this->input->post('status') ?? '';

        $columns_map = [
            0 => 'r.id',
            1 => 'p.name',
            2 => 'r.author_name',
            3 => 'r.rating',
            5 => 'r.is_verified_purchase',
            6 => 'r.status',
            7 => 'r.created_at',
        ];
        $order_idx = $this->input->post('order')[0]['column'] ?? 7;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'desc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $order_col = $columns_map[$order_idx] ?? 'r.created_at';

        $total = $this->review_model->count_all($status);
        $res   = $this->review_model->datatable($search, $start, $length, $order_col, $order_dir, $status);

        $can_edit   = get_permission('review', 'is_edit');
        $can_delete = get_permission('review', 'is_delete');

        $status_badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $stars = str_repeat('★', (int) $row->rating) . str_repeat('☆', 5 - (int) $row->rating);

            $comment = '<strong>' . html_escape($row->title ?: '') . '</strong>';
            if ($row->comment) {
                $comment .= '<br><span class="text-muted">' . html_escape(character_limiter($row->comment, 120)) . '</span>';
            }
            if ($row->admin_reply) {
                $comment .= '<br><span class="badge badge-info">' . (translate('replied') ?: 'Replied') . '</span> <span class="text-muted">' . html_escape(character_limiter($row->admin_reply, 80)) . '</span>';
            }

            $verified = $row->is_verified_purchase
                ? '<span class="badge badge-success">' . (translate('verified') ?: 'Verified') . '</span>'
                : '<span class="text-muted">—</span>';

            $sb = $status_badges[$row->status] ?? 'secondary';
            $status_html = '<span class="badge badge-' . $sb . '">' . html_escape(ucfirst($row->status)) . '</span>';

            $actions = '';
            if ($can_edit) {
                if ($row->status !== 'approved') {
                    $actions .= '<button type="button" class="btn btn-circle btn-default icon rv-approve" data-id="' . encrypt_id($row->id) . '" data-toggle="tooltip" data-original-title="' . (translate('approve') ?: 'Approve') . '"><i class="fas fa-check text-success"></i></button> ';
                }
                if ($row->status !== 'rejected') {
                    $actions .= '<button type="button" class="btn btn-circle btn-default icon rv-reject" data-id="' . encrypt_id($row->id) . '" data-toggle="tooltip" data-original-title="' . (translate('reject') ?: 'Reject') . '"><i class="fas fa-times text-danger"></i></button> ';
                }
                $actions .= '<button type="button" class="btn btn-circle btn-default icon rv-reply" data-id="' . encrypt_id($row->id) . '" data-reply="' . html_escape($row->admin_reply ?: '') . '" data-toggle="tooltip" data-original-title="' . (translate('reply') ?: 'Reply') . '"><i class="fas fa-reply"></i></button> ';
            }
            if ($can_delete) {
                $actions .= btn_delete('review/delete/' . encrypt_id($row->id));
            }

            $data[] = [
                $i++,
                html_escape($row->product_name ?: '—'),
                html_escape($row->author_name),
                '<span title="' . (int) $row->rating . '/5" style="color:#f5a623;">' . $stars . '</span>',
                $comment,
                $verified,
                $status_html,
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

    public function approve()
    {
        return $this->_set_status('approved');
    }

    public function reject()
    {
        return $this->_set_status('rejected');
    }

    private function _set_status($status)
    {
        if (!get_permission('review', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        if (!$this->review_model->set_status($id, $status)) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'review', $id, 'Review set to ' . $status);
        return $this->jsonResponse([
            'status'  => 'success',
            'message' => translate('information_has_been_updated_successfully'),
        ]);
    }

    public function reply()
    {
        if (!get_permission('review', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $text = trim((string) $this->input->post('reply'));
        $this->review_model->reply($id, $text);
        $this->log_activity('update', 'review', $id, 'Replied to review');
        return $this->jsonResponse([
            'status'  => 'success',
            'message' => translate('information_has_been_updated_successfully'),
        ]);
    }

    public function delete($hash = '')
    {
        if (!get_permission('review', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->review_model->delete($id)) {
            $this->log_activity('delete', 'review', $id, 'Deleted review');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('review'));
    }
}

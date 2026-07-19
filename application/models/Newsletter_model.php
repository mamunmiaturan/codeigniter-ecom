<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Marketing
 * @author   : Mamun Mia Turan
 * @filename : Newsletter_model.php
 */
class Newsletter_model extends MY_Model
{
    protected $table = 'newsletter_subscribers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function get_by_email($email)
    {
        return $this->db->where('email', strtolower(trim($email)))->get('newsletter_subscribers')->row_array();
    }

    /**
     * Subscribe an email (re-subscribes if previously unsubscribed).
     * @return string 'added' | 'resubscribed' | 'exists'
     */
    public function subscribe($email, $source = 'footer', $user_id = null)
    {
        $email = strtolower(trim($email));
        $row = $this->get_by_email($email);
        $now = date('Y-m-d H:i:s');
        if (!$row) {
            $this->db->insert('newsletter_subscribers', [
                'email' => $email, 'status' => 'subscribed', 'source' => $source,
                'user_id' => $user_id ? (int) $user_id : null, 'created_at' => $now,
            ]);
            return 'added';
        }
        if ($row['status'] === 'unsubscribed') {
            $this->db->where('id', $row['id'])->update('newsletter_subscribers', ['status' => 'subscribed', 'updated_at' => $now]);
            return 'resubscribed';
        }
        return 'exists';
    }

    public function unsubscribe($email)
    {
        $this->db->where('email', strtolower(trim($email)))->update('newsletter_subscribers', ['status' => 'unsubscribed', 'updated_at' => date('Y-m-d H:i:s')]);
        return $this->db->affected_rows() > 0;
    }

    public function set_status($id, $status)
    {
        if (!in_array($status, ['subscribed', 'unsubscribed'], true)) {
            return false;
        }
        $this->db->where('id', (int) $id)->update('newsletter_subscribers', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function remove($id)
    {
        $this->db->where('id', (int) $id)->delete('newsletter_subscribers');
        return true;
    }

    public function all_subscribed()
    {
        return $this->db->select('email, source, created_at')->where('status', 'subscribed')->order_by('id', 'DESC')->get('newsletter_subscribers')->result_array();
    }

    public function count_all($status = '')
    {
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results('newsletter_subscribers');
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('newsletter_subscribers');
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()->like('email', $search)->or_like('source', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }
}

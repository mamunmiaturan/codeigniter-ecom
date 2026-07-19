<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / FAQ
 * @author   : Mamun Mia Turan
 * @filename : Faq_model.php
 */
class Faq_model extends MY_Model
{
    protected $table = 'faqs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'question', 'answer', 'category', 'status', 'sort_order', 'created_by', 'updated_by',
    ];

    /** Active FAQs for the storefront, ordered for display. */
    public function active()
    {
        return $this->db->select('question, answer, category, sort_order')
            ->where('status', 'Active')->where('deleted_at', null)
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('faqs')->result_array();
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('faqs')->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()->like('question', $search)->or_like('category', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }
}

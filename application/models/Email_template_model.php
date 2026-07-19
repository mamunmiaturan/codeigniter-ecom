<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Email_template_model extends MY_Model
{
    public function list_all(): array
    {
        return $this->db->order_by('template_key', 'ASC')
            ->get('email_templates')
            ->result_array();
    }

    public function get_by_id(int $id): ?array
    {
        $row = $this->db->where('id', $id)->get('email_templates')->row_array();
        return $row ?: null;
    }

    /**
     * Update email_templates row (signature matches MY_Model::update).
     */
    public function update($id, $data)
    {
        if (!is_array($data)) {
            return false;
        }
        $id = (int) $id;
        if ($id < 1) {
            return false;
        }
        $payload = array_intersect_key($data, array_flip(['subject', 'template_body', 'is_active', 'email_type']));
        if (empty($payload)) {
            return false;
        }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('email_templates', $payload);
    }
}

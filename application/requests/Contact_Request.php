<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once __DIR__ . '/Form_Request.php';

class Contact_Request extends Form_Request
{
    public function rules(): array
    {
        return [
            ['field' => 'name',    'label' => 'Name',    'rules' => 'trim|required|max_length[150]'],
            ['field' => 'email',   'label' => 'Email',   'rules' => 'trim|required|valid_email|max_length[150]'],
            ['field' => 'phone',   'label' => 'Phone',   'rules' => 'trim|max_length[30]'],
            ['field' => 'subject', 'label' => 'Subject', 'rules' => 'trim|max_length[200]'],
            ['field' => 'message', 'label' => 'Message', 'rules' => 'trim|required'],
        ];
    }
    public function back(): string { return base_url('landing/contact'); }
}

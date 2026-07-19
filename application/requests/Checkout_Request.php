<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once __DIR__ . '/Form_Request.php';

class Checkout_Request extends Form_Request
{
    public function rules(): array
    {
        return [
            ['field' => 'name',     'label' => 'Name',        'rules' => 'trim|required|max_length[150]'],
            ['field' => 'phone',    'label' => 'Phone',       'rules' => 'trim|required|max_length[30]'],
            ['field' => 'address',  'label' => 'Full address', 'rules' => 'trim|required'],
            ['field' => 'email',    'label' => 'Email',       'rules' => 'trim|valid_email|max_length[150]'],
            ['field' => 'division', 'label' => 'Division',    'rules' => 'trim|max_length[60]'],
            ['field' => 'district', 'label' => 'District',    'rules' => 'trim|max_length[60]'],
        ];
    }
    public function back(): string { return base_url('landing/checkout'); }
}

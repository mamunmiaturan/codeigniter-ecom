<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once __DIR__ . '/Form_Request.php';

class Register_Request extends Form_Request
{
    public function rules(): array
    {
        return [
            ['field' => 'name',             'label' => 'Name',             'rules' => 'trim|required|max_length[150]'],
            ['field' => 'email',            'label' => 'Email',            'rules' => 'trim|required|valid_email|max_length[150]'],
            ['field' => 'phone',            'label' => 'Phone',            'rules' => 'trim|max_length[30]'],
            ['field' => 'password',         'label' => 'Password',         'rules' => 'required|min_length[8]'],
            ['field' => 'password_confirm', 'label' => 'Confirm Password', 'rules' => 'required|matches[password]'],
        ];
    }
    public function back(): string { return base_url('landing/account/register'); }
}

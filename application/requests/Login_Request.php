<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once __DIR__ . '/Form_Request.php';

class Login_Request extends Form_Request
{
    public function rules(): array
    {
        return [
            ['field' => 'email',    'label' => 'Email',    'rules' => 'trim|required|valid_email'],
            ['field' => 'password', 'label' => 'Password', 'rules' => 'required'],
        ];
    }
    public function back(): string { return base_url('landing/account/login'); }
}

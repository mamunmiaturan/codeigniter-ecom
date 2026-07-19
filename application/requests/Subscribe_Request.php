<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once __DIR__ . '/Form_Request.php';

class Subscribe_Request extends Form_Request
{
    public function rules(): array
    {
        return [
            ['field' => 'email', 'label' => 'Email', 'rules' => 'trim|required|valid_email|max_length[150]'],
        ];
    }
}

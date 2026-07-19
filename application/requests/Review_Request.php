<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once __DIR__ . '/Form_Request.php';

class Review_Request extends Form_Request
{
    public function rules(): array
    {
        return [
            ['field' => 'rating',  'label' => 'Rating',  'rules' => 'trim|required|integer|greater_than_equal_to[1]|less_than_equal_to[5]'],
            ['field' => 'title',   'label' => 'Title',   'rules' => 'trim|max_length[150]'],
            ['field' => 'comment', 'label' => 'Comment', 'rules' => 'trim|max_length[2000]'],
        ];
    }
}

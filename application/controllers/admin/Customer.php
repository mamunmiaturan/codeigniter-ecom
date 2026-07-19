<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin — Customers list. Storefront customers are login_credential rows with
 * role = ROLE_CUSTOMER_ID (linked to the `users` table). This gives a dedicated
 * customer directory separate from the staff User management screen.
 * Permission module prefix: `user` (customer management is part of user rights).
 */
class Customer extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (!get_permission('customer', 'is_view')) {
            access_denied();
        }

        $rows = $this->db
            ->select('u.id, u.user_id, u.name, u.mobile_no, u.photo, lc.email, lc.status, u.created_at,
                      (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                      (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.status NOT IN ("cancelled","returned")) AS total_spent', false)
            ->from('users u')
            ->join('login_credential lc', 'lc.user_id = u.id', 'inner')
            ->where('lc.role', ROLE_CUSTOMER_ID)
            ->where('u.deleted_at', null)
            ->order_by('u.id', 'DESC')
            ->get()
            ->result();

        $this->data['customers'] = $rows;
        $this->data['title']     = translate('customers') ?: 'Customers';
        $this->data['sub_page']  = 'customer/index';
        $this->data['main_menu'] = 'customer';
        $this->load->view('layout/index', $this->data);
    }
}

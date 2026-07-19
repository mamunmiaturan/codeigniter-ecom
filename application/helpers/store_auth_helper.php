<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Storefront
 * @filename : store_auth_helper.php
 *
 * Storefront customer session helpers. A customer's web session is kept under
 * the `store_customer` userdata key so it never collides with the admin panel's
 * `loggedin` session. Populated by Landing account login/registration.
 */

if (!function_exists('current_customer')) {
    function current_customer()
    {
        $ci = &get_instance();
        $c = $ci->session->userdata('store_customer');
        return is_array($c) ? $c : null;
    }
}

if (!function_exists('is_customer_loggedin')) {
    function is_customer_loggedin()
    {
        return current_customer() !== null;
    }
}

if (!function_exists('customer_id')) {
    function customer_id()
    {
        $c = current_customer();
        return $c ? (int) $c['id'] : null;
    }
}

if (!function_exists('customer_name')) {
    function customer_name()
    {
        $c = current_customer();
        return $c ? (string) $c['name'] : '';
    }
}

if (!function_exists('store_cart_token')) {
    /**
     * Persistent guest cart token kept in the session (anonymous shoppers).
     */
    function store_cart_token()
    {
        $ci = &get_instance();
        $t = $ci->session->userdata('store_cart_token');
        if (!$t) {
            $t = 's_' . app_generate_hash();
            $ci->session->set_userdata('store_cart_token', $t);
        }
        return $t;
    }
}

if (!function_exists('cart_owner')) {
    /**
     * Resolve the current cart owner: a logged-in customer owns their cart by
     * user_id; otherwise the anonymous guest token is used.
     *
     * @return array ['user_id' => int|null, 'guest_token' => string|null]
     */
    function cart_owner()
    {
        $cid = customer_id();
        return $cid
            ? ['user_id' => $cid, 'guest_token' => null]
            : ['user_id' => null, 'guest_token' => store_cart_token()];
    }
}

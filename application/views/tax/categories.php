<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Thin wrapper so the sidebar can mark "Tax > Tax Categories" active via a
 * distinct sub_page ('tax/categories') while reusing the shared Tax body.
 * The controller passes $tab = 'categories', so only the categories panel renders.
 */
$this->load->view('tax/index');

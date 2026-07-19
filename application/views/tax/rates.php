<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Thin wrapper so the sidebar can mark "Tax > Tax Rates" active via a distinct
 * sub_page ('tax/rates') while reusing the shared Tax body. The controller
 * passes $tab = 'rates', so only the rates panel renders.
 */
$this->load->view('tax/index');

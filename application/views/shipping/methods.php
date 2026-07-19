<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Thin wrapper so the sidebar can mark "Shipping > Shipping Methods" active via
 * a distinct sub_page ('shipping/methods') while reusing the shared Shipping
 * body. The controller passes $tab = 'methods', so only the methods panel
 * renders.
 */
$this->load->view('shipping/index');

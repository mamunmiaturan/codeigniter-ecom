<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Thin wrapper so the sidebar can mark "Shipping > Shipping Zones" active via a
 * distinct sub_page ('shipping/zones') while reusing the shared Shipping body.
 * The controller passes $tab = 'zones', so only the zones panel renders.
 */
$this->load->view('shipping/index');

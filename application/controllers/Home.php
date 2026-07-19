<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'controllers/landing/Landing.php';

/**
 * Root-level home controller. CodeIgniter's default_controller cannot target a
 * controller inside a sub-directory (the storefront lives in controllers/landing/),
 * so this thin root class EXTENDS the storefront Landing controller and inherits
 * its index() (the storefront home) — letting the bare site root (ecom.test/)
 * serve the storefront directly, with no 'landing' prefix and no redirect.
 */
class Home extends Landing
{
    // Everything (constructor, index, _render, …) is inherited from Landing.
}

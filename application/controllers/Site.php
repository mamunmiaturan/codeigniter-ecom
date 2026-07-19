<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Site-root entry point. CodeIgniter's default_controller / 404_override cannot
 * target a controller inside a sub-directory (the storefront lives in
 * controllers/landing/), so this thin top-level controller forwards the bare
 * site root (ecom.test/) to the storefront home and renders the storefront 404
 * page for unknown URLs.
 *
 * The root sends a server redirect AND an HTML fallback (meta-refresh + JS +
 * link) so the page is never blank even if a proxy / opcache / env quirk drops
 * the Location header.
 */
class Site extends CI_Controller
{
    public function index()
    {
        // The site root is served by the root-level Home controller (default_controller
        // = 'home', which extends the storefront Landing controller). This method is
        // only reachable if someone hits /site directly — send them to the home.
        redirect(base_url('/'));
    }

    /** 404_override target for unmatched URLs. */
    public function show_404()
    {
        $this->output->set_status_header(404);
        $site = get_global_setting('site_name') ?: 'Store';
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Page Not Found · ' . html_escape($site) . '</title>'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<style>body{font-family:system-ui,Segoe UI,Roboto,sans-serif;display:flex;min-height:100vh;margin:0;align-items:center;justify-content:center;background:#f7f8fa;color:#1f2937;text-align:center}'
            . '.box{max-width:420px;padding:2rem}h1{font-size:4rem;margin:0;color:#0d9488}p{color:#6b7280}a{display:inline-block;margin-top:1rem;background:#0d9488;color:#fff;padding:.6rem 1.4rem;border-radius:100px;text-decoration:none;font-weight:600}</style>'
            . '</head><body><div class="box"><h1>404</h1><p>Sorry, the page you are looking for could not be found.</p>'
            . '<a href="' . base_url('/') . '">Back to home</a></div></body></html>';
    }
}

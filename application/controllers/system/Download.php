<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Storefront
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Download.php
 *
 * Streams downloadable-product files. Files live in the web-inaccessible
 * uploads/downloads/ directory (protected by .htaccess) and are only ever
 * served through here, never linked directly.
 *
 *   file/{token} — a customer's purchased file. Requires ownership of the
 *                  customer_downloads row and honours its limit + expiry.
 *   sample/{id}  — a free sample file attached to a product (public).
 */
class Download extends Frontend_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('download_model');
        $this->load->helper(['url', 'store_auth', 'download']);
    }

    /** Secure, per-order customer download by token. */
    public function file($token = '')
    {
        $row = $token ? $this->download_model->get_by_token($token) : null;
        if (!$row) {
            show_404();
            return;
        }
        // A link tied to a registered customer may only be fetched by that
        // customer — via the web session (browser) or a matching JWT bearer
        // (mobile app). Guest-order links have a null user_id and rely on the
        // token secret alone.
        if (!$this->_owner_ok($row)) {
            redirect(base_url('account/login?redirect=' . urlencode(base_url('account/downloads'))));
            return;
        }
        if (!$this->download_model->is_available($row)) {
            set_alert('error', 'This download link has expired or reached its limit.');
            redirect(base_url('account/downloads'));
            return;
        }
        $path = $this->_resolve($row['file_path']);
        if (!$path) {
            show_404();
            return;
        }
        // Count the download before streaming — force_download() exits.
        $this->download_model->record_download((int) $row['id']);
        $this->_send($path, $row['file_path'], $row['name']);
    }

    /** Free sample file (public, no auth). */
    public function sample($id = 0)
    {
        $row = $this->download_model->get_download((int) $id);
        if (!$row || empty($row['is_sample'])) {
            show_404();
            return;
        }
        $path = $this->_resolve($row['file_path']);
        if (!$path) {
            show_404();
            return;
        }
        $this->_send($path, $row['file_path'], $row['name']);
    }

    // ---- helpers ----

    /** True when the current requester may fetch this customer_downloads row. */
    private function _owner_ok($row)
    {
        if ($row['user_id'] === null) {
            return true; // guest-order link: the token secret is the credential
        }
        if (is_customer_loggedin() && (int) $row['user_id'] === (int) customer_id()) {
            return true; // web session owner
        }
        $uid = $this->_bearer_user_id();
        return $uid !== null && (int) $uid === (int) $row['user_id']; // mobile bearer
    }

    /** Decode a JWT bearer (if present) and return its subject, else null. */
    private function _bearer_user_id()
    {
        $header = $this->input->get_request_header('Authorization', true) ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$header) {
            return null;
        }
        $this->load->library('jwt');
        $token = Jwt::extract_bearer($header);
        if (!$token) {
            return null;
        }
        try {
            $claims = $this->jwt->decode($token);
        } catch (Throwable $e) {
            return null;
        }
        if (($claims['type'] ?? '') !== 'access' || !isset($claims['sub'])) {
            return null;
        }
        return (int) $claims['sub'];
    }

    /** Absolute path inside uploads/downloads/ or null if missing. */
    private function _resolve($stored)
    {
        if (!$stored) {
            return null;
        }
        $path = FCPATH . 'uploads/downloads/' . basename($stored);
        return is_file($path) ? $path : null;
    }

    /** Stream the file with a human-friendly filename, then exit. */
    private function _send($path, $stored, $display_name)
    {
        $ext  = pathinfo($stored, PATHINFO_EXTENSION);
        $nice = preg_replace('/[^\pL\pN\.\-_ ]+/u', '', (string) $display_name);
        $nice = trim($nice) !== '' ? trim($nice) : 'download';
        if ($ext && strcasecmp((string) pathinfo($nice, PATHINFO_EXTENSION), $ext) !== 0) {
            $nice .= '.' . $ext;
        }
        force_download($nice, file_get_contents($path)); // sends headers + exit
    }
}

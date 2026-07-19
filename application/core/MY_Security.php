<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Security extends CI_Security {

    public function __construct()
    {
        parent::__construct();
    }

    public function csrf_set_cookie()
    {
        $expire = time() + $this->_csrf_expire;
        $secure_cookie = (bool) config_item('cookie_secure');

        if ($secure_cookie && ! is_https())
        {
            return FALSE;
        }

        $path = config_item('cookie_path') ?: '/';
        $domain = config_item('cookie_domain') ?: '';
        $httponly = (bool) config_item('cookie_httponly');

        if (PHP_VERSION_ID >= 70300) {
            setcookie($this->_csrf_cookie_name, $this->_csrf_hash, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure_cookie,
                'httponly' => $httponly,
                'samesite' => 'Lax'
            ]);
        } else {
            // Fallback for older PHP versions
            setcookie(
                $this->_csrf_cookie_name,
                $this->_csrf_hash,
                $expire,
                $path . '; SameSite=Lax',
                $domain,
                $secure_cookie,
                $httponly
            );
        }

        log_message('info', 'CSRF cookie sent with SameSite=Lax');
        return $this;
    }
}

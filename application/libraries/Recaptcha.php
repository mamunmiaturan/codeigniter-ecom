<?php (! defined('BASEPATH')) and exit('No direct script access allowed');

/**
 * CodeIgniter Recaptcha library
 *
 * @package CodeIgniter
 * @author  Bo-Yi Wu <appleboy.tw@gmail.com>
 * @link    https://github.com/appleboy/CodeIgniter-reCAPTCHA
 */
class Recaptcha
{
    /**
     * ci instance object
     *
     */
    private $_ci;
    private $_siteKey = '';
    private $_secretKey = '';
    private $_language = 'en';
    private $_configured = false;

    /**
     * reCAPTCHA site up, verify and api url.
     *
     */
    const sign_up_url = 'https://www.google.com/recaptcha/admin';
    const site_verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    const api_url = 'https://www.google.com/recaptcha/api.js';

    /**
     * Constructor. Keys are read from global_settings
     * (recaptcha_site_key / recaptcha_secret_key). When either is blank, the
     * library is simply "not configured" — callers should check is_configured()
     * and skip the challenge so login/registration keep working out of the box.
     */
    public function __construct()
    {
        $this->_ci = & get_instance();
        $site   = function_exists('get_global_setting') ? (string) get_global_setting('recaptcha_site_key') : '';
        $secret = function_exists('get_global_setting') ? (string) get_global_setting('recaptcha_secret_key') : '';
        $this->_siteKey    = trim($site);
        $this->_secretKey  = trim($secret);
        $this->_configured = ($this->_siteKey !== '' && $this->_secretKey !== '');
    }

    /** Whether both keys are present (i.e. reCAPTCHA should be enforced). */
    public function is_configured()
    {
        return $this->_configured;
    }

    /**
     * Submits an HTTP GET to a reCAPTCHA server.
     *
     * @param array $data array of parameters to be sent.
     *
     * @return array response
     */
    private function _submitHTTPGet($data)
    {
        $url = self::site_verify_url.'?'.http_build_query($data);
        
        if( ini_get('allow_url_fopen') ) {
            $response = file_get_contents($url);
        } else {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);       

            $response = curl_exec($ch);
            curl_close($ch);
        }

        return $response;
    }

    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test.
     *
     * @param string $response response string from recaptcha verification.
     * @param string $remoteIp IP address of end user.
     *
     * @return ReCaptchaResponse
     */
    public function verifyResponse($response, $remoteIp = null)
    {
        $remoteIp = (!empty($remoteIp)) ? $remoteIp : $this->_ci->input->ip_address();

        // Discard empty solution submissions
        if (empty($response)) {
            return array(
                'success' => false,
                'error-codes' => 'missing-input',
            );
        }

        $getResponse = $this->_submitHttpGet(
            array(
                'secret' => $this->_secretKey,
                'remoteip' => $remoteIp,
                'response' => $response,
            )
        );

        // get reCAPTCHA server response
        $responses = json_decode($getResponse, true);

        if (isset($responses['success']) and $responses['success'] == true) {
            $status = true;
        } else {
            $status = false;
            $error = (isset($responses['error-codes'])) ? $responses['error-codes']
                : 'invalid-input-response';
        }

        return array(
            'success' => $status,
            'error-codes' => (isset($error)) ? $error : null,
        );
    }

    /**
     * Render Script Tag
     *
     * onload: Optional.
     * render: [explicit|onload] Optional.
     * hl: Optional.
     * see: https://developers.google.com/recaptcha/docs/display
     *
     * @param array parameters.
     *
     * @return scripts
     */
    public function getScriptTag(array $parameters = array())
    {
        $default = array(
            'render' => 'onload',
            'hl' => $this->_language,
        );

        $result = array_merge($default, $parameters);

        $scripts = sprintf('<script type="text/javascript" src="%s?%s" async defer></script>',
            self::api_url, http_build_query($result));

        return $scripts;
    }

    /**
     * render the reCAPTCHA widget
     *
     * data-theme: dark|light
     * data-type: audio|image
     *
     * @param array parameters.
     *
     * @return scripts
     */
    public function getWidget(array $parameters = array())
    {
        $default = array(
            'data-sitekey' => $this->_siteKey,
            'data-theme' => 'light',
            'data-type' => 'image',
            'data-size' => 'normal',
        );

        $result = array_merge($default, $parameters);

        $html = '';
        foreach ($result as $key => $value) {
            $html .= sprintf('%s="%s" ', $key, $value);
        }

        return '<div class="g-recaptcha" '.$html.'></div>';
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

    public function __construct($rules = array()) {
        parent::__construct($rules);
    }

    /**
     * Required
     *
     * Overriding to handle null values for PHP 8.1+ compatibility
     *
     * @param	mixed
     * @return	bool
     */
    public function required($str)
    {
        if ($str === NULL) {
            return FALSE;
        }
        
        return is_array($str)
            ? (empty($str) === FALSE)
            : (trim((string) $str) !== '');
    }

    /**
     * Overriding _execute to ensure native PHP functions don't receive null
     */
    protected function _execute($row, $rules, $postdata = NULL, $cycles = 0)
    {
        // Ensure postdata is a string if it's null, to prevent trim() warnings
        if ($postdata === NULL) {
            $postdata = '';
        }
        return parent::_execute($row, $rules, $postdata, $cycles);
    }

    /**
     * Validate password complexity
     *
     * @param	string	$password
     * @return	bool
     */
    public function password_complexity($password)
    {
        $ci =& get_instance();
        $validation_result = $ci->app_lib->validate_password($password);
        if ($validation_result !== true) {
            $this->set_message('password_complexity', $validation_result);
            return FALSE;
        }
        return TRUE;
    }
}

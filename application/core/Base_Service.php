<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Base_Service.php
 */
class Base_Service {
    
    protected $ci;
    protected $db;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->db = $this->ci->db;
    }

    /**
     * Magic getter to access CI libraries, models, etc. easily
     * e.g. $this->session instead of $this->ci->session
     */
    public function __get($key)
    {
        return $this->ci->$key;
    }

    /**
     * Helper to load other services
     */
    public function service($name)
    {
        $class = ucfirst($name) . 'Service';
        if (!isset($this->ci->$class)) {
            require_once APPPATH . 'services/' . $class . '.php';
            $this->ci->$class = new $class();
        }
        return $this->ci->$class;
    }
}

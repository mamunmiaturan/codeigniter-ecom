<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Seeder.php
 */
class Seeder {
    protected $ci;
    protected $db;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->db = $this->ci->db;
    }

    /**
     * Run the seeder
     */
    public function run()
    {
        // To be implemented by child classes
    }

    /**
     * Call another seeder
     *
     * @param string $seeder Seeder class name
     */
    public function call($seeder)
    {
        $file = FCPATH . 'database/seeders/' . $seeder . '.php';
        if (file_exists($file)) {
            require_once $file;
            $obj = new $seeder();
            $obj->run();
        } else {
            echo "Seeder $seeder not found." . PHP_EOL;
        }
    }
}

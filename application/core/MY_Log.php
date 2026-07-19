<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Log extends CI_Log {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Format the log line as JSON
     *
     * @param	string	$level 	The error level
     * @param	string	$date 	Formatted date string
     * @param	string	$message 	The log message
     * @return	string	Formatted log line with a new line character at the end
     */
    protected function _format_line($level, $date, $message)
    {
        $log_entry = array(
            'level'   => $level,
            'date'    => $date,
            'message' => trim($message)
        );
        return json_encode($log_entry) . PHP_EOL;
    }
}

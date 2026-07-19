<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Simple Database-based Queue Library
 */
class Queue {
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
    }

    /**
     * Push a new job onto the queue
     * 
     * @param string $uri The CodeIgniter URI to execute (e.g. 'notifications/send_email')
     * @param array $data Data to be passed to the task
     * @param string $queue The queue name (default: 'default')
     * @return bool
     */
    public function push($uri, $data = [], $queue = 'default', $priority = 0)
    {
        $payload = json_encode([
            'uri' => $uri,
            'data' => $data
        ]);

        $job_data = [
            'queue' => $queue,
            'payload' => $payload,
            'priority' => $priority,
            'available_at' => date('Y-m-d H:i:s'),
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->ci->db->insert('jobs', $job_data);
    }
}

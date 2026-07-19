<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pusher Library for Real-time events
 */
class Pusher_lib
{
    protected $pusher;
    protected $enabled;

    public function __construct()
    {
        $this->enabled = (getenv('PUSHER_ENABLED') === 'true');
        
        if ($this->enabled) {
            // Require composer autoload if not already
            if (file_exists(FCPATH . 'vendor/autoload.php')) {
                require_once FCPATH . 'vendor/autoload.php';
            }

            if (class_exists('Pusher\Pusher')) {
                $options = array(
                    'cluster' => getenv('PUSHER_APP_CLUSTER'),
                    'useTLS' => true
                );
                $this->pusher = new Pusher\Pusher(
                    getenv('PUSHER_APP_KEY'),
                    getenv('PUSHER_APP_SECRET'),
                    getenv('PUSHER_APP_ID'),
                    $options
                );
            } else {
                $this->enabled = false;
                log_message('error', 'Pusher class not found. Please run composer require pusher/pusher-php-server');
            }
        }
    }

    public function trigger($channel, $event, $data)
    {
        if ($this->enabled && $this->pusher) {
            try {
                $this->pusher->trigger($channel, $event, $data);
                return true;
            } catch (Exception $e) {
                log_message('error', 'Pusher Error: ' . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    public function socket_auth($channelName, $socketId)
    {
        if ($this->enabled && $this->pusher) {
            try {
                if (method_exists($this->pusher, 'authorizeChannel')) {
                    return $this->pusher->authorizeChannel($channelName, $socketId);
                } else if (method_exists($this->pusher, 'socket_auth')) {
                    return $this->pusher->socket_auth($channelName, $socketId);
                }
            } catch (Exception $e) {
                log_message('error', 'Pusher Auth Error: ' . $e->getMessage());
                return false;
            }
        }
        return false;
    }
}

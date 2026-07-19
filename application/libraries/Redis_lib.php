<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Resilient Redis Library for CodeIgniter 3
 */
class Redis_lib
{
    protected $redis = null;
    protected $enabled = false;
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;
        $password = getenv('REDIS_PASSWORD');
        if ($password === 'null' || empty($password)) {
            $password = null;
        }

        // Only attempt connection if REDIS_ENABLED environment variable is explicitly set to 'true'
        $redis_enabled = getenv('REDIS_ENABLED');
        if (strtolower($redis_enabled) === 'true' && class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                // 1.5 seconds connection timeout
                if ($this->redis->connect($host, (int)$port, 0.5)) {
                    if ($password !== null) {
                        $this->redis->auth($password);
                    }
                    $this->enabled = true;
                }
            } catch (Exception $e) {
                log_message('debug', 'Redis connection failed: ' . $e->getMessage());
                $this->redis = null;
            }
        }
    }

    /**
     * Check if Redis is connected and enabled
     */
    public function is_enabled()
    {
        return $this->enabled;
    }

    /**
     * Get a value from Redis
     */
    public function get($key)
    {
        if ($this->enabled && $this->redis) {
            try {
                $val = $this->redis->get($key);
                return $val !== false ? json_decode($val, true) : null;
            } catch (Exception $e) {
                log_message('error', 'Redis GET error: ' . $e->getMessage());
            }
        }
        
        // Fallback to CodeIgniter session storage if Redis is disabled/offline
        return $this->CI->session->userdata('r_fallback_' . $key);
    }

    /**
     * Set a value in Redis with optional TTL (seconds)
     */
    public function set($key, $value, $ttl = null)
    {
        if ($this->enabled && $this->redis) {
            try {
                $val = json_encode($value);
                if ($ttl) {
                    return $this->redis->setex($key, $ttl, $val);
                } else {
                    return $this->redis->set($key, $val);
                }
            } catch (Exception $e) {
                log_message('error', 'Redis SET error: ' . $e->getMessage());
            }
        }

        // Fallback to CodeIgniter session storage if Redis is disabled/offline
        $this->CI->session->set_userdata('r_fallback_' . $key, $value);
        return true;
    }

    /**
     * Delete a key from Redis
     */
    public function delete($key)
    {
        if ($this->enabled && $this->redis) {
            try {
                return $this->redis->del($key);
            } catch (Exception $e) {
                log_message('error', 'Redis DELETE error: ' . $e->getMessage());
            }
        }

        // Fallback to CodeIgniter session storage if Redis is disabled/offline
        $this->CI->session->unset_userdata('r_fallback_' . $key);
        return true;
    }

    /**
     * Increment a key, with optional TTL (seconds) set upon creation
     */
    public function incr($key, $ttl = null)
    {
        if ($this->enabled && $this->redis) {
            try {
                $val = $this->redis->incr($key);
                if ($ttl && $val == 1) {
                    $this->redis->expire($key, $ttl);
                }
                return $val;
            } catch (Exception $e) {
                log_message('error', 'Redis INCR error: ' . $e->getMessage());
            }
        }

        // Fallback to CodeIgniter session storage if Redis is disabled/offline
        $val = intval($this->get($key) ?? 0) + 1;
        $this->set($key, $val, $ttl);
        return $val;
    }

    /**
     * Return the remaining TTL (seconds) of a key, or -1 if no expiry / not found.
     */
    public function ttl($key)
    {
        if ($this->enabled && $this->redis) {
            try {
                return (int) $this->redis->ttl($key);
            } catch (Exception $e) {
                log_message('error', 'Redis TTL error: ' . $e->getMessage());
            }
        }
        return -1;
    }

    /**
     * Execute arbitrary command directly on native Redis object
     */
    public function call($method, ...$args)
    {
        if ($this->enabled && $this->redis) {
            try {
                return call_user_func_array([$this->redis, $method], $args);
            } catch (Exception $e) {
                log_message('error', 'Redis direct call error (' . $method . '): ' . $e->getMessage());
            }
        }
        return null;
    }
}

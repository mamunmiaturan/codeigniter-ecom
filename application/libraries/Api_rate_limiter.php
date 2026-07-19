<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Rate Limiter Library
 *
 * Sliding-window rate limiting for HTTP endpoints.
 * Uses Redis when available; falls back to the `api_rate_limits` DB table.
 *
 * Usage:
 *   $this->load->library('api_rate_limiter');
 *   $result = $this->api_rate_limiter->check('forgot_password:' . $ip, 5, 900);
 *   if (!$result['allowed']) { show_error('Too many requests', 429); }
 */
class Api_rate_limiter
{
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
    }

    /**
     * Check and increment the hit counter for a given rate key.
     *
     * @param  string $key         Unique identifier, e.g. "endpoint:ip_or_user_id"
     * @param  int    $max_hits    Maximum allowed hits per window
     * @param  int    $window_sec  Window duration in seconds
     * @return array  ['allowed' => bool, 'remaining' => int, 'reset_in' => int (seconds)]
     */
    public function check(string $key, int $max_hits, int $window_sec): array
    {
        $this->ci->load->library('redis_lib');
        if ($this->ci->redis_lib->is_enabled()) {
            return $this->_check_redis($key, $max_hits, $window_sec);
        }
        return $this->_check_db($key, $max_hits, $window_sec);
    }

    /**
     * Reset the hit counter for a key (e.g. after successful authentication).
     */
    public function reset(string $key): void
    {
        $this->ci->load->library('redis_lib');
        if ($this->ci->redis_lib->is_enabled()) {
            $this->ci->redis_lib->delete('rl:' . $key);
            return;
        }
        $this->ci->db->where('rate_key', $key)->delete('api_rate_limits');
    }

    // -------------------------------------------------------------------------
    // Redis backend
    // -------------------------------------------------------------------------

    private function _check_redis(string $key, int $max_hits, int $window_sec): array
    {
        $redis_key = 'rl:' . $key;
        $hits = (int) $this->ci->redis_lib->incr($redis_key, $window_sec);

        if ($hits === 1) {
            // First hit — TTL was just set by incr; no extra action needed.
        }

        $ttl = $this->ci->redis_lib->ttl($redis_key);
        $remaining = max(0, $max_hits - $hits);
        return [
            'allowed'   => $hits <= $max_hits,
            'remaining' => $remaining,
            'reset_in'  => max(0, (int) $ttl),
        ];
    }

    // -------------------------------------------------------------------------
    // Database backend
    // -------------------------------------------------------------------------

    private function _check_db(string $key, int $max_hits, int $window_sec): array
    {
        $now = time();
        $window_start = $now - $window_sec;

        $row = $this->ci->db->where('rate_key', $key)->get('api_rate_limits')->row();

        if (!$row || $row->window_start < $window_start) {
            // New window — upsert with hits = 1
            $this->ci->db->query(
                "INSERT INTO api_rate_limits (rate_key, hits, window_start, created_at)
                 VALUES (?, 1, ?, NOW())
                 ON DUPLICATE KEY UPDATE hits = 1, window_start = ?",
                [$key, $now, $now]
            );
            return ['allowed' => true, 'remaining' => $max_hits - 1, 'reset_in' => $window_sec];
        }

        // Within the same window — increment
        $this->ci->db->where('rate_key', $key)->set('hits', 'hits + 1', false)->update('api_rate_limits');
        $hits = (int) $row->hits + 1;
        $reset_in = max(0, ($row->window_start + $window_sec) - $now);
        return [
            'allowed'   => $hits <= $max_hits,
            'remaining' => max(0, $max_hits - $hits),
            'reset_in'  => $reset_in,
        ];
    }
}

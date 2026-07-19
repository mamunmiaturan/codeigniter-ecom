<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Append-only login history. Used for security dashboards, anomalous-login
 * heuristics, and audit. Indexed for "last N logins for user X" and
 * "failures by IP in last 24h" queries.
 */
class Login_history_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function record(string $event, ?int $user_id, string $email, array $details = []): void
    {
        // Safe no-op on half-migrated deployments — auth must not break just
        // because the operator hasn't run `php artisan migrate` yet.
        if (!$this->db->table_exists('login_history')) {
            log_message('error', 'Login_history_model::record skipped — login_history table missing. Run migrations.');
            return;
        }

        $ip = (string) $this->input->ip_address();
        $ua = substr((string) $this->input->user_agent(), 0, 512);
        $device_hash = hash('sha256', $ip . '|' . $ua);

        $this->db->insert('login_history', [
            'user_id'     => $user_id,
            'email'       => substr($email, 0, 255),
            'event'       => $event,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
            'device_hash' => $device_hash,
            'details'     => $details === [] ? null : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function recent_for_user(int $user_id, int $limit = 20): array
    {
        return $this->db
            ->where('user_id', $user_id)
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get('login_history')
            ->result_array();
    }

    public function recent_failures_for_email(string $email, int $minutes = 15): int
    {
        $since = date('Y-m-d H:i:s', time() - $minutes * 60);
        return (int) $this->db
            ->where('email', $email)
            ->where('event', 'failure')
            ->where('created_at >=', $since)
            ->count_all_results('login_history');
    }
}

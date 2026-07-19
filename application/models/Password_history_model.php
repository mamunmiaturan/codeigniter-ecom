<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Password reuse-prevention store.
 *
 *  - record(): inserts the new hash; prunes everything beyond keep_last.
 *  - is_reused(): true if $plain_password matches any kept history hash.
 *
 * keep_last defaults to 10 — overridable via PASSWORD_HISTORY_KEEP env.
 */
class Password_history_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function record(int $credential_id, string $password_hash): void
    {
        if (!$this->db->table_exists('password_history')) {
            log_message('error', 'Password_history_model::record skipped — password_history table missing. Run migrations.');
            return;
        }
        $this->db->insert('password_history', [
            'login_credential_id' => $credential_id,
            'password_hash'       => $password_hash,
        ]);

        $keep = (int) (getenv('PASSWORD_HISTORY_KEEP') ?: 10);
        if ($keep < 1) $keep = 1;

        // Prune rows beyond the newest $keep — single delete query.
        $oldest_to_keep = $this->db
            ->select('id')
            ->from('password_history')
            ->where('login_credential_id', $credential_id)
            ->order_by('id', 'DESC')
            ->limit(1, $keep - 1)
            ->get()
            ->row();
        if ($oldest_to_keep) {
            $this->db
                ->where('login_credential_id', $credential_id)
                ->where('id <', (int) $oldest_to_keep->id)
                ->delete('password_history');
        }
    }

    public function is_reused(int $credential_id, string $plain_password): bool
    {
        if (!$this->db->table_exists('password_history')) {
            return false; // Fail-open on missing migration — let the change proceed.
        }
        $rows = $this->db
            ->select('password_hash')
            ->where('login_credential_id', $credential_id)
            ->order_by('id', 'DESC')
            ->get('password_history')
            ->result_array();
        foreach ($rows as $row) {
            if (password_verify($plain_password, $row['password_hash'])) {
                return true;
            }
        }
        return false;
    }
}

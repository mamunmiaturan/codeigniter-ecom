<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Refresh-token storage with rotation + reuse-detection.
 *
 * Tokens are never stored in plaintext — only their SHA-256 hash. When a
 * refresh token is presented, we atomically:
 *  1. Look it up by hash.
 *  2. If used_at IS NOT NULL → reuse attack: revoke the whole chain (the
 *     entire descendant tree from the original issuance).
 *  3. Mark it used_at = NOW(), issue a fresh token with parent_id = this row.
 */
class Api_refresh_token_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function issue(int $user_id, int $credential_id, string $scope, int $ttl_seconds, ?int $parent_id = null): array
    {
        if (!$this->db->table_exists('api_refresh_tokens')) {
            log_message('error', 'Api_refresh_token_model::issue blocked — api_refresh_tokens missing. Run migrations.');
            throw new RuntimeException('Refresh-token storage not initialized. Run migrations.');
        }
        $plain = bin2hex(random_bytes(32));
        $row = [
            'parent_id'           => $parent_id,
            'user_id'             => $user_id,
            'login_credential_id' => $credential_id,
            'refresh_token_hash'  => hash('sha256', $plain),
            'scope'               => $scope,
            'device_hash'         => $this->_device_hash(),
            'ip_address'          => $this->input->ip_address(),
            'user_agent'          => substr((string) $this->input->user_agent(), 0, 512),
            'expires_at'          => date('Y-m-d H:i:s', time() + max(60, $ttl_seconds)),
        ];
        $this->db->insert('api_refresh_tokens', $row);
        return [
            'id'    => (int) $this->db->insert_id(),
            'token' => $plain,
            'exp'   => $row['expires_at'],
        ];
    }

    /**
     * Atomically rotate. Returns ['user_id', 'credential_id', 'scope', 'parent_id']
     * on success. On reuse / expiry / revocation returns NULL after burning
     * the entire chain.
     */
    public function rotate(string $plain_token): ?array
    {
        if (!$this->db->table_exists('api_refresh_tokens')) {
            log_message('error', 'Api_refresh_token_model::rotate skipped — api_refresh_tokens missing.');
            return null; // Caller will translate to 401, never 500.
        }
        $hash = hash('sha256', $plain_token);
        $this->db->trans_start();
        $row = $this->db
            ->where('refresh_token_hash', $hash)
            ->get('api_refresh_tokens')
            ->row();
        if (!$row) {
            $this->db->trans_complete();
            return null;
        }
        if ($row->revoked_at !== null || strtotime($row->expires_at) < time()) {
            $this->db->trans_complete();
            return null;
        }
        if ($row->used_at !== null) {
            // Reuse detected → burn descendants.
            $this->_revoke_chain((int) $row->id);
            $this->db->trans_complete();
            log_message('error', 'Refresh-token reuse detected; chain revoked. user_id=' . $row->user_id);
            return null;
        }
        $this->db->where('id', $row->id)->update('api_refresh_tokens', [
            'used_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->trans_complete();
        return [
            'user_id'       => (int) $row->user_id,
            'credential_id' => (int) $row->login_credential_id,
            'scope'         => (string) $row->scope,
            'parent_id'     => (int) $row->id,
        ];
    }

    public function revoke_by_credential(int $credential_id): int
    {
        if (!$this->db->table_exists('api_refresh_tokens')) {
            return 0;
        }
        $this->db->where('login_credential_id', $credential_id)
            ->where('revoked_at', null)
            ->update('api_refresh_tokens', [
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);
        return (int) $this->db->affected_rows();
    }

    private function _revoke_chain(int $start_id): void
    {
        // Walk parent_id ancestors first, then revoke all descendants of the
        // ultimate root. Bounded loop to avoid runaway in case of cycles.
        $root = $start_id;
        $guard = 0;
        while ($guard++ < 50) {
            $parent = $this->db->select('parent_id')
                ->where('id', $root)
                ->get('api_refresh_tokens')
                ->row();
            if (!$parent || $parent->parent_id === null) {
                break;
            }
            $root = (int) $parent->parent_id;
        }

        // BFS over descendants from $root, revoking every row.
        $frontier = [$root];
        $now = date('Y-m-d H:i:s');
        while (!empty($frontier)) {
            $batch = array_splice($frontier, 0, 500);
            $this->db->where_in('id', $batch)
                ->where('revoked_at', null)
                ->update('api_refresh_tokens', ['revoked_at' => $now]);
            $children = $this->db
                ->select('id')
                ->where_in('parent_id', $batch)
                ->get('api_refresh_tokens')
                ->result_array();
            foreach ($children as $c) {
                $frontier[] = (int) $c['id'];
            }
        }
    }

    private function _device_hash(): string
    {
        $ip = (string) $this->input->ip_address();
        $ua = (string) $this->input->user_agent();
        // Subnet rather than full IP so a mobile user roaming WiFi doesn't lose tokens.
        $subnet = $ip;
        if (strpos($ip, ':') !== false) {
            $parts = explode(':', $ip);
            if (count($parts) >= 4) $subnet = implode(':', array_slice($parts, 0, 4));
        } else {
            $parts = explode('.', $ip);
            if (count($parts) >= 3) $subnet = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
        }
        return hash('sha256', $subnet . '|' . $ua);
    }
}

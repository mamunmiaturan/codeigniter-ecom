<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api_token_model extends MY_Model
{
    public function issue(int $user_id, int $credential_id, int $role_id, ?string $device_token = null, int $ttl_days = 30): array
    {
        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);
        $expires = date('Y-m-d H:i:s', time() + ($ttl_days * 86400));

        $this->db->insert('api_tokens', [
            'user_id'       => $user_id,
            'credential_id' => $credential_id,
            'role_id'       => $role_id,
            'token_hash'    => $hash,
            'device_token'  => $device_token,
            'expires_at'    => $expires,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return [
            'token'      => $plain,
            'expires_at' => $expires,
            'token_id'   => (int) $this->db->insert_id(),
        ];
    }

    public function resolve(string $plain_token): ?array
    {
        $hash = hash('sha256', $plain_token);
        $row = $this->db->get_where('api_tokens', ['token_hash' => $hash])->row_array();
        if (!$row || !empty($row['deleted_at'])) {
            return null;
        }
        if (strtotime($row['expires_at']) < time()) {
            return null;
        }

        $this->db->where('id', $row['id'])->update('api_tokens', [
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);

        $profile = $this->db->select('u.id, u.name, u.mobile_no, u.branch_id, u.photo, lc.email, lc.role')
            ->from('users u')
            ->join('login_credential lc', 'lc.user_id = u.id AND lc.id = ' . (int) $row['credential_id'], 'inner')
            ->where('u.id', $row['user_id'])
            ->where('u.deleted_at IS NULL', null, false)
            ->get()
            ->row_array();

        if (!$profile || ($profile['role'] ?? 0) != $row['role_id']) {
            return null;
        }

        return [
            'token_row' => $row,
            'profile'   => $profile,
        ];
    }

    public function revoke(string $plain_token): bool
    {
        $hash = hash('sha256', $plain_token);
        return $this->db->where('token_hash', $hash)->update('api_tokens', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function revoke_all_for_user(int $user_id): void
    {
        $this->db->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->update('api_tokens', ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package    Authentication
 * @author     Mamun Mia Turan
 * @filename   User_2fa_model.php
 *
 * All DB operations for the user_2fa table live here.
 * Controllers must not query this table directly.
 */
class User_2fa_model extends MY_Model
{
    protected $table = 'user_2fa';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the user_2fa row for a given user, or NULL if none exists.
     */
    public function get_by_user_id(int $user_id)
    {
        return $this->db->where('user_id', $user_id)->get($this->table)->row();
    }

    /**
     * Return the user_2fa row only when 2FA is enabled, or NULL.
     */
    public function get_enabled_by_user_id(int $user_id)
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('enabled', 1)
            ->get($this->table)
            ->row();
    }

    /**
     * Insert or update the 2FA record for a user.
     * $data keys: secret (already encrypted), enabled, backup_codes (plaintext JSON)
     */
    public function upsert(int $user_id, array $data): void
    {
        if (isset($data['backup_codes']) && $data['backup_codes'] !== null) {
            $data['backup_codes'] = $this->_encrypt_backup_codes($data['backup_codes']);
        }
        $existing = $this->get_by_user_id($user_id);
        if ($existing) {
            $this->db->where('user_id', $user_id)->update($this->table, $data);
        } else {
            $data['user_id']    = $user_id;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert($this->table, $data);
        }
    }

    /**
     * Enable or disable 2FA for a user. Also clears backup_codes on disable.
     */
    public function set_enabled(int $user_id, bool $enabled): void
    {
        $update = ['enabled' => (int) $enabled];
        if (!$enabled) {
            $update['backup_codes'] = null;
        }
        $this->db->where('user_id', $user_id)->update($this->table, $update);
    }

    /**
     * Overwrite the backup codes JSON for a user. $codes_json is plaintext JSON.
     */
    public function update_backup_codes(int $user_id, string $codes_json): void
    {
        $this->db->where('user_id', $user_id)->update($this->table, [
            'backup_codes' => $this->_encrypt_backup_codes($codes_json),
        ]);
    }

    /**
     * Decrypt stored backup_codes ciphertext to a JSON string.
     * Returns '[]' for null/empty; transparently handles legacy plaintext.
     */
    public function decrypt_backup_codes(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '[]';
        }
        return totp_decrypt_secret($stored);
    }

    /**
     * Return backup-codes remaining count for a user.
     */
    public function backup_code_count(int $user_id): int
    {
        $row = $this->get_by_user_id($user_id);
        if (!$row || !$row->backup_codes) {
            return 0;
        }
        $codes = json_decode($this->decrypt_backup_codes($row->backup_codes), true);
        return is_array($codes) ? count($codes) : 0;
    }

    private function _encrypt_backup_codes(string $json): string
    {
        return totp_encrypt_secret($json);
    }
}

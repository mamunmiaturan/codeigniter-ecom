<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stores past bcrypt hashes so we can refuse password reuse on change.
 * Retention: only the last N (default 10) entries per credential — pruned
 * after every insert by the Password_history_model.
 */
class Migration_Create_Password_History_Table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('password_history')) {
            return;
        }
        $sql = <<<'SQL'
CREATE TABLE `password_history` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `login_credential_id` INT NOT NULL,
    `password_hash`      VARCHAR(255) NOT NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_credential_created` (`login_credential_id`, `created_at`),
    CONSTRAINT `fk_password_history_credential`
        FOREIGN KEY (`login_credential_id`) REFERENCES `login_credential` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $this->db->query($sql);

        // Backfill: capture the current password as the first history row so
        // the very next change can't reuse it. Best-effort — skipped on empty.
        $rows = $this->db->select('id, password')
            ->from('login_credential')
            ->where('deleted_at', null)
            ->get()
            ->result_array();
        foreach ($rows as $row) {
            if (!empty($row['password'])) {
                $this->db->insert('password_history', [
                    'login_credential_id' => $row['id'],
                    'password_hash'       => $row['password'],
                ]);
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('password_history', TRUE);
    }
}

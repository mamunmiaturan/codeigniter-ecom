<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Per-user immutable login history (success + failure).
 *
 * Used by the security dashboard, "where have I logged in from?" UI, and
 * anomalous-login detection. We index by user_id + created_at DESC for the
 * "last N logins for user X" query.
 */
class Migration_Create_Login_History_Table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('login_history')) {
            return;
        }
        $sql = <<<'SQL'
CREATE TABLE `login_history` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT NULL COMMENT 'FK users.id (NULL for unknown-email attempts)',
    `email`          VARCHAR(255) NOT NULL COMMENT 'Whichever email was tried',
    `event`          ENUM('success','failure','blocked','2fa_failure','2fa_success','impersonation','restore_previous','logout') NOT NULL,
    `ip_address`     VARCHAR(45) NOT NULL,
    `user_agent`     VARCHAR(512) NULL,
    `device_hash`    CHAR(64) NULL COMMENT 'sha256(ip-subnet|ua) — for device manager',
    `country`        CHAR(2) NULL COMMENT 'Optional GeoIP enrichment',
    `details`        JSON NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_created` (`user_id`, `created_at`),
    INDEX `idx_email_created` (`email`, `created_at`),
    INDEX `idx_event_created` (`event`, `created_at`),
    INDEX `idx_ip_created` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('login_history', TRUE);
    }
}

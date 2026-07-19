<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API access + refresh tokens for the JWT auth layer.
 *
 *  - access_token_hash : SHA-256 of the issued JWT's "jti" claim (so we can
 *                       revoke single tokens without storing the full JWT).
 *  - refresh_token_hash: SHA-256 of the opaque refresh token (rotated on use).
 *  - scope            : space-separated string of scopes for fine-grained checks.
 *  - parent_id        : self-reference for refresh-token rotation chain — if a
 *                       previously-rotated token is presented again it means
 *                       reuse-detection has fired and we revoke the whole chain.
 */
class Migration_Create_Api_Tokens_Table extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('api_refresh_tokens')) {
            $sql = <<<'SQL'
CREATE TABLE `api_refresh_tokens` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id`          BIGINT UNSIGNED NULL,
    `user_id`            INT NOT NULL,
    `login_credential_id` INT NOT NULL,
    `refresh_token_hash` CHAR(64) NOT NULL,
    `scope`              VARCHAR(255) NOT NULL DEFAULT '',
    `device_hash`        CHAR(64) NULL,
    `ip_address`         VARCHAR(45) NULL,
    `user_agent`         VARCHAR(512) NULL,
    `expires_at`         DATETIME NOT NULL,
    `used_at`            DATETIME NULL,
    `revoked_at`         DATETIME NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_refresh_token_hash` (`refresh_token_hash`),
    INDEX `idx_user_expires` (`user_id`, `expires_at`),
    INDEX `idx_credential` (`login_credential_id`),
    INDEX `idx_parent` (`parent_id`),
    CONSTRAINT `fk_refresh_token_credential`
        FOREIGN KEY (`login_credential_id`) REFERENCES `login_credential` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
            $this->db->query($sql);
        }

        if (!$this->db->table_exists('api_revoked_jti')) {
            $sql = <<<'SQL'
CREATE TABLE `api_revoked_jti` (
    `jti_hash`   CHAR(64) NOT NULL,
    `revoked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL COMMENT 'When this row can be GC-pruned',
    PRIMARY KEY (`jti_hash`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
            $this->db->query($sql);
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('api_revoked_jti', TRUE);
        $this->dbforge->drop_table('api_refresh_tokens', TRUE);
    }
}

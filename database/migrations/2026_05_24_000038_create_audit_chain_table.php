<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Hash-chained activity log integrity.
 *
 * Every entry's hash = HMAC-SHA256(SECURITY_KEY, prev_hash || JSON(payload)).
 * A separate table (not a column on activity_logs) means we can rebuild
 * activity_logs from the file-based NDJSON without invalidating the chain,
 * and adversaries who only get DB write can't silently truncate history
 * without leaving a gap.
 *
 * Verification: walk rows ordered by id ASC, recompute hash; first mismatch
 * is the tamper point.
 */
class Migration_Create_Audit_Chain_Table extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('audit_chain')) {
            return;
        }
        $sql = <<<'SQL'
CREATE TABLE `audit_chain` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `prev_hash`    CHAR(64) NOT NULL DEFAULT '',
    `payload_hash` CHAR(64) NOT NULL COMMENT 'sha256 of canonical payload JSON',
    `chain_hash`   CHAR(64) NOT NULL COMMENT 'HMAC over prev_hash||payload_hash',
    `payload`      JSON NOT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('audit_chain', TRUE);
    }
}

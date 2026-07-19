<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Backup Model - executes SQL statements parsed from a backup file.
 *
 * Defense-in-depth: even though Backup::restore_file already runs an
 * allowlist, this model also rejects destructive statements so any future
 * caller that forgets the controller-side check cannot wipe the database.
 */
class Backup_model extends MY_Model
{
    /**
     * Statement prefixes that may NEVER be executed via this model.
     * Match is case-insensitive and applied to the trimmed start of the SQL.
     */
    private const DENIED_PREFIXES = [
        'DROP ', 'TRUNCATE', 'GRANT ', 'REVOKE ', 'RENAME ',
        'ALTER ', 'DELETE ', 'UPDATE ', 'CALL ', 'LOAD DATA',
        'HANDLER ', 'CREATE USER', 'CREATE DEFINER',
        'CREATE TRIGGER', 'CREATE PROCEDURE', 'CREATE FUNCTION',
        'CREATE EVENT', 'CREATE VIEW',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run a single SQL statement from a restored backup file.
     * Returns FALSE if the statement is blocked or the query fails.
     */
    public function run_query($sql)
    {
        $normalized = strtoupper(ltrim((string) $sql));
        foreach (self::DENIED_PREFIXES as $bad) {
            if (strpos($normalized, $bad) === 0) {
                log_message('error', 'Backup_model::run_query rejected destructive statement: '
                    . substr((string) $sql, 0, 120));
                return false;
            }
        }
        return $this->db->query($sql);
    }
}

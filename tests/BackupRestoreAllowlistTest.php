<?php
use PHPUnit\Framework\TestCase;

/**
 * Verifies that Backup_model::run_query refuses destructive SQL even if the
 * controller-side allowlist is bypassed (defense-in-depth check from CRIT-A2).
 *
 * We do not actually hit a database — we substitute a stub $db on the model
 * via reflection so we can see exactly which statements would have been run.
 */
class BackupRestoreAllowlistTest extends TestCase
{
    private function makeModel()
    {
        if (!class_exists('CI_Model')) {
            eval('#[\AllowDynamicProperties] class CI_Model { public function __construct() {} public function __get($k) { return $this->$k ?? null; } }');
        }
        if (!class_exists('Base_Model')) {
            eval('#[\AllowDynamicProperties] class Base_Model extends CI_Model { protected $table = "x"; public function __construct() { parent::__construct(); } }');
        }
        if (!class_exists('MY_Model')) {
            eval('#[\AllowDynamicProperties] class MY_Model extends Base_Model { public function __construct() { parent::__construct(); } }');
        }
        require_once APPPATH . 'models/Backup_model.php';
        if (!class_exists('TestableBackupModel')) {
            eval('#[\AllowDynamicProperties] class TestableBackupModel extends Backup_model {}');
        }

        $model = new TestableBackupModel();
        $model->db = new class {
            public $executed = [];
            public function query($sql) { $this->executed[] = $sql; return true; }
        };
        return $model;
    }

    /** @dataProvider blockedStatements */
    public function test_blocks_destructive_statements($sql)
    {
        $model = $this->makeModel();
        $result = $model->run_query($sql);
        $this->assertFalse($result, "Statement should be blocked: $sql");
        $this->assertCount(0, $model->db->executed, "No SQL should reach the DB for: $sql");
    }

    public function blockedStatements(): array
    {
        return [
            'DROP TABLE'        => ['DROP TABLE users;'],
            'DROP TABLE lower'  => ['drop table users;'],
            'TRUNCATE'          => ['TRUNCATE TABLE login_credential;'],
            'GRANT'             => ['GRANT ALL ON db.* TO attacker@%;'],
            'REVOKE'            => ['REVOKE SELECT ON db.* FROM user;'],
            'RENAME'            => ['RENAME TABLE users TO pwned;'],
            'ALTER'             => ['ALTER TABLE users ADD COLUMN backdoor TEXT;'],
            'DELETE'            => ['DELETE FROM users;'],
            'UPDATE'            => ['UPDATE users SET role = 1;'],
            'CALL'              => ['CALL malicious_proc();'],
            'LOAD DATA'         => ['LOAD DATA INFILE "/etc/passwd" INTO TABLE users;'],
            'CREATE USER'       => ['CREATE USER attacker@% IDENTIFIED BY "x";'],
            'CREATE TRIGGER'    => ['CREATE TRIGGER t BEFORE INSERT ON users FOR EACH ROW BEGIN END;'],
            'CREATE PROCEDURE'  => ['CREATE PROCEDURE p() BEGIN END;'],
            'CREATE FUNCTION'   => ['CREATE FUNCTION f() RETURNS INT RETURN 0;'],
            'CREATE EVENT'      => ['CREATE EVENT e ON SCHEDULE EVERY 1 HOUR DO SELECT 1;'],
            'CREATE VIEW'       => ['CREATE VIEW v AS SELECT * FROM users;'],
            'leading whitespace + DROP' => ["   DROP TABLE login_credential;"],
        ];
    }

    /** @dataProvider allowedStatements */
    public function test_allows_safe_statements($sql)
    {
        $model = $this->makeModel();
        $result = $model->run_query($sql);
        $this->assertNotFalse($result, "Statement should be allowed: $sql");
        $this->assertCount(1, $model->db->executed);
    }

    public function allowedStatements(): array
    {
        return [
            'INSERT'              => ["INSERT INTO users (id, name) VALUES (1, 'alice');"],
            'SET FOREIGN_KEY'     => ["SET FOREIGN_KEY_CHECKS = 0;"],
            'SET NAMES'           => ["SET NAMES utf8mb4;"],
            'LOCK TABLES'         => ["LOCK TABLES users WRITE;"],
            'UNLOCK TABLES'       => ["UNLOCK TABLES;"],
        ];
    }
}

<?php
use PHPUnit\Framework\TestCase;

class DbIntegrationTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        if (!function_exists('show_error')) {
            function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered') {
                throw new \Exception($message);
            }
        }
        
        $params = array(
            'dsn'      => 'sqlite::memory:',
            'hostname' => '',
            'username' => '',
            'password' => '',
            'database' => ':memory:',
            'dbdriver' => 'pdo',
            'dbprefix' => '',
            'pconnect' => FALSE,
            'db_debug' => TRUE,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt'  => FALSE,
            'compress' => FALSE,
            'stricton' => FALSE,
            'failover' => array(),
            'save_queries' => TRUE
        );

        require_once BASEPATH . 'database/DB.php';
        $this->db = DB($params, TRUE);

        // Setup a mock schema
        $this->db->query("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
    }

    public function testDbInsertAndSelect()
    {
        $this->db->insert('users', ['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->assertEquals(1, $this->db->affected_rows());

        $query = $this->db->get_where('users', ['email' => 'john@example.com']);
        $row = $query->row();
        
        $this->assertNotNull($row);
        $this->assertEquals('John Doe', $row->name);
    }
    
    public function testQueryBuilderUpdate()
    {
        $this->db->insert('users', ['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $this->db->where('email', 'jane@example.com');
        $this->db->update('users', ['name' => 'Jane Smith']);
        
        $query = $this->db->get_where('users', ['email' => 'jane@example.com']);
        $row = $query->row();
        
        $this->assertEquals('Jane Smith', $row->name);
    }

    public function testQueryBuilderDelete()
    {
        $this->db->insert('users', ['name' => 'Delete Me', 'email' => 'delete@example.com']);
        $this->db->where('email', 'delete@example.com');
        $this->db->delete('users');
        
        // Assert on the result set, not num_rows(): PDO's rowCount() reports the
        // last DML statement's count for SQLite SELECTs, so num_rows() would
        // return the DELETE's affected-row count here rather than 0.
        $query = $this->db->get_where('users', ['email' => 'delete@example.com']);
        $this->assertCount(0, $query->result_array());
    }
}

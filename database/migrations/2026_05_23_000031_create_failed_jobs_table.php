<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_failed_jobs_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => TRUE,
                'auto_increment' => TRUE,
            ],
            'queue' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'default'    => 'default',
            ],
            'payload' => [
                'type' => 'LONGTEXT',
                'null' => FALSE,
            ],
            'exception' => [
                'type' => 'LONGTEXT',
                'null' => TRUE,
            ],
            'attempts' => [
                'type'     => 'TINYINT',
                'unsigned' => TRUE,
                'default'  => 0,
            ],
            'failed_at' => [
                'type' => 'DATETIME',
                'null' => FALSE,
            ],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('failed_jobs', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('failed_jobs', TRUE);
    }
}

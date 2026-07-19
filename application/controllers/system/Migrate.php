<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Migrate.php
 */
class Migrate extends CI_Controller {
    
    public $migration;
    public $dbforge;
    public $input;
    public $config;
    public $load;
    public $db;
    public $lang;

    public function __construct()
    {
        parent::__construct();
        
        // Only allow CLI access
        if (!$this->input->is_cli_request()) {
            exit("This controller can only be accessed via CLI." . PHP_EOL);
        }

        $this->config->load('migration');
        $this->load->library('migration');
        $this->load->model('migration_model');
    }

    /**
     * Default action: migrate to latest
     */
    public function index()
    {
        $this->latest();
    }

    /**
     * Migrate to the latest version
     */
    public function latest()
    {
        echo "Running migrations to latest version..." . PHP_EOL;
        
        // Ensure migration table exists (especially after fresh)
        $this->_setup_migration_table();

        if ($this->migration->latest() === FALSE) {
            echo "Error: " . $this->migration->error_string() . PHP_EOL;
        } else {
            echo "Migrations completed successfully." . PHP_EOL;
        }
    }

    /**
     * Ensure the migration table exists and optionally reset it
     */
    private function _setup_migration_table($reset = FALSE)
    {
        $table = $this->config->item('migration_table');
        if (empty($table)) {
            $table = 'migrations';
        }
        
        $exists = $this->migration_model->table_exists($table);

        if (!$exists) {
            echo "Creating migration table: $table" . PHP_EOL;
            $this->migration_model->create_migration_table($table);
        } elseif ($reset) {
            $this->migration_model->reset_migration_version($table);
            echo "Reset migration version to 0." . PHP_EOL;
        }
    }

    /**
     * Migrate to a specific version
     * 
     * @param string $v Version timestamp or sequence
     */
    public function version($v)
    {
        echo "Migrating to version $v..." . PHP_EOL;
        
        $this->_setup_migration_table();

        if ($this->migration->version($v) === FALSE) {
            echo "Error: " . $this->migration->error_string() . PHP_EOL;
        } else {
            echo "Migration to version $v completed successfully." . PHP_EOL;
        }
    }

    public function fresh()
    {
        // Safeguard for production environment
        if (ENVIRONMENT === 'production' && !in_array('--force', $_SERVER['argv'])) {
            echo "*****************************************************************" . PHP_EOL;
            echo "* Error: You are in PRODUCTION environment.                     *" . PHP_EOL;
            echo "* Running migrate:fresh will DESTROY all your data.             *" . PHP_EOL;
            echo "* If you really want to do this, use: php artisan migrate:fresh --force *" . PHP_EOL;
            echo "*****************************************************************" . PHP_EOL;
            return;
        }

        echo "Dropping all tables..." . PHP_EOL;
        
        // Disable foreign key checks using migration_model
        $this->migration_model->disable_foreign_key_checks();
        
        $tables = $this->migration_model->list_tables();
        $migration_table = $this->config->item('migration_table') ?: 'migrations';

        foreach ($tables as $table) {
            // Do not drop the migrations table itself
            if ($table === $migration_table) {
                continue;
            }
            if ($this->migration_model->drop_table($table)) {
                echo "Dropped table: $table" . PHP_EOL;
            }
        }
        
        // Reset migration version to 0
        $this->_setup_migration_table(TRUE);
        
        // Enable foreign key checks using migration_model
        $this->migration_model->enable_foreign_key_checks();

        echo "Tables dropped. Re-migrating..." . PHP_EOL;
        
        $this->latest();

        // Check if --seed was passed in CLI arguments
        if (in_array('--seed', $_SERVER['argv'])) {
            $this->seed();
        }
    }

    /**
     * Seed the database
     * 
     * @param string $name Seeder class name
     */
    public function seed($name = 'DatabaseSeeder')
    {
        if (empty($name)) {
            $name = 'DatabaseSeeder';
        }

        echo "Seeding database with $name..." . PHP_EOL;
        
        // Include base seeder
        require_once APPPATH . 'core/Seeder.php';
        
        $file = FCPATH . 'database/seeders/' . $name . '.php';
        if (file_exists($file)) {
            require_once $file;
            if (class_exists($name)) {
                $seeder = new $name();
                $seeder->run();
                echo "Seeding completed successfully." . PHP_EOL;
            } else {
                echo "Error: Class $name not found in $file" . PHP_EOL;
            }
        } else {
            echo "Error: Seeder file not found at $file" . PHP_EOL;
        }
    }

    /**
     * Check migration status
     */
    public function status()
    {
        $table = $this->config->item('migration_table');
        if (!$this->migration_model->table_exists($table)) {
            echo "Migrations table does not exist. Run migrate to initialize." . PHP_EOL;
            return;
        }

        $current_version = $this->migration_model->get_migration_version($table);
        if ($current_version !== null) {
            echo "Current migration version: $current_version" . PHP_EOL;
        } else {
            echo "No migrations have been run yet (version 0)." . PHP_EOL;
        }
    }

    /**
     * Fix auto_increment and primary key in all migration files
     */
    public function fix()
    {
        echo "Fixing auto_increment and primary keys in migration files..." . PHP_EOL;
        
        $dir = FCPATH . 'database/migrations/';
        if (!is_dir($dir)) {
            echo "Error: Migrations directory not found at $dir" . PHP_EOL;
            return;
        }

        $files = scandir($dir);
        $count = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || substr($file, -4) !== '.php') {
                continue;
            }
            
            $path = $dir . $file;
            $content = file_get_contents($path);
            
            $modified = false;
            
            // Pattern 1: match integer `id` lines inside CREATE TABLE raw SQL and add AUTO_INCREMENT PRIMARY KEY
            $pattern = '/(`id`\s+(?:int|bigint|mediumint|tinyint|smallint)(?:\(\d+\))?(?:\s+unsigned)?\s+NOT\s+NULL),/i';
            if (preg_match($pattern, $content) && strpos($content, 'AUTO_INCREMENT') === false && strpos($content, 'PRIMARY KEY') === false) {
                $content = preg_replace($pattern, '$1 AUTO_INCREMENT PRIMARY KEY,', $content);
                $modified = true;
            }
            
            // Pattern 2: match varchar `id` lines in raw SQL and add PRIMARY KEY
            $pattern_session = '/(`id`\s+varchar\(\d+\)\s+NOT\s+NULL),/i';
            if (preg_match($pattern_session, $content) && strpos($content, 'PRIMARY KEY') === false) {
                $content = preg_replace($pattern_session, '$1 PRIMARY KEY,', $content);
                $modified = true;
            }
            
            if ($modified) {
                file_put_contents($path, $content);
                echo "Fixed migration: $file" . PHP_EOL;
                $count++;
            }
        }

        echo "Total migration files fixed: $count" . PHP_EOL;

        // Fix InitialDataSeeder to make it idempotent
        $seeder_file = FCPATH . 'database/seeders/InitialDataSeeder.php';
        if (file_exists($seeder_file)) {
            echo "Making InitialDataSeeder idempotent (INSERT IGNORE)..." . PHP_EOL;
            $seeder_content = file_get_contents($seeder_file);
            $updated_seeder_content = str_ireplace('INSERT INTO `', 'INSERT IGNORE INTO `', $seeder_content);
            if ($updated_seeder_content !== $seeder_content) {
                file_put_contents($seeder_file, $updated_seeder_content);
                echo "Successfully made InitialDataSeeder idempotent!" . PHP_EOL;
            } else {
                echo "InitialDataSeeder is already idempotent." . PHP_EOL;
            }
        }
    }
}

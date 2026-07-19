<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custom Migration Library to support underscored timestamps
 */
class MY_Migration extends CI_Migration {

    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Extracts the migration number from a filename
     *
     * @param	string	$migration
     * @return	string	numeric portion of a migration filename
     */
    protected function _get_migration_number($migration)
    {
        // Support YYYY_MM_DD_HHIISS format
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_/', $migration, $match)) {
            return str_replace('_', '', $match[1]);
        }
        
        // Default CI behavior
        return $this->_migration_type === 'timestamp'
            ? substr($migration, 0, 14)
            : substr($migration, 0, 3);
    }

    /**
     * Extracts the migration class name from a filename
     *
     * @param	string	$migration
     * @return	string	text portion of a migration filename
     */
    protected function _get_migration_name($migration)
    {
        // Support YYYY_MM_DD_HHIISS_... format
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $migration, $match)) {
            return $match[1];
        }
        
        return parent::_get_migration_name($migration);
    }

    /**
     * Retrieves a list of available migration scripts
     *
     * @return	array	list of migration file paths sorted by version
     */
    public function find_migrations()
    {
        $migrations = array();

        // Search for both underscored and standard timestamp/sequential formats
        foreach (glob($this->_migration_path . '*.php') as $file)
        {
            $name = basename($file, '.php');

            // Support YYYY_MM_DD_HHIISS format
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(\w+)$/', $name, $match))
            {
                $number = str_replace('_', '', $match[1]);
            }
            // Support standard timestamp (14 digits)
            elseif (preg_match('/^(\d{14})_(\w+)$/', $name, $match))
            {
                $number = $match[1];
            }
            // Support standard sequential (3 digits)
            elseif (preg_match('/^(\d{3})_(\w+)$/', $name, $match))
            {
                $number = $match[1];
            }
            else
            {
                continue;
            }

            if (isset($migrations[$number]))
            {
                $this->_error_string = sprintf($this->lang->line('migration_multiple_version'), $number);
                return FALSE;
            }

            $migrations[$number] = $file;
        }

        ksort($migrations);
        return $migrations;
    }
    /**
     * Migrate to a schema version
     * Overridden to fix class name issues and PHP 8 compatibility
     */
    public function version($target_version)
    {
        // Note: We use strings, so that timestamp versions work on 32-bit systems
        $current_version = $this->_get_version();

        if ($this->_migration_type === 'sequential') {
            $target_version = sprintf('%03d', $target_version);
        } else {
            $target_version = (string) $target_version;
        }

        $migrations = $this->find_migrations();

        if ($target_version > 0 && !isset($migrations[$target_version])) {
            $this->_error_string = sprintf($this->lang->line('migration_not_found'), $target_version);
            return FALSE;
        }

        if ($target_version > $current_version) {
            $method = 'up';
        } elseif ($target_version < $current_version) {
            $method = 'down';
            krsort($migrations);
        } else {
            return TRUE;
        }

        $pending = array();
        foreach ($migrations as $number => $file) {
            if ($method === 'up') {
                if ($number <= $current_version) continue;
                elseif ($number > $target_version) break;
            } else {
                if ($number > $current_version) continue;
                elseif ($number <= $target_version) break;
            }

            include_once($file);
            
            // Try different class name variations
            $name = $this->_get_migration_name(basename($file, '.php'));
            $class_variations = array(
                'Migration_' . ucfirst(strtolower($name)),
                'Migration_' . ucfirst($name),
                'Migration_' . $name,
                'Migration_' . str_replace(' ', '_', ucwords(str_replace('_', ' ', strtolower($name)))),
                'Migration_' . preg_replace('/(?<!^)(?=[A-Z])/', '_', $name), // PascalCase to Underscored_Snake_Case
            );

            $class = FALSE;
            foreach ($class_variations as $v) {
                if (class_exists($v, FALSE)) {
                    $class = $v;
                    break;
                }
            }

            if (!$class) {
                $this->_error_string = sprintf($this->lang->line('migration_class_doesnt_exist'), 'Migration_' . ucfirst(strtolower($name)));
                return FALSE;
            }

            // Use method_exists instead of is_callable for non-static methods on class strings
            if (!method_exists($class, $method)) {
                $this->_error_string = sprintf($this->lang->line('migration_missing_' . $method . '_method'), $class);
                return FALSE;
            }

            $pending[$number] = array($class, $method);
        }

        foreach ($pending as $number => $migration) {
            log_message('debug', 'Migrating ' . $method . ' from version ' . $current_version . ' to version ' . $number);

            $obj = new $migration[0];
            $obj->{$migration[1]}();
            
            $current_version = $number;
            $this->_update_version($current_version);
        }

        if ($current_version <> $target_version) {
            $current_version = $target_version;
            $this->_update_version($current_version);
        }

        return $current_version;
    }
}

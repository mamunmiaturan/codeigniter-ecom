<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Backup.php
 */

class Backup extends Admin_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->helpers('download');
		$this->load->model('backup_model');
	}

	public function index()
	{
		// check access permission
		if (!get_permission('database_backup', 'is_view')) {
			access_denied();
		}
		if (isset($_POST['backup'])) {
			if (!get_permission('database_backup', 'is_add')) {
				access_denied();
			}

			$path = './uploads/db_backup/';
			$file_name = 'db-backup_' . date('Y-m-d-H-i-s');
			if (!is_really_writable($path)) {
				set_alert('error', 'Backups folder is not writable. You need to change the permissions to 755');
				redirect(base_url('backup'));
			}

			$this->load->dbutil();
			$config = array(
				'ignore' => array(),
				'format' => 'zip', // gzip, zip, txt
				'add_drop' => TRUE, // Whether to add DROP TABLE statements to backup file
				'add_insert' => TRUE, // Whether to add INSERT data to backup file
				'filename' => $file_name . '.sql',
				'newline' => "\n"
			);
			$backup = $this->dbutil->backup($config);
			write_file($path . $file_name . '.zip', $backup);
			set_alert('success', "Database Backup Completed");
			redirect(base_url('backup'));
		}
		$this->data['title'] = translate('settings');
		$this->data['sub_page'] = 'settings/backup/index';
		$this->data['main_menu'] = 'settings';
		$this->load->view('layout/index', $this->data);
	}

	// backup zip file download function
	public function download()
	{
		if (!get_permission('database_backup', 'is_add')) {
			access_denied();
		}
		$file = basename($this->input->get('file'));
		$path = './uploads/db_backup/' . $file;
		if (file_exists($path)) {
			$this->data = file_get_contents($path);
			force_download($file, $this->data);
		} else {
			set_alert('error', 'File not found');
		}
		redirect(base_url('backup'));
	}

	// backup file delete function
	public function delete($file)
	{
		if (!get_permission('database_backup', 'is_delete')) {
			access_denied();
		}
		$file = basename($file);
		$path = './uploads/db_backup/' . $file;
		if (file_exists($path)) {
			unlink($path);
			set_alert('success', 'File deleted successfully');
		}
		redirect(base_url('backup'));
	}

	// backup restore file function
	public function restore_file()
	{
		if (!get_permission('database_restore', 'is_add')) {
			access_denied();
		}
		$this->load->helper('unzip');
		$config['upload_path'] = './uploads/db_temp/';
		$config['allowed_types'] = 'zip';
		$config['overwrite'] = TRUE;
		$this->upload->initialize($config);
		if (!$this->upload->do_upload('uploaded_file')) {
			$error = $this->upload->display_errors('', ' ');
			set_alert('error', $error);
			redirect(base_url('backup'));
		} else {
			$data 	= array('upload_data' => $this->upload->data());
			$backup = "uploads/db_temp/" . $data['upload_data']['file_name'];
		}
		if (!unzip($backup, "uploads/db_temp/", TRUE)) {
			set_alert('error', "Backup Restore Error");
			redirect(base_url('backup'));
		} else {
			$backup = str_replace('.zip', '', $backup);
			$templine = '';
			$errors = [];
			// Read in entire file
			$lines = file($backup . ".sql");
			// Loop through each line
			foreach ($lines as $line) {
				if (substr($line, 0, 2) == '--' || $line == '')
					continue;
				$templine .= $line;
				// If it has a semicolon at the end, it's the end of the query so can process this templine
				if (substr(trim($line), -1, 1) == ';') {
					// Strict SQL allowlist. Destructive DDL (DROP / TRUNCATE / GRANT /
					// REVOKE / RENAME / ALTER) is rejected so a tampered backup cannot
					// wipe tables, escalate DB privileges, or run arbitrary UPDATE/DELETE.
					$normalized = strtoupper(ltrim($templine));
					$allowed_prefixes = [
						'INSERT INTO',
						'CREATE TABLE',
						'CREATE INDEX',
						'CREATE UNIQUE INDEX',
						'SET ',
						'LOCK TABLES',
						'UNLOCK TABLES',
						'/*!',
						'--',
					];
					$denied_prefixes = [
						'DROP ', 'TRUNCATE', 'GRANT ', 'REVOKE ', 'RENAME ',
						'ALTER ', 'DELETE ', 'UPDATE ', 'CALL ', 'LOAD DATA',
						'HANDLER ', 'CREATE USER', 'CREATE DEFINER',
						'CREATE TRIGGER', 'CREATE PROCEDURE', 'CREATE FUNCTION',
						'CREATE EVENT', 'CREATE VIEW',
					];

					$is_denied = false;
					foreach ($denied_prefixes as $bad) {
						if (strpos($normalized, $bad) === 0) {
							$is_denied = true;
							$errors[] = 'Blocked destructive statement: ' . substr($templine, 0, 80) . '...';
							break;
						}
					}

					$is_allowed = false;
					if (!$is_denied) {
						foreach ($allowed_prefixes as $good) {
							if (strpos($normalized, $good) === 0) {
								$is_allowed = true;
								break;
							}
						}
					}

					if ($is_allowed) {
						if (!$this->backup_model->run_query($templine)) {
							$errors[] = 'Query failed: ' . substr($templine, 0, 100) . '...';
						}
					}
					$templine = '';
				}
			}
			if (!empty($errors)) {
				set_alert('warning', count($errors) . ' statement(s) failed or were skipped during restore. Check system logs.');
				log_message('error', 'Backup restore errors: ' . implode(' | ', $errors));
			} else {
				set_alert('success', translate('the_configuration_has_been_updated'));
			}
		}

		unlink($backup . '.sql');
		unlink($backup . '.zip');
		redirect(base_url('backup'));
	}
}

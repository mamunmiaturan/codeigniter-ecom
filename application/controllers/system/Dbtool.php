<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Dbtool.php
 */
class Dbtool extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Only allow CLI access
        if (!$this->input->is_cli_request()) {
            exit("This controller can only be accessed via CLI." . PHP_EOL);
        }

        $this->load->dbutil();
        $this->load->helper('file');
    }

    /**
     * Run a full database backup
     * Usage: php artisan db:backup
     */
    public function backup()
    {
        echo "--- Starting Database Backup ---" . PHP_EOL;

        $prefs = [
            'format' => 'zip',
            'filename' => 'backup_' . date('Y-m-d_H-i-s') . '.sql',
            'add_drop' => TRUE,
            'add_insert' => TRUE,
            'newline' => "\n"
        ];

        $backup = $this->dbutil->backup($prefs);
        $db_name = $this->db->database;
        $save_path = FCPATH . 'database/backups/' . $db_name . '_' . date('Y-m-d_H-i-s') . '.zip';

        if (write_file($save_path, $backup)) {
            echo "Backup successfully created: " . basename($save_path) . PHP_EOL;
            echo "Path: " . $save_path . PHP_EOL;
            
            // Optional: Email the backup if configured
            $this->_email_backup($save_path);
        } else {
            echo "Error: Could not write backup file." . PHP_EOL;
        }
    }

    /**
     * Optimize all tables in the database
     */
    public function optimize()
    {
        echo "Optimizing database tables... ";
        $result = $this->dbutil->optimize_database();
        if ($result) {
            echo "Done." . PHP_EOL;
        } else {
            echo "Failed." . PHP_EOL;
        }
    }

    /**
     * Send backup to admin email
     */
    private function _email_backup($file_path)
    {
        $to = getenv('SMTP_FROM_EMAIL');
        if (empty($to)) return;

        echo "Sending backup to $to... ";

        $this->load->library('email');
        $this->email->initialize([
            'protocol' => getenv('EMAIL_PROTOCOL') ?: 'smtp',
            'smtp_host' => getenv('SMTP_HOST'),
            'smtp_port' => getenv('SMTP_PORT') ?: 587,
            'smtp_user' => getenv('SMTP_USER'),
            'smtp_pass' => getenv('SMTP_PASS'),
            'smtp_crypto' => getenv('SMTP_CRYPTO') ?: 'tls',
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n",
            'crlf' => "\r\n"
        ]);

        $this->email->from(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME') ?: 'System Backup');
        $this->email->to($to);
        $this->email->subject('Automated Database Backup - ' . date('Y-m-d H:i:s'));
        $this->email->message('Please find the attached database backup file.');
        $this->email->attach($file_path);

        if ($this->email->send()) {
            echo "Success." . PHP_EOL;
        } else {
            echo "Failed." . PHP_EOL;
        }
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Queue.php
 */

/**
 * Queue Controller - CLI Background Job Processing Worker
 */
class Queue extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            exit("This controller can only be accessed via CLI.\n");
        }
        $this->load->model('queue_model');
    }

    /**
     * Run the Queue Worker daemon process.
     * Usage: php index.php queue/work [queue_name]
     *
     * @param  string $queue_name
     * @noreturn  Intentional infinite loop — terminated by Ctrl+C or Supervisor.
     */
    public function work($queue_name = 'default')
    {
        echo "=============================================\n";
        echo " Starting Queue Worker for queue: '{$queue_name}'\n";
        echo " Press Ctrl+C to terminate the daemon worker.\n";
        echo "=============================================\n\n";

        // Daemon Loop
        while (true) {
            // Fetch the oldest available job using queue_model
            $job = $this->queue_model->get_next_job($queue_name);

            if (!$job) {
                // No jobs found, sleep for 3 seconds to avoid pinning the CPU
                sleep(3);
                continue;
            }

            // Lock/Reserve job using queue_model before running
            $this->queue_model->reserve_job($job->id, $job->attempts);

            $payload = json_decode($job->payload, true);
            $uri = $payload['uri'] ?? '';
            $data = $payload['data'] ?? [];

            echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Processing: '{$uri}'\n";

            // Run the job with safe process isolation / inline fallback
            $output = [];
            $return_var = 1;
            $this->execute_job($uri, $data, $output, $return_var);

            if ($return_var === 0) {
                $this->queue_model->delete_job($job->id);
                echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Success.\n\n";
            } else {
                // Resolve MAX_ATTEMPTS outside string interpolation —
                // PHP 8+ doesn't accept {$obj->prop::CONST} inside double quotes.
                $max_attempts = Queue_model::MAX_ATTEMPTS;
                $detail = implode("\n", $this->sanitize_output($output));
                echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Failed (exit {$return_var}). Attempt {$job->attempts}/{$max_attempts}.\n";
                if ($detail) {
                    echo "---------------------------------------------\n{$detail}\n---------------------------------------------\n";
                }

                // Permanently bury the job after MAX_ATTEMPTS exhausted
                if ($job->attempts >= $max_attempts) {
                    $this->queue_model->bury_job($job, "exit={$return_var}\n" . $detail);
                    echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Moved to dead-letter (failed_jobs).\n";
                }
                echo "\n";
            }

            usleep(100000); // 100 ms between jobs
        }
    }

    /**
     * Execute a job either via CLI process isolation (if exec is enabled) or fallback safely to inline execution.
     */
    protected function execute_job(string $uri, array $data, array &$output, int &$return_var): void
    {
        $output = [];
        $return_var = 1;

        // Try CLI Process Isolation (exec) first for true process isolation to avoid memory leaks
        $exec_enabled = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));

        if ($exec_enabled) {
            $encoded_data = json_encode($data);
            $index_path = FCPATH . 'index.php';
            $command = "php " . escapeshellarg($index_path) . " " . escapeshellarg($uri) . " " . escapeshellarg($encoded_data);
            exec($command, $output, $return_var);
            return;
        }

        // Inline Execution Fallback (if exec is disabled).
        // Strict allowlist of (controller, method) pairs that may be invoked.
        // Anything else (e.g. a row inserted by a compromised DB) is rejected
        // before require_once / new — preventing arbitrary controller load.
        $allowed_jobs = [
            'queue' => ['send_email', 'send_sms', 'persist_activity_log', 'trigger_pusher'],
            'import' => ['process_queue'],
        ];

        $segments = explode('/', trim($uri, '/'));
        $controller_name = strtolower($segments[0] ?? 'queue');
        $method_name     = strtolower($segments[1] ?? 'index');

        // Both segments must be identifier-shaped before we touch the filesystem.
        $is_ident = static function ($s) {
            return is_string($s) && preg_match('/^[a-z][a-z0-9_]{0,40}$/', $s) === 1;
        };
        if (!$is_ident($controller_name) || !$is_ident($method_name)) {
            $output[] = "Inline execution rejected: invalid job descriptor.";
            $return_var = 1;
            return;
        }
        if (!isset($allowed_jobs[$controller_name])
            || !in_array($method_name, $allowed_jobs[$controller_name], true)) {
            $output[] = "Inline execution rejected: job '{$controller_name}/{$method_name}' is not in the allowlist.";
            $return_var = 1;
            log_message('error', "Queue::execute_job blocked non-allowlisted job: {$controller_name}/{$method_name}");
            return;
        }

        try {
            ob_start();
            if ($controller_name === 'queue') {
                $this->$method_name(json_encode($data));
                $return_var = 0;
            } else {
                $controller_file = APPPATH . 'controllers/' . ucfirst($controller_name) . '.php';
                if (!file_exists($controller_file)) {
                    throw new Exception("Controller file not found.");
                }
                require_once $controller_file;
                $class_name = ucfirst($controller_name);
                if (!class_exists($class_name)) {
                    throw new Exception("Class {$class_name} not found.");
                }
                $instance = new $class_name();
                if (!method_exists($instance, $method_name)) {
                    throw new Exception("Method {$method_name} not found in {$class_name} controller.");
                }
                $instance->$method_name(json_encode($data));
                $return_var = 0;
            }
            $output[] = ob_get_clean();
        } catch (Exception $e) {
            $output[] = ob_get_clean();
            $output[] = "Inline execution error: " . $e->getMessage();
            $return_var = 1;
        }
    }

    /**
     * CLI Job Handler: Send Email
     * Receives JSON payload as the first parameter
     * 
     * @param string $payload_json
     */
    public function send_email($payload_json = '')
    {
        $payload = json_decode($payload_json, true);
        if (!$payload) {
            echo "Error: Invalid email payload JSON.\n";
            exit(1);
        }

        $email = $payload['email'] ?? '';
        $subject = $payload['subject'] ?? '';
        $message = $payload['message'] ?? '';

        if (empty($email) || empty($subject) || empty($message)) {
            echo "Error: Missing required email parameters (email, subject, message).\n";
            exit(1);
        }

        echo "Attempting to send email to '{$email}' via SMTP...\n";

        $this->load->model('email_model');
        $success = $this->email_model->sendMail($email, $subject, $message);

        if ($success) {
            echo "Email successfully delivered.\n";
            exit(0);
        } else {
            echo "Failed to deliver email. Check system email logs.\n";
            exit(1);
        }
    }

    /**
     * CLI Job Handler: Send SMS
     * Receives JSON payload as the first parameter
     * 
     * @param string $payload_json
     */
    public function send_sms($payload_json = '')
    {
        $payload = json_decode($payload_json, true);
        if (!$payload) {
            echo "Error: Invalid SMS payload JSON.\n";
            exit(1);
        }

        $mobile = $payload['mobile_no'] ?? '';
        $message = $payload['message'] ?? '';

        if (empty($mobile) || empty($message)) {
            echo "Error: Missing required SMS parameters (mobile_no, message).\n";
            exit(1);
        }

        echo "Attempting to send SMS to '{$mobile}'...\n";

        $this->load->model('sms_model');
        // Check if SMS model has sending capability
        if (method_exists($this->sms_model, 'send')) {
            $success = $this->sms_model->send($mobile, $message);
        } elseif (method_exists($this->sms_model, 'send_sms')) {
            $success = $this->sms_model->send_sms($mobile, $message);
        } else {
            echo "Error: sms_model has no send/send_sms method implemented.\n";
            exit(1);
        }

        if ($success) {
            echo "SMS successfully delivered.\n";
            exit(0);
        } else {
            echo "Failed to deliver SMS. Check SMS logs.\n";
            exit(1);
        }
    }

    /**
     * CLI Job Handler: Persist an activity log entry to the database.
     * Decoupled from the HTTP request cycle so the DB insert never blocks the user.
     *
     * @param string $payload_json
     */
    public function persist_activity_log($payload_json = '')
    {
        $data = json_decode($payload_json, true);
        if (!$data || empty($data['action'])) {
            echo "Error: Invalid activity log payload.\n";
            exit(1);
        }

        $this->load->model('activity_logs_model');

        $insert = [
            'user_id'     => (int) ($data['user_id']     ?? 0),
            'module_name' => $data['module_name'] ?? '',
            'table_name'  => $data['table_name']  ?? '',
            'row_id'      => (int) ($data['row_id']      ?? 0),
            'action'      => $data['action']      ?? '',
            'description' => $data['description'] ?? '',
            'ip_address'  => $data['ip_address']  ?? '',
            'user_agent'  => $data['user_agent']  ?? '',
            'old_data'    => isset($data['old_data'])  ? json_encode($data['old_data'])  : null,
            'new_data'    => isset($data['new_data'])  ? json_encode($data['new_data'])  : null,
            'created_at'  => $data['created_at']  ?? date('Y-m-d H:i:s'),
        ];

        $ok = $this->db->insert('activity_logs', $insert);
        if ($ok) {
            echo "Activity log persisted (id=" . $this->db->insert_id() . ").\n";
            exit(0);
        } else {
            echo "Failed to persist activity log to DB.\n";
            exit(1);
        }
    }

    /**
     * CLI Job Handler: Trigger Pusher Event
     * Receives JSON payload as the first parameter
     *
     * @param string $payload_json
     */
    public function trigger_pusher($payload_json = '')
    {
        $payload = json_decode($payload_json, true);
        if (!$payload) {
            echo "Error: Invalid Pusher payload JSON.\n";
            exit(1);
        }

        $channel = $payload['channel'] ?? '';
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        if (empty($channel) || empty($event)) {
            echo "Error: Missing required Pusher parameters (channel, event).\n";
            exit(1);
        }

        echo "Attempting to trigger Pusher event '{$event}' on channel '{$channel}'...\n";

        $this->load->library('pusher_lib');
        
        $success = $this->pusher_lib->trigger($channel, $event, $data);

        if ($success) {
            echo "Pusher event triggered successfully.\n";
            exit(0);
        } else {
            echo "Failed to trigger Pusher event.\n";
            exit(1);
        }
    }

    /**
     * Process all currently pending jobs and exit.
     * Useful for one-off runs or environments where daemon workers aren't running.
     * 
     * @param string $queue_name
     */
    public function process($queue_name = 'default')
    {
        echo "=============================================\n";
        echo " Processing Pending Jobs for queue: '{$queue_name}'\n";
        echo "=============================================\n\n";

        $processed = 0;
        while (true) {
            // Fetch the oldest available job using queue_model
            $job = $this->queue_model->get_next_job($queue_name);

            if (!$job) {
                break;
            }

            // Lock/Reserve job using queue_model
            $this->queue_model->reserve_job($job->id, $job->attempts);

            $payload = json_decode($job->payload, true);
            $uri = $payload['uri'] ?? '';
            $data = $payload['data'] ?? [];

            echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Processing: '{$uri}'\n";

            // Run the job with safe process isolation / inline fallback
            $output = [];
            $return_var = 1;
            $this->execute_job($uri, $data, $output, $return_var);

            if ($return_var === 0) {
                $this->queue_model->delete_job($job->id);
                echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Success.\n\n";
                $processed++;
            } else {
                $max_attempts = Queue_model::MAX_ATTEMPTS;
                $detail = implode("\n", $this->sanitize_output($output));
                echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Failed (exit {$return_var}). Attempt {$job->attempts}/{$max_attempts}.\n";
                if ($detail) {
                    echo "---------------------------------------------\n{$detail}\n---------------------------------------------\n";
                }
                if ($job->attempts >= $max_attempts) {
                    $this->queue_model->bury_job($job, "exit={$return_var}\n" . $detail);
                    echo "[" . date('Y-m-d H:i:s') . "] [Job #{$job->id}] Moved to dead-letter (failed_jobs).\n";
                }
                echo "\n";
            }

            $output = [];
        }

        echo "Finished. Total processed jobs: {$processed}.\n";
    }

    /**
     * Sanitize output from a job to redact sensitive variables and credentials.
     */
    protected function sanitize_output(array $output_lines): array
    {
        $scrubbed = [];
        $patterns = [
            '/(password|pass|passwd|secret|key|token|auth|smtp_pass|smtp_password)\b(\s*[:=>\s]\s*["\']?)[^"\'\r\n\s,;]{3,}/i' => '$1$2[REDACTED]'
        ];

        foreach ($output_lines as $line) {
            foreach ($patterns as $pattern => $replacement) {
                $line = preg_replace($pattern, $replacement, $line);
            }
            $scrubbed[] = $line;
        }
        return $scrubbed;
    }
}

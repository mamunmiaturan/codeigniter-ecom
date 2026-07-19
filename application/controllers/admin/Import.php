<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Import extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('user_model');
        $this->load->model('import_model');
    }

    public function index()
    {
        if (!get_permission('imports', 'is_view')) {
            access_denied();
        }

        $f_status    = $this->input->get('status')    ?: '';
        $f_uploader  = $this->input->get('uploader')  ?: '';
        $f_date_from = $this->input->get('date_from') ?: '';
        $f_date_to   = $this->input->get('date_to')   ?: '';

        $imports = $this->import_model->get_imports(loggedin_role_id(), get_loggedin_user_id());

        if ($f_status) {
            $imports = array_filter($imports, function ($r) use ($f_status) {
                return strtolower($r['status']) === strtolower($f_status);
            });
        }
        if ($f_uploader) {
            $imports = array_filter($imports, function ($r) use ($f_uploader) {
                return stripos($r['uploaded_by'] ?? '', $f_uploader) !== false;
            });
        }
        if ($f_date_from) {
            $imports = array_filter($imports, function ($r) use ($f_date_from) {
                return date('Y-m-d', strtotime($r['created_at'] ?? '')) >= $f_date_from;
            });
        }
        if ($f_date_to) {
            $imports = array_filter($imports, function ($r) use ($f_date_to) {
                return date('Y-m-d', strtotime($r['created_at'] ?? '')) <= $f_date_to;
            });
        }

        $all_uploaders = array_values(array_unique(array_filter(array_column(
            $this->import_model->get_imports(loggedin_role_id(), get_loggedin_user_id()),
            'uploaded_by'
        ))));
        sort($all_uploaders);

        $this->data['imports']       = array_values($imports);
        $this->data['all_uploaders'] = $all_uploaders;
        $this->data['filters']       = compact('f_status', 'f_uploader', 'f_date_from', 'f_date_to');
        $this->data['title']         = translate('imports');
        $this->data['sub_page']      = 'import/index';
        $this->data['main_menu']     = 'imports';
        $this->load->view('layout/index', $this->data);
    }

    public function download_file($id)
    {
        if (!get_permission('imports', 'is_view')) {
            access_denied();
        }

        $import = $this->import_model->get_import_by_id($id, loggedin_role_id(), get_loggedin_user_id());

        if ($import && file_exists(FCPATH . $import['file_path'])) {
            $this->load->helper('download');
            force_download(FCPATH . $import['file_path'], NULL);
        } else {
            set_alert('error', "Import file not found.");
            redirect(base_url('import'));
        }
    }

    public function approve($id = '')
    {
        if (!get_permission('imports', 'is_add')) {
            access_denied();
        }
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }

        $id = decrypt_id($id);
        if (!$id) {
            show_404();
            return;
        }

        // Fetch the import record
        $import = $this->import_model->get_import_by_id($id, loggedin_role_id(), get_loggedin_user_id());

        if (!$import) {
            set_alert('error', "Import record not found.");
            redirect(base_url('import'));
        }

        if ($import['status'] !== 'Pending') {
            set_alert('error', "This import has already been processed or is currently processing.");
            redirect(base_url('import'));
        }

        $loggedin_user_id = get_loggedin_user_id();
        $dest_path = FCPATH . $import['file_path'];

        // Run the import synchronously (ensures 100% reliable execution in local & web environments)
        $result = $this->execute_import($import['id'], $dest_path, $loggedin_user_id);

        if ($result['status']) {
            set_alert('success', "Import processed successfully. Total inserted: {$result['inserted_count']}, Failed: {$result['failed_count']}.");
        } else {
            set_alert('error', "Import failed: " . $result['message']);
        }

        redirect(base_url('import'));
    }

    public function retry($id = '')
    {
        if (!get_permission('imports', 'is_add')) {
            access_denied();
        }
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }

        $id = decrypt_id($id);
        if (!$id) {
            show_404();
            return;
        }

        // Fetch the import record
        $import = $this->import_model->get_import_by_id($id, loggedin_role_id(), get_loggedin_user_id());

        if (!$import) {
            set_alert('error', "Import record not found.");
            redirect(base_url('import'));
        }

        if ($import['status'] !== 'Failed') {
            set_alert('error', "Only failed imports can be retried.");
            redirect(base_url('import'));
        }

        // Reset status and stats using Import_model
        $this->import_model->update_import($id, [
            'status' => 'Pending',
            'success_rows' => 0,
            'failed_rows' => 0,
            'error_message' => NULL
        ]);

        // Execute approve directly since we are already in POST
        return $this->approve(encrypt_id($id));
    }

    public function process_queue($payload_json = '')
    {
        if (!is_cli()) {
            exit("This method can only be accessed via CLI.\n");
        }

        // Decode payload
        $payload = json_decode($payload_json, true);
        if (!$payload) {
            echo "Error: Invalid import payload JSON.\n";
            exit(1);
        }

        $import_id = $payload['import_id'] ?? 0;
        $file_path = $payload['file_path'] ?? '';
        $user_id = $payload['user_id'] ?? 0;

        $result = $this->execute_import($import_id, $file_path, $user_id);
        if ($result['status']) {
            echo "Import finished. Total inserted: {$result['inserted_count']}, Total failed: {$result['failed_count']}.\n";
            exit(0);
        } else {
            echo "Import failed: {$result['message']}\n";
            exit(1);
        }
    }

    private function execute_import($import_id, $file_path, $user_id): array
    {
        $file = $this->_validate_import_file($import_id, $file_path);
        if (!$file['ok']) {
            return ['status' => false, 'message' => $file['error']];
        }

        if ($import_id) {
            $this->import_model->update_import($import_id, ['status' => 'Processing']);
        }

        $stats = $this->_process_import_rows($file['handle'], $file['header'], $user_id);

        if (!$stats['trans_ok']) {
            $trans_error = "Database transaction failed — all inserts were rolled back.";
            if ($import_id) {
                $this->import_model->update_import($import_id, [
                    'status'        => 'Failed',
                    'total_rows'    => $stats['total'],
                    'success_rows'  => 0,
                    'failed_rows'   => $stats['total'],
                    'error_message' => $trans_error,
                ]);
            }
            $this->log_activity('IMPORT_FAILED', 'imports', $import_id,
                "Bulk import #{$import_id} transaction rollback. {$stats['total']} rows attempted.");
            return ['status' => false, 'message' => $trans_error];
        }

        return $this->_finalize_import_audit($import_id, $user_id, $stats);
    }

    /**
     * Open the CSV file and return the normalised header row.
     *
     * @return array{ok:bool, error?:string, handle?:resource, header?:array}
     */
    private function _validate_import_file($import_id, string $file_path): array
    {
        if (empty($file_path) || !file_exists($file_path)) {
            $msg = "CSV file not found at: {$file_path}";
            if ($import_id) {
                $this->import_model->update_import($import_id, [
                    'status' => 'Failed', 'error_message' => $msg,
                ]);
            }
            $this->log_activity('IMPORT_FAILED', 'imports', $import_id, $msg);
            return ['ok' => false, 'error' => $msg];
        }

        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            $msg = "Unable to open CSV file.";
            if ($import_id) {
                $this->import_model->update_import($import_id, [
                    'status' => 'Failed', 'error_message' => $msg,
                ]);
            }
            return ['ok' => false, 'error' => $msg];
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$header) {
            $msg = "Empty CSV file.";
            if ($import_id) {
                $this->import_model->update_import($import_id, [
                    'status' => 'Failed', 'error_message' => $msg,
                ]);
            }
            fclose($handle);
            return ['ok' => false, 'error' => $msg];
        }

        // Normalise: strip UTF-8 BOM, lowercase, spaces → underscores
        $header = array_map(function ($col) {
            $col = preg_replace('/^[\xEF\xBB\xBF\xFE\xFF\xFF\xFE]/', '', $col);
            return strtolower(trim(str_replace(' ', '_', $col)));
        }, $header);

        return ['ok' => true, 'handle' => $handle, 'header' => $header];
    }

    /**
     * Iterate CSV rows, validate each one, and persist valid users inside a transaction.
     *
     * @return array{inserted:int, failed:int, total:int, errors:array, trans_ok:bool}
     */
    private function _process_import_rows($handle, array $header, int $user_id): array
    {
        $inserted_count = 0;
        $failed_count   = 0;
        $total_count    = 0;
        $errors         = [];

        $uploader_role   = $user_id ? $this->import_model->get_user_role($user_id) : 0;
        $existing_emails = $this->import_model->get_all_emails_map();

        $roles       = $this->db->select('id')->get('roles')->result_array();
        $valid_roles = array_column($roles, 'id');

        $valid_genders      = ['male', 'female', 'other'];
        $valid_blood_groups = ['a+', 'a-', 'b+', 'b-', 'o+', 'o-', 'ab+', 'ab-'];

        $this->db->trans_start();

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (empty($row)) {
                continue;
            }
            if (count($row) < count($header)) {
                $total_count++;
                $failed_count++;
                $errors[] = "Row {$total_count}: Column count mismatch.";
                continue;
            }

            $total_count++;
            $data        = array_combine($header, array_slice($row, 0, count($header)));
            $name        = trim($data['name'] ?? '');
            $email       = trim($data['email'] ?? '');
            $mobile      = trim($data['mobile'] ?? $data['mobile_no'] ?? '');
            $password    = trim($data['password'] ?? '');
            $gender      = trim($data['gender'] ?? 'Male');
            $blood_group = trim($data['blood_group'] ?? $data['blood'] ?? '');
            $role_id     = intval($data['role_id'] ?? $data['role'] ?? 2);

            if ($uploader_role != 1 && $role_id <= $uploader_role) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Privilege escalation blocked. Cannot assign a role higher/equal to your own.";
                continue;
            }
            if (empty($name) || empty($email) || empty($password)) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Missing required fields (Name, Email, or Password).";
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Invalid email format: {$email}";
                continue;
            }
            $pass_valid = $this->app_lib->validate_password($password);
            if ($pass_valid !== true) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Password complexity validation failed: {$pass_valid}";
                continue;
            }
            if (!in_array($role_id, $valid_roles)) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Invalid role ID: {$role_id}";
                continue;
            }
            if (!empty($gender) && !in_array(strtolower($gender), $valid_genders)) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Invalid gender: {$gender}";
                continue;
            }
            if (!empty($blood_group) && !in_array(strtolower($blood_group), $valid_blood_groups)) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Invalid blood group: {$blood_group}";
                continue;
            }
            if (isset($existing_emails[strtolower($email)])) {
                $failed_count++;
                $errors[] = "Row {$total_count}: Email already exists: {$email}";
                continue;
            }

            $new_user_id = $this->user_model->save([
                'name'       => $name,
                'email'      => $email,
                'mobile_no'  => $mobile,
                'password'   => $password,
                'gender'     => $gender,
                'blood_group'=> $blood_group,
                'user_role'  => $role_id,
                'status'     => 'Active',
                'created_by' => $user_id,
            ]);

            if ($new_user_id) {
                $inserted_count++;
                $existing_emails[strtolower($email)] = true;
            } else {
                $failed_count++;
                $errors[] = "Row {$total_count}: Failed to save database record.";
            }
        }

        $this->db->trans_complete();
        fclose($handle);

        return [
            'inserted' => $inserted_count,
            'failed'   => $failed_count,
            'total'    => $total_count,
            'errors'   => $errors,
            'trans_ok' => $this->db->trans_status() !== false,
        ];
    }

    /**
     * Persist import status, write audit log, notify user, and push real-time event.
     *
     * @return array{status:bool, total_count:int, inserted_count:int, failed_count:int}
     */
    private function _finalize_import_audit($import_id, int $user_id, array $stats): array
    {
        $inserted = $stats['inserted'];
        $failed   = $stats['failed'];
        $total    = $stats['total'];
        $errors   = $stats['errors'];

        if ($import_id) {
            $this->import_model->update_import($import_id, [
                'status'        => 'Completed',
                'total_rows'    => $total,
                'success_rows'  => $inserted,
                'failed_rows'   => $failed,
                'error_message' => !empty($errors) ? implode("\n", array_slice($errors, 0, 10)) : null,
            ]);
        }

        $event = $failed === $total ? 'IMPORT_FAILED' : ($failed > 0 ? 'IMPORT_PARTIAL' : 'IMPORT_SUCCESS');
        $this->log_activity($event, 'imports', $import_id,
            "Bulk import #{$import_id}: {$inserted}/{$total} rows inserted, {$failed} failed.",
            null,
            ['inserted' => $inserted, 'failed' => $failed, 'total' => $total]
        );

        $title   = "Bulk Import Completed";
        $message = "Successfully imported {$inserted} users out of {$total}.";
        if ($failed > 0) {
            $message .= " Failed: {$failed}.";
        }

        $this->import_model->create_notification([
            'user_id'    => $user_id,
            'title'      => $title,
            'message'    => $message,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->load->library('pusher_lib');
        $this->pusher_lib->trigger('notifications-channel-' . $user_id, 'new-notification', [
            'title'   => $title,
            'message' => $message,
            'time'    => date('H:i:s'),
        ]);

        return [
            'status'         => true,
            'total_count'    => $total,
            'inserted_count' => $inserted,
            'failed_count'   => $failed,
        ];
    }

    public function download_sample_csv()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sample_users_import.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Name', 'Email', 'Mobile', 'Password', 'Gender', 'Blood_Group', 'Role_ID'], ',', '"', '\\');
        fputcsv($output, ['John Doe', 'john@example.com', '01711223344', '12345678aA@', 'Male', 'O+', '2'], ',', '"', '\\');
        fputcsv($output, ['Jane Doe', 'jane@example.com', '01811223344', '12345678aA@', 'Female', 'A-', '2'], ',', '"', '\\');
        fclose($output);
        exit;
    }

    public function import_csv()
    {
        if (!get_permission('user', 'is_add')) {
            access_denied();
        }

        if ($_FILES && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                // 1. Enforce max file size: 2MB (2 * 1024 * 1024 bytes)
                if ($file['size'] > 2097152) {
                    set_alert('error', 'File size exceeds the 2MB limit.');
                    redirect(base_url('import'));
                }

                // 2. Validate file extension is strictly .csv
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($ext !== 'csv') {
                    set_alert('error', 'Invalid file type. Only CSV files are allowed.');
                    redirect(base_url('import'));
                }

                // Ensure directory exists
                $upload_dir = FCPATH . 'uploads/import/users/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        set_alert('error', "Failed to create upload directory.");
                        redirect(base_url('import'));
                    }
                }

                $original_filename = $file['name'];
                $filename = 'import_' . time() . '_' . uniqid() . '.csv';
                $dest_path = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                    $loggedin_user_id = get_loggedin_user_id();

                    // Insert entry to 'imports' table using Import_model
                    $import_data = [
                        'filename' => $filename,
                        'original_filename' => $original_filename,
                        'file_path' => 'uploads/import/users/' . $filename,
                        'user_id' => $loggedin_user_id,
                        'status' => 'Pending',
                        'total_rows' => 0,
                        'success_rows' => 0,
                        'failed_rows' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $import_id = $this->import_model->create_import($import_data);

                    // Load queue library
                    $this->load->library('queue');

                    // Push job to queue
                    $this->queue->push('import/process_queue', [
                        'import_id' => $import_id,
                        'file_path' => $dest_path,
                        'user_id' => $loggedin_user_id
                    ]);

                    set_alert('success', translate('csv_uploaded_successfully_awaiting_approval'));
                } else {
                    set_alert('error', "Failed to move uploaded file. Please check folder permissions.");
                }
            } else {
                set_alert('error', "File upload error code: " . $file['error']);
            }
        }

        redirect(base_url('import'));
    }
}

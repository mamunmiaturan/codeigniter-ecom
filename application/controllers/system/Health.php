<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Health Check Endpoint
 *
 * Liveness / readiness probe for load balancers, Docker, k8s, uptime monitors.
 * Returns JSON:
 *   { "status": "ok|degraded|down", "checks": { ... } }
 *
 *  - / health             — shallow liveness (HTTP 200 if the app runs)
 *  - /health/ready        — readiness: DB + cache dir + log dir writable
 *  - /health/details      — full diagnostic payload (admin token required)
 *
 * No authentication on shallow checks so probes do not consume a session.
 * The detailed endpoint requires the X-Health-Token header (env HEALTH_TOKEN).
 */
class Health extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    public function index()
    {
        $this->_emit(200, ['status' => 'ok', 'time' => date(DATE_ATOM)]);
    }

    public function ready()
    {
        $checks = [
            'database'  => $this->_check_db(),
            'cache_dir' => $this->_check_writable(APPPATH . 'cache/'),
            'logs_dir'  => $this->_check_writable(APPPATH . 'logs/'),
            'uploads'   => $this->_check_writable(FCPATH . 'uploads/'),
        ];
        $ok = !in_array(false, array_column($checks, 'ok'), true);
        $this->_emit($ok ? 200 : 503, [
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
        ]);
    }

    public function details()
    {
        $expected = getenv('HEALTH_TOKEN') ?: '';
        $supplied = $this->input->get_request_header('X-Health-Token', true) ?: '';
        if ($expected === '' || !hash_equals($expected, $supplied)) {
            $this->_emit(401, ['status' => 'unauthorized']);
            return;
        }

        $checks = [
            'database'         => $this->_check_db(),
            'cache_dir'        => $this->_check_writable(APPPATH . 'cache/'),
            'logs_dir'         => $this->_check_writable(APPPATH . 'logs/'),
            'uploads'          => $this->_check_writable(FCPATH . 'uploads/'),
            'session_storage'  => $this->_check_session_storage(),
            'queue_pending'    => $this->_check_queue_pending(),
            'env_security_key' => ['ok' => (bool) getenv('SECURITY_KEY'), 'label' => 'SECURITY_KEY env present'],
        ];
        $ok = !in_array(false, array_column($checks, 'ok'), true);
        $this->_emit($ok ? 200 : 503, [
            'status'   => $ok ? 'ok' : 'degraded',
            'time'     => date(DATE_ATOM),
            'php'      => PHP_VERSION,
            'env'      => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown',
            'checks'   => $checks,
        ]);
    }

    private function _check_db()
    {
        try {
            $this->load->database();
            $row = $this->db->query('SELECT 1 AS up')->row();
            return ['ok' => isset($row->up) && $row->up == 1];
        } catch (Throwable $e) {
            log_message('error', 'Health::_check_db failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'db_unreachable'];
        }
    }

    private function _check_writable($path)
    {
        return ['ok' => is_dir($path) && is_writable($path), 'path' => $path];
    }

    private function _check_session_storage()
    {
        $driver = config_item('sess_driver');
        if ($driver !== 'database') {
            return ['ok' => true, 'driver' => $driver];
        }
        try {
            $this->load->database();
            $tbl = config_item('sess_save_path') ?: 'ci_sessions';
            return ['ok' => $this->db->table_exists($tbl), 'driver' => 'database', 'table' => $tbl];
        } catch (Throwable $e) {
            return ['ok' => false, 'driver' => 'database', 'error' => $e->getMessage()];
        }
    }

    private function _check_queue_pending()
    {
        try {
            $this->load->database();
            if (!$this->db->table_exists('jobs')) {
                return ['ok' => true, 'pending' => 0];
            }
            $pending = (int) $this->db->where('status', 'pending')->count_all_results('jobs');
            // Degraded if more than 10k pending jobs — operator should investigate.
            return ['ok' => $pending < 10000, 'pending' => $pending];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function _emit($status_code, array $payload)
    {
        // Emitted inline rather than via MY_Controller::jsonResponse(): this
        // controller extends CI_Controller on purpose to keep probes free of
        // session/auth overhead, and jsonResponse() would inject a CSRF token
        // into the probe payload.
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}

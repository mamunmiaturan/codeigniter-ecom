<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Systemlog extends Admin_Controller
{
    private static $LOG_GROUPS = [
        'activity' => ['label' => 'Activity', 'icon' => 'fa-user-clock',  'color' => '#6366f1'],
        'email'    => ['label' => 'Email',    'icon' => 'fa-envelope',    'color' => '#8b5cf6'],
        'sms'      => ['label' => 'SMS',      'icon' => 'fa-sms',         'color' => '#10b981'],
        'log'      => ['label' => 'System',   'icon' => 'fa-server',      'color' => '#ef4444'],
        'server'   => ['label' => 'Server',   'icon' => 'fa-terminal',    'color' => '#f59e0b'],
    ];

    public function __construct()
    {
        parent::__construct();
        if (!get_permission('system_log', 'is_view')) {
            access_denied();
        }
        if (!audit_logs_can_view_system()) {
            access_denied();
        }
    }

    public function index()
    {
        $groups = [];
        foreach (self::$LOG_GROUPS as $key => $meta) {
            if ($key === 'server') {
                $files = array_merge(glob(APPPATH . 'logs/*.err') ?: [], glob(APPPATH . 'logs/*.out') ?: []);
                rsort($files);
                $items = [];
                foreach ($files as $fp) {
                    $name    = basename($fp);
                    $items[] = [
                        'name'  => $name,
                        'key'   => 'server/' . $name,
                        'size'  => $this->_human_size(filesize($fp)),
                        'bytes' => filesize($fp),
                        'mtime' => filemtime($fp),
                    ];
                }
                if (!empty($items)) {
                    $groups[$key] = array_merge($meta, ['files' => $items]);
                }
                continue;
            }

            $dir   = APPPATH . 'logs/' . $key . '/';
            $files = glob($dir . '*.json') ?: [];
            rsort($files);
            $items = [];
            foreach ($files as $fp) {
                $name = basename($fp);
                if ($name === 'index.html') continue;
                $items[] = [
                    'name'  => $name,
                    'key'   => $key . '/' . $name,
                    'size'  => $this->_human_size(filesize($fp)),
                    'bytes' => filesize($fp),
                    'mtime' => filemtime($fp),
                ];
            }
            if (!empty($items)) {
                $groups[$key] = array_merge($meta, ['files' => $items]);
            }
        }

        $this->data['groups']   = $groups;
        $this->data['title']    = 'Log Viewer';
        $this->data['sub_page'] = 'audit/system/log/index';
        $this->load->view('layout/fullpage', $this->data);
    }

    // AJAX or direct GET: load file entries as JSON
    public function load_file()
    {
        // Accept both AJAX and direct calls (output is always JSON)
        $this->output->set_content_type('application/json');

        $file_key = $this->input->get('file') ?: '';

        if (!$this->_is_valid_file_key($file_key)) {
            echo json_encode(['error' => 'Invalid file']);
            return;
        }

        $path = $this->_resolve_path($file_key);
        if (!$path || !file_exists($path)) {
            echo json_encode(['error' => 'File not found: ' . $file_key]);
            return;
        }

        $group        = explode('/', $file_key)[0];
        $search       = strtolower(trim($this->input->get('search') ?: ''));
        $level_filter = strtoupper(trim($this->input->get('level') ?: ''));
        $page         = max(1, (int) ($this->input->get('page') ?: 1));
        $per_page     = max(10, min(500, (int) ($this->input->get('per_page') ?: 100)));

        $sort = $this->input->get('sort') ?: 'newest';
        $all  = $this->_parse_file($path, $group);
        // newest = reverse chronological (last line first), oldest = natural file order
        if ($sort !== 'oldest') {
            $all = array_reverse($all);
        }

        // Count by level before filtering
        $level_counts = ['ERROR' => 0, 'WARNING' => 0, 'INFO' => 0, 'DEBUG' => 0, 'SUCCESS' => 0];
        foreach ($all as $e) {
            $lv = $e['level'] ?? 'INFO';
            if (isset($level_counts[$lv])) {
                $level_counts[$lv]++;
            }
        }

        if ($level_filter) {
            $all = array_values(array_filter($all, function ($e) use ($level_filter) {
                return ($e['level'] ?? '') === $level_filter;
            }));
        }
        if ($search) {
            $all = array_values(array_filter($all, function ($e) use ($search) {
                return strpos(strtolower($e['message'] ?? ''), $search) !== false
                    || strpos(strtolower($e['meta'] ?? ''), $search) !== false;
            }));
        }

        $total       = count($all);
        $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 1;
        $entries     = array_slice($all, ($page - 1) * $per_page, $per_page);

        echo json_encode([
            'entries'      => $entries,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $per_page,
            'total_pages'  => $total_pages,
            'level_counts' => $level_counts,
        ]);
    }

    // POST: delete a single log file
    public function delete_file()
    {
        $this->output->set_content_type('application/json');

        if (!get_permission('system_log', 'is_delete')) {
            echo json_encode(['error' => 'Permission denied']);
            return;
        }

        $file_key = $this->input->post('file') ?: '';
        if (!$this->_is_valid_file_key($file_key)) {
            echo json_encode(['error' => 'Invalid file']);
            return;
        }

        $path = $this->_resolve_path($file_key);
        if ($path && file_exists($path)) {
            unlink($path);
        }

        echo json_encode(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function _is_valid_file_key(string $key): bool
    {
        return preg_match('#^(activity|email|sms|log)/[\w\-\.]+\.json$#', $key)
            || preg_match('#^server/[\w\-\.]+\.(err|out)$#', $key);
    }

    private function _resolve_path(string $file_key): ?string
    {
        $group = explode('/', $file_key)[0];
        if ($group === 'server') {
            return APPPATH . 'logs/' . basename($file_key);
        }
        return APPPATH . 'logs/' . $file_key;
    }

    private function _parse_file(string $path, string $group): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) return [];

        $lines = explode("\n", trim($raw));
        $all   = [];

        if ($group === 'server') {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $all[] = $this->_parse_plain_line($line);
            }
        } else {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $data = json_decode($line, true);
                if (!is_array($data)) continue;
                $all[] = $this->_normalise_entry($data, $group);
            }
        }

        return $all;
    }

    private function _parse_plain_line(string $line): array
    {
        $time = '';
        $msg  = $line;
        if (preg_match('/^\[([^\]]+)\]\s*(.*)$/s', $line, $m)) {
            $time = $m[1];
            $msg  = trim($m[2]);
        }

        $lower = strtolower($msg);
        if (strpos($lower, 'error') !== false || strpos($lower, 'fatal') !== false) {
            $level = 'ERROR';
        } elseif (strpos($lower, 'warn') !== false) {
            $level = 'WARNING';
        } elseif (strpos($lower, 'accepted') !== false || strpos($lower, 'closing') !== false || strpos($lower, 'started') !== false) {
            $level = 'DEBUG';
        } else {
            $level = 'INFO';
        }

        return ['level' => $level, 'time' => $time, 'message' => $msg, 'meta' => ''];
    }

    private function _normalise_entry(array $data, string $group): array
    {
        switch ($group) {
            case 'activity':
                $level = 'INFO';
                $msg   = ($data['action'] ?? '') . ' — ' . ($data['description'] ?? '');
                $meta  = 'User: ' . ($data['user_name'] ?? '-')
                    . '   IP: ' . ($data['ip_address'] ?? '-')
                    . '   Module: ' . ($data['module_name'] ?? '-');
                $time  = $data['created_at'] ?? '';
                break;
            case 'email':
            case 'sms':
                $st    = strtolower($data['status'] ?? 'info');
                $level = $st === 'success' ? 'SUCCESS' : ($st === 'failed' ? 'ERROR' : 'INFO');
                $msg   = ($data['subject'] ?? $data['type'] ?? '-') . ' → ' . ($data['recipient'] ?? '-');
                $meta  = isset($data['error']) && $data['error'] ? 'Error: ' . $data['error'] : '';
                $time  = $data['time'] ?? $data['created_at'] ?? '';
                break;
            default: // log / system
                $level = strtoupper($data['level'] ?? 'INFO');
                $msg   = $data['message'] ?? '';
                $meta  = '';
                $time  = $data['date'] ?? $data['time'] ?? $data['created_at'] ?? '';
                break;
        }

        return ['level' => $level, 'time' => $time, 'message' => $msg, 'meta' => $meta];
    }

    private function _human_size(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}

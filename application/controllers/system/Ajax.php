<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Ajax.php
 */
class Ajax extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ajax_model');
    }

    public function toggle_developer_mode()
    {
        if (in_array(loggedin_role_id(), [ROLE_SUPERMAN_ID, ROLE_ADMIN_ID])) {
            $state = $this->input->post('state');
            $this->session->set_userdata('developer_mode', ($state == 'true' ? 1 : 0));
            return $this->jsonResponse([
                'status' => 'success',
                'developer_mode' => $this->session->userdata('developer_mode')
            ]);
        } else {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
    }

    public function heartbeat()
    {
        $loggedin_id = get_loggedin_id();
        if (empty($loggedin_id)) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Not logged in'], 401);
        }

        $this->load->library('redis_lib');
        $now_time = time();
        $redis_active_ids = [];
        $redis_enabled = $this->redis_lib->is_enabled();

        if ($redis_enabled) {
            // 1. Update last_active in Redis sorted set
            $this->redis_lib->call('zAdd', 'online_users_set', $now_time, $loggedin_id);
            // Cleanup users older than 45 seconds
            $this->redis_lib->call('zRemRangeByScore', 'online_users_set', '-inf', (string)($now_time - 45));

            // Throttle DB write: only write to MySQL if not written in last 5 minutes
            $db_write_cache_key = 'db_write_last_active:' . $loggedin_id;
            if (!$this->redis_lib->get($db_write_cache_key)) {
                $this->ajax_model->update_last_active($loggedin_id);
                $this->redis_lib->set($db_write_cache_key, 1, 300); // cache for 5 minutes
            }

            // Get active user IDs from Redis
            $redis_active_ids = $this->redis_lib->call('zRangeByScore', 'online_users_set', (string)($now_time - 45), '+inf');
            if (($key = array_search($loggedin_id, $redis_active_ids)) !== false) {
                unset($redis_active_ids[$key]);
            }
        } else {
            // Database Fallback: Throttle update_last_active using Session cache to prevent DB write amplification
            $last_write = $this->session->userdata('last_active_db_write:' . $loggedin_id);
            if (empty($last_write) || ($now_time - intval($last_write)) >= 300) {
                $this->ajax_model->update_last_active($loggedin_id);
                $this->session->set_userdata('last_active_db_write:' . $loggedin_id, $now_time);
            }
        }
        // 2. Query all active users using ajax_model
        $loggedin_role = loggedin_role_id();
        $loggedin_user_id = get_loggedin_user_id();
        $threshold = date('Y-m-d H:i:s', $now_time - 45);

        $active_users = $this->ajax_model->get_active_users(
            $redis_enabled, 
            $redis_active_ids, 
            $loggedin_id, 
            $threshold, 
            $loggedin_role, 
            $loggedin_user_id
        );

        // 3. Trigger Pusher if there is a change in the online users list
        $prev_active_ids = $this->session->userdata('last_active_user_ids') ?? [];
        $current_active_ids = array_column($active_users, 'cred_id');
        sort($prev_active_ids);
        sort($current_active_ids);

        if ($prev_active_ids !== $current_active_ids) {
            $this->session->set_userdata('last_active_user_ids', $current_active_ids);
            
            // Trigger Pusher broadcast
            $this->load->library('pusher_lib');
            $this->pusher_lib->trigger('activity-channel', 'online-users-updated', [
                'count' => count($active_users)
            ]);
        }

        // Format photos
        foreach ($active_users as &$user) {
            $imgFolder = ($user['role'] == 7) ? 'client/' : 'user/';
            $user['photo'] = $this->app_lib->get_image_url($imgFolder . $user['photo']);
            $user['last_message'] = '';
            $user['last_message_sender'] = '';
            $user['last_message_time'] = '';
        }

        return $this->jsonResponse([
            'status' => 'success',
            'active_users' => $active_users,
            'count' => count($active_users),
            'unread_count' => 0
        ]);
    }
}

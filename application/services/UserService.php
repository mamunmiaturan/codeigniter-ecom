<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Base_Service.php';

/**
 * UserService
 *
 * Domain layer for user CRUD + role-hierarchy enforcement. The User controller
 * is now a thin HTTP adapter: it parses the request, calls a single service
 * method, and renders the response.
 *
 * Every method returns a structured array { ok: bool, code: string, ... }
 * instead of issuing redirects/flash messages — that's the controller's job.
 *
 * Authorization rules enforced here:
 *   - Cannot create/modify/delete a user whose role is >= your own
 *     (unless you are super-admin, role_id == 1).
 *   - Cannot delete yourself.
 *   - Cannot assign a role >= your own.
 *
 * These rules are also enforced in the controller for defense-in-depth, but
 * the service is the canonical guard.
 */
class UserService extends Base_Service
{
    public function __construct()
    {
        parent::__construct();
        $this->ci->load->model('user_model');
    }

    /**
     * Result shape: ['ok' => bool, 'code' => string, 'user_id' => ?int, 'message' => ?string]
     */
    public function create(array $input, int $actor_role): array
    {
        $assigned_role = (int) ($input['user_role'] ?? 0);
        if ($assigned_role <= 0) {
            return ['ok' => false, 'code' => 'invalid_role', 'message' => 'A role is required.'];
        }
        if ($actor_role !== 1 && $assigned_role <= $actor_role) {
            return ['ok' => false, 'code' => 'role_hierarchy_blocked',
                    'message' => 'You cannot assign a role equal to or higher than your own.'];
        }
        $user_id = $this->ci->user_model->save($input);
        if (!$user_id) {
            return ['ok' => false, 'code' => 'save_failed', 'message' => 'Failed to create user.'];
        }
        return ['ok' => true, 'code' => 'created', 'user_id' => (int) $user_id];
    }

    /**
     * Update an existing user. $input must contain 'user_id' as the encrypted
     * token from the form; service decrypts and enforces hierarchy.
     */
    public function update(array $input, int $actor_role): array
    {
        $raw = $input['user_id'] ?? '';
        $target_id = decrypt_id($raw);
        if (!$target_id || !ctype_digit((string) $target_id)) {
            return ['ok' => false, 'code' => 'invalid_id', 'message' => 'Invalid user id.'];
        }
        $target_id = (int) $target_id;
        $input['user_id'] = $target_id;

        $target = $this->ci->user_model->get_single_user($target_id);
        if (!$target) {
            return ['ok' => false, 'code' => 'not_found', 'message' => 'User not found.'];
        }
        if ($actor_role !== 1) {
            if ((int) $target['role_id'] <= $actor_role) {
                return ['ok' => false, 'code' => 'hierarchy_blocked',
                        'message' => 'You cannot modify a user at or above your role.'];
            }
            $assigned = (int) ($input['user_role'] ?? $target['role_id']);
            if ($assigned <= $actor_role) {
                return ['ok' => false, 'code' => 'role_elevation_blocked',
                        'message' => 'You cannot assign a role equal to or higher than your own.'];
            }
        }

        $ok = $this->ci->user_model->save($input);
        return ['ok' => (bool) $ok, 'code' => $ok ? 'updated' : 'save_failed',
                'user_id' => $target_id];
    }

    public function delete(string $encrypted_id, int $actor_role, int $actor_user_id): array
    {
        $target_id = decrypt_id($encrypted_id);
        if (!$target_id || !ctype_digit((string) $target_id)) {
            return ['ok' => false, 'code' => 'invalid_id'];
        }
        $target_id = (int) $target_id;
        if ($target_id === $actor_user_id) {
            return ['ok' => false, 'code' => 'self_delete_blocked',
                    'message' => 'You cannot delete yourself.'];
        }
        $target = $this->ci->user_model->get_single_user($target_id);
        if (!$target) {
            return ['ok' => false, 'code' => 'not_found'];
        }
        if ($actor_role !== 1 && (int) $target['role_id'] <= $actor_role) {
            return ['ok' => false, 'code' => 'hierarchy_blocked'];
        }
        $ok = $this->ci->user_model->delete_user($target_id, $actor_user_id);
        return ['ok' => (bool) $ok, 'code' => $ok ? 'deleted' : 'delete_failed',
                'user_id' => $target_id];
    }

    /**
     * Bulk reactivation. Returns counts; silently skips ids that are missing,
     * hierarchy-blocked, or self.
     */
    public function bulk_activate(array $encrypted_ids, int $actor_role, int $actor_user_id): array
    {
        $decrypted_ids = array_values(array_filter(array_map(function ($v) {
            $id = decrypt_id($v);
            return ctype_digit((string) $id) && (int) $id > 0 ? (int) $id : null;
        }, $encrypted_ids)));

        $activated = 0;
        $skipped = 0;
        foreach ($decrypted_ids as $id) {
            if ($id === $actor_user_id) { $skipped++; continue; }
            $target = $this->ci->user_model->get_single_user($id);
            if (!$target) { $skipped++; continue; }
            if ($actor_role !== 1 && (int) $target['role_id'] <= $actor_role) { $skipped++; continue; }
            $this->ci->user_model->update_status($id, 'Active');
            $activated++;
        }
        return ['ok' => true, 'code' => 'bulk_completed',
                'activated' => $activated, 'skipped' => $skipped];
    }

    public function change_password(string $encrypted_id, string $plain_password, int $actor_role): array
    {
        $target_id = decrypt_id($encrypted_id);
        if (!$target_id || !ctype_digit((string) $target_id)) {
            return ['ok' => false, 'code' => 'invalid_id'];
        }
        $target_id = (int) $target_id;
        $target = $this->ci->user_model->get_single_user($target_id);
        if (!$target) {
            return ['ok' => false, 'code' => 'not_found'];
        }
        if ($actor_role !== 1 && (int) $target['role_id'] <= $actor_role) {
            return ['ok' => false, 'code' => 'hierarchy_blocked'];
        }
        $valid = $this->ci->app_lib->validate_password($plain_password);
        if ($valid !== true) {
            return ['ok' => false, 'code' => 'invalid_password',
                    'message' => is_string($valid) ? $valid : 'Password does not meet complexity requirements.'];
        }
        $this->ci->user_model->change_password($target_id, $this->ci->app_lib->pass_hashed($plain_password));
        // Append to password history when the table is present.
        if (isset($this->ci->password_history_model)) {
            $this->ci->password_history_model->record(
                (int) ($this->ci->app_lib->get_credential_id($target_id) ?: 0),
                $this->ci->app_lib->pass_hashed($plain_password)
            );
        }
        return ['ok' => true, 'code' => 'password_changed', 'user_id' => $target_id];
    }

    public function toggle_status(string $encrypted_id, bool $activate, int $actor_role): array
    {
        $target_id = decrypt_id($encrypted_id);
        if (!$target_id || !ctype_digit((string) $target_id)) {
            return ['ok' => false, 'code' => 'invalid_id'];
        }
        $target_id = (int) $target_id;
        $target = $this->ci->user_model->get_single_user($target_id);
        if (!$target) {
            return ['ok' => false, 'code' => 'not_found'];
        }
        if ($actor_role !== 1 && (int) $target['role_id'] <= $actor_role) {
            return ['ok' => false, 'code' => 'hierarchy_blocked'];
        }
        $new_status = $activate ? 'Active' : 'Inactive';
        $this->ci->user_model->update_status($target_id, $new_status);
        return ['ok' => true, 'code' => $activate ? 'activated' : 'deactivated',
                'user_id' => $target_id];
    }
}

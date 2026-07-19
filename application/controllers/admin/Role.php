<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Role.php
 */

class Role extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('role_model');
    }

    // new role add
    public function index()
    {
        if (isset($_POST['save'])) {
            if (!get_permission('role_permission', 'is_add')) {
                access_denied();
            }
            $rules = array(
                array(
                    'field' => 'role',
                    'label' => 'Role Name',
                    'rules' => 'required|callback_unique_name',
                ),
            );
            $this->form_validation->set_rules($rules);
            if ($this->form_validation->run() == false) {
                $this->data['validation_error'] = true;
            } else {
                // Whitelist fields to prevent mass assignment
                $data = [
                    'role'      => $this->input->post('role'),
                    'name'      => $this->input->post('name') ?: $this->input->post('role'),
                    'level'     => $this->input->post('level'),
                    'parent_id' => $this->input->post('parent_id'),
                ];
                $id = $this->role_model->save_roles($data);
                generate_sidebar_files();
                invalidate_permission_cache();
                $this->log_activity('create', 'roles', $id, 'Created new role: ' . $data['role']);
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('role'));
            }
        }
        $this->data['roles'] = $this->role_model->get_roles_for_manager((int) loggedin_role_id());
        $this->data['parent_options'] = $this->role_model->get_all_roles();
        $this->data['role_tree'] = $this->role_model->get_role_tree();
        $this->data['title'] = translate('roles');
        $this->data['sub_page'] = 'settings/role/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    // role edit
    public function edit($id)
    {
        $id = decrypt_id($id);
        if (!$id) {
            show_404();
            return;
        }
        $loggedin_role = (int) loggedin_role_id();
        if (!$this->role_model->can_manage_role($loggedin_role, (int) $id)) {
            access_denied();
        }
        if (isset($_POST['save'])) {
            if (!get_permission('role_permission', 'is_edit')) {
                access_denied();
            }
            $rules = array(
                array(
                    'field' => 'role',
                    'label' => 'Role Name',
                    'rules' => 'required|callback_unique_name',
                ),
            );
            $this->form_validation->set_rules($rules);
            if ($this->form_validation->run() == false) {
                $this->data['validation_error'] = true;
            } else {
                $posted_id = decrypt_id($this->input->post('id'));
                // Hierarchy re-check against decrypted body id (defense in depth)
                if (!$posted_id || !$this->role_model->can_manage_role($loggedin_role, (int) $posted_id)) {
                    access_denied();
                }
                $data = [
                    'id'        => $posted_id,
                    'role'      => $this->input->post('role'),
                    'name'      => $this->input->post('name') ?: $this->input->post('role'),
                    'level'     => $this->input->post('level'),
                    'parent_id' => $this->input->post('parent_id'),
                ];
                $this->role_model->save_roles($data);
                generate_sidebar_files();
                invalidate_permission_cache();
                $this->log_activity('update', 'roles', $data['id'], 'Updated role: ' . $data['role']);
                set_alert('success', translate('information_has_been_updated_successfully'));
                redirect(base_url('role'));
            }
        }
        $this->data['roles'] = $this->role_model->get_list('roles', array('id' => $id), true);
        $this->data['parent_options'] = $this->role_model->get_all_roles();
        $this->data['edit_role_id'] = (int) $id;
        $this->data['title'] = translate('roles');
        $this->data['sub_page'] = 'settings/role/edit';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    // check unique name using role_model
    public function unique_name($name)
    {
        // The edit form posts an ENCRYPTED id; decrypt it so the self-exclusion
        // matches the numeric id column (otherwise a role's own name is wrongly
        // flagged as a duplicate when editing).
        $posted = $this->input->post('id');
        $id = $posted ? decrypt_id($posted) : null;
        $exists = $this->role_model->check_unique_name($name, $id);
        if ($exists) {
            $this->form_validation->set_message("unique_name", "The %s name are already used.");
            return false;
        } else {
            return true;
        }
    }

    // role delete in DB using role_model
    public function delete($role_id)
    {
        if (!get_permission('role_permission', 'is_delete')) {
            access_denied();
        }
        $role_id = decrypt_id($role_id);
        if (!$role_id) {
            show_404();
            return;
        }
        $loggedin_role = (int) loggedin_role_id();
        if (!$this->role_model->can_manage_role($loggedin_role, (int) $role_id)) {
            access_denied();
        }
        $systemRole = array(ROLE_SUPERMAN_ID, ROLE_ADMIN_ID);
        if (!in_array($role_id, $systemRole)) {
            // Check if any user is assigned to this role using role_model
            if ($this->role_model->is_role_assigned($role_id)) {
                set_alert('error', 'This role cannot be deleted because it has assigned users.');
            } else {
                $this->role_model->delete_role($role_id);
                generate_sidebar_files();
                invalidate_permission_cache();
                $this->log_activity('delete', 'roles', $role_id, 'Deleted role ID: ' . $role_id);
                set_alert('success', translate('information_has_been_delete_successfully'));
            }
        } else {
            set_alert('error', 'System roles cannot be deleted.');
        }
        redirect(base_url('role'));
    }

    public function permission($role_id)
    {
        $role_id = decrypt_id($role_id);
        if (!$role_id) {
            show_404();
            return;
        }
        $loggedin_role = (int) loggedin_role_id();

        if (!$this->role_model->can_manage_role($loggedin_role, (int) $role_id)) {
            access_denied();
        }

        // Execute seeder to sync sidebar helper permissions
        require_once APPPATH . 'core/Seeder.php';
        require_once FCPATH . 'database/seeders/SidebarPermissionsSeeder.php';
        $seeder = new SidebarPermissionsSeeder();
        $seeder->run();

        if (isset($_POST['save'])) {
            if (!get_permission('role_permission', 'is_edit')) {
                access_denied();
            }
            $role_id = decrypt_id($this->input->post('role_id'));
            if (!$role_id || !$this->role_model->can_manage_role($loggedin_role, (int) $role_id)) {
                access_denied();
            }
            $privileges = $this->input->post('privileges');

            // Self-Elevation / Self-Demotion Blocker: Prevent any admin from modifying their own role's permissions
            if ((int) $role_id === $loggedin_role) {
                set_alert('error', 'You cannot modify permissions for your own role to prevent accidental demotion or self-elevation!');
                redirect(base_url('role/' . route_hash('permission') . '/' . encrypt_id($role_id)));
            }

            // Save user privileges using role_model in a single transaction
            $status = $this->role_model->save_privileges($role_id, $privileges, $loggedin_role);

            if ($status === FALSE) {
                set_alert('error', 'Failed to update user privileges.');
            } else {
                invalidate_permission_cache();
                $this->log_activity('update', 'user_privileges', $role_id, 'Updated permissions for role ID: ' . $role_id);
                set_alert('success', translate('information_has_been_updated_successfully'));
            }
            redirect(base_url('role/' . route_hash('permission') . '/' . encrypt_id($role_id)));
        }
        $this->data['role_id'] = encrypt_id($role_id);
        $this->data['role_id_dec'] = $role_id;
        $this->data['granter_role_id'] = $loggedin_role;
        $this->data['modules'] = $this->role_model->get_permission_modules_for_granter($loggedin_role);
        $this->data['title'] = translate('roles');
        $this->data['sub_page'] = 'settings/role/permission/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function regenerate_sidebar($role_id = '')
    {
        if (!get_permission('role_permission', 'is_edit')) {
            access_denied();
        }

        $role_id = decrypt_id($role_id);
        if (!$role_id) {
            show_404();
            return;
        }

        $loggedin_role = (int) loggedin_role_id();
        if (!$this->role_model->can_manage_role($loggedin_role, (int) $role_id)) {
            access_denied();
        }

        $ok = generate_sidebar_file_for_role((int) $role_id);
        if ($ok) {
            $this->log_activity('update', 'roles', $role_id, 'Regenerated sidebar for role ID: ' . $role_id);
            set_alert('success', translate('sidebar_regenerated_successfully'));
        } else {
            set_alert('error', translate('sidebar_regeneration_failed'));
        }

        if ($this->input->get('return') === 'list') {
            redirect(base_url('role'));
        }

        redirect(base_url('role/' . route_hash('permission') . '/' . encrypt_id($role_id)));
    }
}

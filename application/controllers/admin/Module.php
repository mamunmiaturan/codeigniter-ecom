<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Module.php
 */

class Module extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Module_model');
        if (!is_superman_loggedin()) {
            access_denied();
        }
    }

    public function index()
    {
        if ($this->input->post('save')) {
            $module_type = $this->input->post('module_type');
            if ($module_type == 'new') {
                $this->form_validation->set_rules('name', 'Module Name', 'required|trim|is_unique[permission_modules.name]');
                $this->form_validation->set_rules('permission_name', 'Permission Name', 'required|trim|is_unique[permission.name]');
            } elseif ($module_type == 'existing') {
                $this->form_validation->set_rules('existing_module_id', 'Existing Module', 'required|trim');
                $this->form_validation->set_rules('permission_name', 'Permission Name', 'required|trim|is_unique[permission.name]');
            }
            if ($this->form_validation->run() == false) {
                set_alert('error', translate('failed_to_save_module'));
                $this->data['validation_error'] = true;
                $this->data['module_names'] = $this->Module_model->get_permission_modules_name();
                $this->data['modules'] = $this->Module_model->get_permission_modules_list();
                $this->data['title'] = translate('module_and_permission');
                $this->data['sub_page'] = 'settings/module/index';
                $this->data['main_menu'] = 'settings';
                $this->load->view('layout/index', $this->data);
            } else {
                $data = $this->input->post();
                if ($module_type == 'existing' && !empty($this->input->post('permission_name'))) {
                    $data['name'] = $this->input->post('permission_name');
                }
                $result = $this->Module_model->insert($data);
                if ($result) {
                    $this->log_activity('create', 'permission_modules', $result, 'Created new module/permission: ' . $data['name']);
                    set_alert('success', translate('information_has_been_saved_successfully'));
                } else {
                    set_alert('error', translate('failed_to_save_module_information'));
                }
                redirect(base_url('module'));
            }
        } else {
            // Load the modules if the form was not submitted
            $this->data['module_names'] = $this->Module_model->get_permission_modules_name();
            $this->data['modules'] = $this->Module_model->get_permission_modules_list();
            $this->data['title'] = translate('module_and_permission');
            $this->data['sub_page'] = 'settings/module/index';
            $this->data['main_menu'] = 'settings';
            $this->load->view('layout/index', $this->data);
        }
    }

    public function edit($module_id, $permission_id)
    {
        $module_id = decrypt_id($module_id);
        $permission_id = decrypt_id($permission_id);
        $data = $this->Module_model->get_single_permission_module($module_id, $permission_id);
        if (!$data) {
            set_alert('error', translate('module_not_found'));
            redirect(base_url('module'));
        }
        // Process the form submission
        if ($this->input->post('update')) {
            // Validation
            $this->form_validation->set_rules('module_name', 'Module Name', 'required');
            $this->form_validation->set_rules('module_prefix', 'Module Prefix', 'required');
            $this->form_validation->set_rules('permission_name', 'Permission Name', 'required');
            $this->form_validation->set_rules('permission_prefix', 'Permission Prefix', 'required');

            if ($this->form_validation->run() === FALSE) {
                set_alert('error', translate('validation_failed'));
            } else {
                $data = $this->input->post();
                $result = $this->Module_model->update_module_permission($module_id, $permission_id, $data);
                if ($result) {
                    $this->log_activity('update', 'permission_modules', $module_id, 'Updated module/permission ID: ' . $module_id);
                    set_alert('success', translate('information_has_been_updated_successfully'));
                } else {
                    set_alert('error', translate('failed_to_updated_module_information'));
                }
                redirect(base_url('module/' . route_hash('edit') . '/' . encrypt_id($module_id) . '/' . encrypt_id($permission_id)));
            }
        }
        $this->data['module'] = $data;
        $this->data['title'] = translate('Permission Module');
        $this->data['sub_page'] = 'settings/module/edit';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function delete($module_id, $permission_id)
    {
        $module_id = decrypt_id($module_id);
        $permission_id = decrypt_id($permission_id);
        $module = $this->Module_model->get_single_permission_module($module_id, $permission_id);
        if (!$module) {
            set_alert('error', translate('module_not_found'));
            redirect(base_url('module'));
        }
        $is_deleted = $this->Module_model->delete_permission_and_module($module_id, $permission_id);
        if ($is_deleted) {
            $this->log_activity('delete', 'permission_modules', $module_id, 'Deleted module/permission ID: ' . $module_id);
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_deleted_failed'));
        }
        redirect(base_url('module'));
    }
}

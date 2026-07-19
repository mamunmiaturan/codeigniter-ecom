<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : User.php
 */

class User extends Admin_Controller
{

    /** @var UserService */
    private $user_service;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('email_model');
        $this->load->model('user_model');
        $this->load->model('import_model');
        require_once APPPATH . 'services/UserService.php';
        $this->user_service = new UserService();
    }

    // getting all employee list
    public function index($role = '')
    {
        if (!get_permission('user', 'is_view') || ($role == 1)) {
            access_denied();
        }

        $roles = $this->_get_visible_roles();
        $role = $this->_resolve_list_role($role);

        $this->data['act_role'] = $role;
        $this->data['roles']    = $roles;
        $this->data['title']    = translate('user');
        $this->data['sub_page'] = 'user/index';
        $this->data['main_menu'] = 'user';
        $this->data['user_list'] = [];
        $this->load->view('layout/index', $this->data);
    }

    // show user creation form
    public function create()
    {
        if (!get_permission('user', 'is_add')) {
            access_denied();
        }
        $this->data['title'] = translate('user');
        $this->data['sub_page'] = 'user/create';
        $this->data['main_menu'] = 'user';
        $this->load->view('layout/index', $this->data);
    }

    // users information are prepared and stored in the database here
    public function store()
    {
        if (!get_permission('user', 'is_add')) {
            access_denied();
        }
        if ($_POST) {
            $this->form_validation->set_rules('name', 'Name', 'trim|required');
            $this->form_validation->set_rules('mobile_no', 'Mobile No', 'trim|required');
            $this->form_validation->set_rules(
                'email',
                'Email',
                'trim|required|valid_email|callback_unique_email',
                array(
                    'unique_email' => translate('email_has_already_been_used')
                )
            );

            $this->form_validation->set_rules('user_role', 'Role', 'trim|required');

            $this->form_validation->set_rules(
                'password',
                'Password',
                'trim|required|min_length[8]|password_complexity'
            );

            $this->form_validation->set_rules(
                'retype_password',
                'Retype Password',
                'trim|required|matches[password]'
            );

            $this->form_validation->set_rules('dob', 'Date of Birth', 'trim');
            if (!empty($this->input->post('gender'))) {
                $this->form_validation->set_rules('gender', 'Gender', 'trim|in_list[Male,Female,Other]');
            } else {
                $this->form_validation->set_rules('gender', 'Gender', 'trim');
            }
            if (!empty($this->input->post('blood_group'))) {
                $this->form_validation->set_rules('blood_group', 'Blood Group', 'trim|in_list[A+,A-,B+,B-,O+,O-,AB+,AB-]');
            } else {
                $this->form_validation->set_rules('blood_group', 'Blood Group', 'trim');
            }
            $this->form_validation->set_rules('religion', 'Religion', 'trim');
            if (!empty($this->input->post('marital_status'))) {
                $this->form_validation->set_rules('marital_status', 'Marital Status', 'trim|in_list[single,married,unmarried,divorced,widowed,separated]');
            } else {
                $this->form_validation->set_rules('marital_status', 'Marital Status', 'trim');
            }
            if (!empty($this->input->post('nationality'))) {
                $this->form_validation->set_rules('nationality', 'Nationality', 'trim|in_list[Bangladeshi,Indian,Pakistani,Nepali,Bhutanese,Other]');
            } else {
                $this->form_validation->set_rules('nationality', 'Nationality', 'trim');
            }
            $this->form_validation->set_rules('nid_no', 'NID No', 'trim');
            $this->form_validation->set_rules('status', 'Status', 'trim');
            $this->form_validation->set_rules('address', 'Address', 'trim');

            if ($this->form_validation->run() !== false) {
                $result = $this->user_service->create($this->input->post(), (int) loggedin_role_id());
                if ($result['ok']) {
                    $this->log_activity('create', 'user', $result['user_id'], 'Created new user account');
                    set_alert('success', translate('information_has_been_saved_successfully'));
                    redirect(base_url('user'));
                }
                $msg = $result['message'] ?? translate('information_could_not_be_saved');
                if (in_array($result['code'], ['role_hierarchy_blocked', 'invalid_role'], true)) {
                    $msg = translate('you_do_not_have_permission_to_assign_this_role');
                }
                set_alert('error', $msg);
                redirect(base_url('user/' . route_hash('create')));
            } else {
                $this->create();
            }
        }
    }

    // profile preview and edit form
    public function edit($id = '')
    {
        if (!get_permission('user', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($id);
        if (!$id) {
            show_404();
            return;
        }
        $this->data['user'] = $this->user_model->get_single_user($id);
        $this->data['title'] = translate('user') . " " . translate('edit');
        $this->data['sub_page'] = 'user/edit';
        $this->data['main_menu'] = 'user';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('user', 'is_edit')) {
            access_denied();
        }
        if ($_POST) {
            $this->form_validation->set_rules('name', 'Name', 'trim|required');
            $this->form_validation->set_rules('mobile_no', 'Mobile No', 'trim|required');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|callback_unique_email', array('unique_email' => translate('email_has_already_been_used')));
            $this->form_validation->set_rules('user_role', 'Role', 'trim|required');
            $this->form_validation->set_rules('nid_no', 'NID No', 'trim');
            $this->form_validation->set_rules('religion', 'Religion', 'trim');
            if (!empty($this->input->post('gender'))) {
                $this->form_validation->set_rules('gender', 'Gender', 'trim|in_list[Male,Female,Other]');
            } else {
                $this->form_validation->set_rules('gender', 'Gender', 'trim');
            }
            if (!empty($this->input->post('blood_group'))) {
                $this->form_validation->set_rules('blood_group', 'Blood Group', 'trim|in_list[A+,A-,B+,B-,O+,O-,AB+,AB-]');
            } else {
                $this->form_validation->set_rules('blood_group', 'Blood Group', 'trim');
            }
            $this->form_validation->set_rules('dob', 'Date of Birth', 'trim');
            if (!empty($this->input->post('marital_status'))) {
                $this->form_validation->set_rules('marital_status', 'Marital Status', 'trim|in_list[single,married,unmarried,divorced,widowed,separated]');
            } else {
                $this->form_validation->set_rules('marital_status', 'Marital Status', 'trim');
            }
            $this->form_validation->set_rules('address', 'Address', 'trim');
            if (!empty($this->input->post('nationality'))) {
                $this->form_validation->set_rules('nationality', 'Nationality', 'trim|in_list[Bangladeshi,Indian,Pakistani,Nepali,Bhutanese,Other]');
            } else {
                $this->form_validation->set_rules('nationality', 'Nationality', 'trim');
            }
            $this->form_validation->set_rules('educational_qualification', 'Educational Qualification', 'trim');
            $this->form_validation->set_rules('age', 'Age', 'trim|numeric');
            $this->form_validation->set_rules('status', 'Status', 'trim');
 
                if ($this->form_validation->run() !== false) {
                    $result = $this->user_service->update($this->input->post(), (int) loggedin_role_id());
                    if ($result['ok']) {
                        $this->log_activity('update', 'user', $result['user_id'], 'Updated user information');
                        set_alert('success', translate('information_has_been_updated_successfully'));
                        redirect(base_url('user'));
                    }
                    if ($result['code'] === 'invalid_id' || $result['code'] === 'not_found') {
                        show_404();
                        return;
                    }
                    if ($result['code'] === 'hierarchy_blocked') {
                        set_alert('error', translate('you_do_not_have_permission_to_modify_this_user'));
                    } elseif ($result['code'] === 'role_elevation_blocked') {
                        set_alert('error', translate('you_do_not_have_permission_to_assign_this_role'));
                    } else {
                        set_alert('error', $result['message'] ?? translate('information_could_not_be_saved'));
                    }
                    redirect(base_url('user'));
                } else {
                    $this->edit($this->input->post('user_id'));
                }
        }
    }

    // profile view
    public function profile($id = '')
    {
        if (!get_permission('user', 'is_view')) {
            access_denied();
        }
        $id = decrypt_id($id);
        if (!$id) {
            show_404();
            return;
        }

        $this->data['user'] = $this->user_model->get_single_user($id);
        if (empty($this->data['user'])) {
            set_alert('error', translate('user_not_found'));
            redirect(base_url('user'));
        }

        // BOLA / IDOR Protection: Lower-privileged users cannot view higher-privileged employee profiles.
        $loggedin_role = loggedin_role_id();
        $loggedin_userid = get_loggedin_user_id();
        if ($loggedin_role != 1 && $id != $loggedin_userid) {
            if ($this->data['user']['role_id'] <= $loggedin_role) {
                access_denied();
            }
        }

        $this->data['title'] = translate('user_profile');
        $this->data['sub_page'] = 'user/view';
        $this->data['main_menu'] = 'user';
        $this->load->view('layout/index', $this->data);
    }

    // employee information delete here
    public function delete($id = '')
    {
        if (!get_permission('user', 'is_delete')) {
            access_denied();
        }
        $result = $this->user_service->delete($id, (int) loggedin_role_id(), (int) get_loggedin_user_id());
        switch ($result['code']) {
            case 'invalid_id':
            case 'not_found':
                show_404();
                return;
            case 'self_delete_blocked':
                set_alert('error', translate('you_cannot_delete_yourself'));
                break;
            case 'hierarchy_blocked':
                set_alert('error', translate('you_do_not_have_permission_to_delete_this_user'));
                break;
            case 'delete_failed':
                set_alert('error', 'Operation failed: Transaction rolled back.');
                break;
            case 'deleted':
                $this->log_activity('delete', 'user', $result['user_id'], 'Soft deleted user account');
                set_alert('success', translate('information_has_been_delete_successfully'));
                break;
        }
        redirect(base_url('user'));
    }

    // unique valid email verification is done here
    public function unique_email($email)
    {
        $user_id = null;
        if ($this->input->post('user_id')) {
            $user_id = decrypt_id($this->input->post('user_id'));
        }
        return $this->user_model->unique_email_check($email, $user_id);
    }

    // employee login password change here by admin
    public function change_password()
    {
        if (!get_permission('user', 'is_edit')) {
            access_denied();
        }

        $encrypted_id   = (string) $this->input->post('user_id');
        $authentication = $this->input->post('authentication');
        $actor_role     = (int) loggedin_role_id();

        // Branch 1: deactivation request (admin "disable account").
        if (!empty($authentication)) {
            $result = $this->user_service->toggle_status($encrypted_id, false, $actor_role);
            if (!$result['ok']) {
                $msg = ($result['code'] === 'hierarchy_blocked')
                    ? translate('you_do_not_have_permission_to_modify_this_user')
                    : 'Invalid User ID';
                return $this->jsonResponse(['status' => 'fail', 'msg' => $msg], 422);
            }
            $this->log_activity('deactivate', 'user', $result['user_id'], 'Deactivated user account');
            set_alert('success', translate('information_has_been_updated_successfully'));
            return $this->jsonResponse(['status' => 'success']);
        }

        // Branch 2: password set by admin.
        $password = (string) $this->input->post('password');
        if ($password === '') {
            return $this->jsonResponse(['status' => 'fail', 'msg' => 'The Password field is required.'], 422);
        }
        $result = $this->user_service->change_password($encrypted_id, $password, $actor_role);
        if (!$result['ok']) {
            $msg = ($result['code'] === 'hierarchy_blocked')
                ? translate('you_do_not_have_permission_to_modify_this_user')
                : ($result['message'] ?? 'Invalid User ID');
            return $this->jsonResponse(['status' => 'fail', 'msg' => $msg], 422);
        }
        $this->log_activity('change_password', 'user', $result['user_id'], 'Changed user password');
        set_alert('success', translate('information_has_been_updated_successfully'));
        return $this->jsonResponse(['status' => 'success']);
    }

    // disable authentication user list
    public function disable_authentication()
    {
        if (!get_permission('user_disable_authentication', 'is_view')) {
            access_denied();
        }
        if (isset($_POST['search'])) {
            $role = $this->input->post('user_role');
            $this->data['userlist'] = $this->user_model->get_user_list($role, 0);
        }
        if (isset($_POST['auth'])) {
            // Bulk action: validate is_edit permission first.
            if (!get_permission('user', 'is_edit')) {
                access_denied();
            }

            $raw_list = (array) $this->input->post('views_bulk_operations');
            $result = $this->user_service->bulk_activate(
                $raw_list,
                (int) loggedin_role_id(),
                (int) get_loggedin_user_id()
            );
            if ($result['activated'] === 0 && $result['skipped'] === 0) {
                set_alert('error', 'Please select at least one item');
            } else {
                $this->log_activity('bulk_activate', 'user', 0,
                    "Bulk reactivation: {$result['activated']} activated, {$result['skipped']} skipped");
                set_alert('success', translate('information_has_been_updated_successfully'));
            }
            redirect(base_url('user/' . route_hash('disable_authentication')));
        }
        $this->data['title'] = translate('deactivate_account');
        $this->data['sub_page'] = 'user/disable';
        $this->data['main_menu'] = 'user';
        $this->load->view('layout/index', $this->data);
    }

    // AJAX user status toggle
    public function status()
    {
        if (!get_permission('user', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }

        $activate = ($this->input->post('status') === 'true');
        $result = $this->user_service->toggle_status(
            (string) $this->input->post('user_id'),
            $activate,
            (int) loggedin_role_id()
        );

        if (!$result['ok']) {
            $msg = ($result['code'] === 'hierarchy_blocked')
                ? translate('you_do_not_have_permission_to_modify_this_user')
                : translate('access_denied');
            return $this->jsonResponse(['status' => 'error', 'message' => $msg], 403);
        }
        $this->log_activity($result['code'], 'user', $result['user_id'], ucfirst($result['code']) . 'd user account');
        return $this->jsonResponse(['status' => 'success', 'message' => translate('information_has_been_updated_successfully')]);
    }

    // Server-side processing for User Datatable
    public function get_users_server_side($role = '')
    {
        if (!get_permission('user', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $post_role = $this->input->post('active_role_id');
        if ($post_role === false || $post_role === null || $post_role === '') {
            $post_role = $this->input->post('role');
        }
        if ($post_role !== false && $post_role !== null && $post_role !== '') {
            $role = (int) $post_role;
        }
        $role = $this->_resolve_list_role($role);
        if (!$this->_is_role_visible($role)) {
            $role = $this->_resolve_list_role('');
        }
        $gender = $this->input->post('gender');
        $blood_group = $this->input->post('blood_group');
        $status = $this->input->post('status');

        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));
        $search = $this->input->post('search')['value'] ?? '';

        $loggedin_role = loggedin_role_id();
        $loggedin_user_id = get_loggedin_user_id();

        // 1. Total records (with custom filters applied) using User_model
        $total_records = $this->user_model->get_users_count($role, $gender, $blood_group, $status, $loggedin_role, $loggedin_user_id);

        // Define columns map for order mapping
        $columns_map = [
            0 => 'user.id',
            1 => 'user.id',
            2 => 'user.name',
            3 => 'user.user_id',
            4 => 'login_credential.email',
            5 => 'user.mobile_no',
            6 => 'creator.name'
        ];
        $order_col_idx = $this->input->post('order')[0]['column'] ?? 0;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, array('asc', 'desc'))) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_col_idx] ?? 'user.id';

        // 2. Fetch records and filtered count using User_model
        $server_side_result = $this->user_model->get_users_server_side_data(
            $role, 
            $gender, 
            $blood_group, 
            $status, 
            $loggedin_role, 
            $loggedin_user_id, 
            $search, 
            $start, 
            $length, 
            $order_col, 
            $order_dir
        );

        $total_filtered = $server_side_result['total_filtered'];
        $users = $server_side_result['data'];

        $data = [];
        $i = $start + 1;
        
        $can_edit = get_permission('user', 'is_edit');
        $can_delete = get_permission('user', 'is_delete');

        foreach ($users as $row) {
            $photo_url = $this->app_lib->get_image_url('user/' . $row->photo);
            $photo_html = '<img class="rounded" src="' . $photo_url . '" width="40" height="40" />';

            $status_icon = ($row->active == 1) ? 'fa-toggle-on text-success' : 'fa-toggle-off text-danger';
            $status_tooltip = ($row->active == 1) ? translate('deactivate') : translate('activate');
            $disabled = (!$can_edit) ? 'disabled' : '';
            $status_html = '
                <button type="button" class="btn btn-circle btn-default icon btn-status-toggle" data-id="' . html_escape(encrypt_id($row->id)) . '" data-active="' . $row->active . '" data-toggle="tooltip" data-original-title="' . $status_tooltip . '" ' . $disabled . '>
                    <i class="fas ' . $status_icon . '"></i>
                </button>';

            $action_html = '';
            if ($can_edit) {
                $action_html .= $status_html;
                $action_html .= '
                    <a href="' . base_url('user/profile/' . encrypt_id($row->id)) . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . translate('edit') . '">
                        <i class="fas fa-pen-nib"></i>
                    </a>
                    <a href="' . base_url('user/profile/' . encrypt_id($row->id)) . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . translate('profile') . '">
                        <i class="far fa-arrow-alt-circle-right"></i>
                    </a>';
            }
            if ($loggedin_role == 1) {
                $action_html .= '
                    <a href="' . base_url('authentication/autoLogin/' . encrypt_id($row->id)) . '" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="' . translate('login_as_user') . '">
                        <i class="fas fa-user-secret"></i>
                    </a>';
            }
            if ($can_delete) {
                $action_html .= ' ' . btn_delete('user/delete/' . encrypt_id($row->id));
            }

            $data[] = [
                $i++,
                $photo_html,
                html_escape($row->name),
                html_escape($row->user_id),
                html_escape($row->email),
                html_escape($row->mobile_no ?? ''),
                html_escape($row->creator_name ?? 'System'),
                $action_html
            ];
        }

        return $this->jsonResponse([
            "draw" => $draw,
            "recordsTotal" => $total_records,
            "recordsFiltered" => $total_filtered,
            "data" => $data,
            "csrfHash" => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * Roles visible to the logged-in user (for tabs and list filtering).
     */
    private function _get_visible_roles()
    {
        $loggedin_role = loggedin_role_id();
        $has_level = $this->db->field_exists('level', 'roles');
        if ($loggedin_role == ROLE_SUPERMAN_ID) {
            $this->db->where('id !=', ROLE_SUPERMAN_ID);
        } elseif ($has_level) {
            // Level-based: only roles strictly BELOW the viewer's authority level.
            $this->db->where('level >', role_level((int) $loggedin_role));
        } else {
            $this->db->where('id >', $loggedin_role);
        }
        // Tabs ordered by hierarchy level (Superadmin, Admin, Manager, …), not id.
        return $this->db->order_by($has_level ? 'level' : 'id', 'ASC')->get('roles')->result();
    }

    /**
     * Always return a concrete role id — never empty (avoids listing all users).
     */
    private function _resolve_list_role($role = '')
    {
        if ($role !== '' && $role !== null && $role !== false && (int) $role > 0) {
            return (int) $role;
        }
        $roles = $this->_get_visible_roles();
        return !empty($roles) ? (int) $roles[0]->id : 0;
    }

    /**
     * Ensure requested role is allowed for the current user.
     */
    private function _is_role_visible($role_id)
    {
        if ((int) $role_id <= 0) {
            return false;
        }
        foreach ($this->_get_visible_roles() as $role) {
            if ((int) $role->id === (int) $role_id) {
                return true;
            }
        }
        return false;
    }
}

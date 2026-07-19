<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Module_model.php
 */

class Module_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // Insert a new module or permission
    public function insert($data): bool
    {
        $module_type = $this->input->post('module_type');
        $this->db->trans_start();

        if ($module_type == 'new') {
            $module_name = $this->input->post('name', true);
            $data1 = array(
                'name' => ucfirst($module_name),
                'prefix' => preg_replace('/\s+/', '_', strtolower(trim($module_name))),
                'system' => 1
            );
            $this->db->insert('permission_modules', $data1);
            $module_id = $this->db->insert_id();

            // Update sorted column
            $this->db->where('id', $module_id);
            $this->db->update('permission_modules', array('sorted' => $module_id));
        } else {
            $module_id = $this->input->post('existing_module_id', true);
        }

        $permission_name = $this->input->post('permission_name', true) ?: $this->input->post('name', true);

        $data2 = array(
            'module_id' => $module_id,
            'name' => ucfirst($permission_name),
            'prefix' => preg_replace('/\s+/', '_', strtolower(trim($permission_name))),
            'show_view' => $this->input->post('show_view') ?? 0,
            'show_add' => $this->input->post('show_add') ?? 0,
            'show_edit' => $this->input->post('show_edit') ?? 0,
            'show_delete' => $this->input->post('show_delete') ?? 0,
        );

        $this->db->insert('permission', $data2);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            // Automatically generate files (controllers, models, views) ONLY if create_files is Yes (1)
            if ($this->input->post('create_files') == '1') {
                if ($module_type == 'new') {
                    $module_name = $this->input->post('name', true);
                    $this->generate_module_files($module_name);
                } else {
                    $existing_module = $this->db->get_where('permission_modules', array('id' => $module_id))->row_array();
                    if ($existing_module) {
                        $this->generate_module_files($existing_module['name']);
                    }
                }
                $this->generate_module_files($permission_name);
            }
            return true;
        }

        return false;
    }

    // Get all modules with permissions
    public function get_permission_modules_list()
    {
        $this->db->select('
            permission_modules.id as module_id,
            permission_modules.name as module_name,
            permission_modules.prefix as module_prefix,
            permission.id as permission_id,
            permission.name as permission_name,
            permission.prefix as permission_prefix,
            permission.show_view,
            permission.show_add,
            permission.show_edit,
            permission.show_delete
        ');
        $this->db->from('permission_modules');
        $this->db->join('permission', 'permission.module_id = permission_modules.id', 'inner');
        $this->db->order_by('permission_modules.name', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_permission_modules_name()
    {
        // Make sure the table exists and 'name' column exists
        $this->db->select('id, name'); // always specify columns
        $this->db->from('permission_modules');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result_array();
    }


    // Get single permission module by module_id and permission_id
    public function get_single_permission_module($module_id, $permission_id)
    {
        $this->db->select('
            permission_modules.id as module_id,
            permission_modules.name as module_name,
            permission_modules.prefix as module_prefix,
            permission.id as permission_id,
            permission.name as permission_name,
            permission.prefix as permission_prefix,
            permission.show_view,
            permission.show_add,
            permission.show_edit,
            permission.show_delete
        ');
        $this->db->from('permission_modules');
        $this->db->join('permission', 'permission.module_id = permission_modules.id', 'inner');
        $this->db->where('permission_modules.id', $module_id);
        $this->db->where('permission.id', $permission_id);
        return $this->db->get()->row_array();
    }

    // Update module and permission     
    public function update_module_permission($module_id, $permission_id, $data)
    {
        $this->db->trans_start();

        $data1 = array(
            'name' => $data['module_name'],
            'prefix' => preg_replace('/\s+/', '_', strtolower(trim($data['module_prefix']))),
        );

        $data2 = array(
            'name' => $data['permission_name'],
            'prefix' => preg_replace('/\s+/', '_', strtolower(trim($data['permission_prefix']))),
            'show_view' => $data['show_view'] ?? 0,
            'show_add' => $data['show_add'] ?? 0,
            'show_edit' => $data['show_edit'] ?? 0,
            'show_delete' => $data['show_delete'] ?? 0
        );

        $this->db->where('id', $module_id);
        $this->db->update('permission_modules', $data1);

        $this->db->where('id', $permission_id);
        $this->db->update('permission', $data2);

        $this->db->trans_complete();
        return $this->db->trans_status() ? true : false;
    }

    // Count permissions for a module
    public function count_permissions_for_module($module_id)
    {
        $this->db->where('module_id', $module_id);
        return $this->db->count_all_results('permission');
    }

    // Delete permission or module
    public function delete_permission_and_module($module_id, $permission_id)
    {
        $this->db->trans_start();

        // 1. Delete associated user privileges first (child of permission)
        $this->db->where('permission_id', $permission_id);
        $this->db->delete('user_privileges');

        $permission_count = $this->count_permissions_for_module($module_id);

        if ($permission_count > 1) {
            // 2. Delete the specific permission only
            $this->db->where('id', $permission_id);
            $this->db->delete('permission');
        } else {
            // 2. Delete child permission first to satisfy FK constraint
            $this->db->where('id', $permission_id);
            $this->db->delete('permission');

            // 3. Delete parent module
            $this->db->where('id', $module_id);
            $this->db->delete('permission_modules');
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? true : false;
    }

    // Automatically generate Controller, Model, and View files/folders
    private function generate_module_files($name)
    {
        if (empty($name)) {
            return;
        }

        // Sanitize input to prevent Path Traversal / Arbitrary File Creation
        $name = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', trim($name));
        if (empty($name)) {
            return;
        }

        $className = ucfirst(str_replace(' ', '_', $name));
        $lowerName = strtolower(str_replace(' ', '_', $name));

        // 1. Generate Controller
        $controller_path = APPPATH . 'controllers/' . $className . '.php';
        if (!file_exists($controller_path)) {
            $controller_template = "<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : " . $className . ".php
 */

class " . $className . " extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        \$this->load->model('" . $className . "_model');
        if (loggedin_role_id() != 1) {
            access_denied();
        }
    }

    public function index()
    {
        \$this->data['title'] = translate('" . $lowerName . "');
        \$this->data['sub_page'] = '" . $lowerName . "/index';
        \$this->data['main_menu'] = '" . $lowerName . "';
        \$this->load->view('layout/index', \$this->data);
    }
}
";
            file_put_contents($controller_path, $controller_template, LOCK_EX);
        }

        // 2. Generate Model
        $model_path = APPPATH . 'models/' . $className . '_model.php';
        if (!file_exists($model_path)) {
            $model_template = "<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : " . $className . "_model.php
 */

class " . $className . "_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }
}
";
            file_put_contents($model_path, $model_template, LOCK_EX);
        }

        // 3. Generate View Directory & Index Page
        $view_dir = APPPATH . 'views/' . $lowerName;
        if (!is_dir($view_dir)) {
            mkdir($view_dir, 0755, true);
        }
        $view_path = $view_dir . '/index.php';
        if (!file_exists($view_path)) {
            $view_template = "<section class=\"panel\">
    <header class=\"panel-heading\">
        <h4 class=\"panel-title\">
            <i class=\"fas fa-list-ul\"></i> <?= translate('" . $lowerName . "_list'); ?>
        </h4>
    </header>
    <div class=\"panel-body\">
        <div class=\"alert alert-info mb-none\">
            Welcome to the new <strong><?= translate('" . $lowerName . "'); ?></strong> module index page!
        </div>
    </div>
</section>
";
            file_put_contents($view_path, $view_template, LOCK_EX);
        }
    }
}

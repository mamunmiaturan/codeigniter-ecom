<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Migrategenerator.php
 */
class Migrategenerator extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            exit("This controller can only be accessed via CLI." . PHP_EOL);
        }
    }

    /**
     * Create a new migration file
     * 
     * @param string $name Table name
     */
    public function run($name = '')
    {
        if (empty($name)) {
            echo "Error: Migration name (table name) is required." . PHP_EOL;
            echo "Usage: php artisan make:migration [table_name]" . PHP_EOL;
            return;
        }

        $dir = FCPATH . 'database/migrations/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, TRUE);
        }

        if (!is_writable($dir)) {
            echo "Error: Directory $dir is not writable." . PHP_EOL;
            return;
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$name}_table.php";
        $path = FCPATH . 'database/migrations/' . $filename;

        // Clean name for class
        $cleanName = str_replace(' ', '_', ucwords(str_replace('_', ' ', $name)));
        $classname = "Migration_Create_{$cleanName}_Table";
        
        $template = "<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class $classname extends CI_Migration {

    public function up()
    {
        \$this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            // Add other fields here
            'created_at datetime DEFAULT CURRENT_TIMESTAMP',
            'updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
        \$this->dbforge->add_key('id', TRUE);
        \$this->dbforge->create_table('$name');
    }

    public function down()
    {
        \$this->dbforge->drop_table('$name', TRUE);
    }
}
";
        if (file_put_contents($path, $template)) {
            echo "Migration created successfully: $filename" . PHP_EOL;
        } else {
            echo "Error: Failed to create migration file." . PHP_EOL;
        }
    }

    /**
     * Create a new seeder file
     * 
     * @param string $name Seeder class name
     */
    public function seed($name = '')
    {
        if (empty($name)) {
            echo "Error: Seeder name is required." . PHP_EOL;
            echo "Usage: php artisan make:seed [SeederName]" . PHP_EOL;
            return;
        }

        $dir = FCPATH . 'database/seeders/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, TRUE);
        }

        if (!is_writable($dir)) {
            echo "Error: Directory $dir is not writable." . PHP_EOL;
            return;
        }

        // Ensure name ends with Seeder for consistency
        if (stripos($name, 'Seeder') === false) {
            $name .= 'Seeder';
        }

        $filename = "{$name}.php";
        $path = FCPATH . 'database/seeders/' . $filename;

        if (file_exists($path)) {
            echo "Error: Seeder $name already exists." . PHP_EOL;
            return;
        }

        $template = "<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class $name extends Seeder {

    public function run()
    {
        echo \"Running $name...\" . PHP_EOL;
        
        // Seeding logic here
        // \$data = [
        //     'name' => 'Sample Name',
        //     'status' => 'active'
        // ];
        // \$this->db->insert('table_name', \$data);
    }
}
";
        if (file_put_contents($path, $template)) {
            echo "Seeder created successfully: $filename" . PHP_EOL;
        } else {
            echo "Error: Failed to create seeder file." . PHP_EOL;
        }
    }

    /**
     * Create a new controller file
     * 
     * @param string $name Controller name
     */
    public function controller($name = '')
    {
        if (empty($name)) {
            echo "Error: Controller name is required." . PHP_EOL;
            return;
        }

        $filename = ucfirst($name) . ".php";
        $path = APPPATH . 'controllers/' . $filename;

        if (file_exists($path)) {
            echo "Error: Controller $name already exists." . PHP_EOL;
            return;
        }

        $template = "<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class " . ucfirst($name) . " extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        // \$this->load->model('" . strtolower($name) . "_model');
    }

    public function index()
    {
        \$this->data['title'] = '" . ucfirst($name) . "';
        \$this->data['sub_page'] = '" . strtolower($name) . "/index';
        \$this->data['main_menu'] = '" . strtolower($name) . "';
        \$this->load->view('layout/index', \$this->data);
    }
}
";
        if (file_put_contents($path, $template)) {
            echo "Controller created successfully: $filename" . PHP_EOL;
            
            // Create view directory if not exists
            $view_dir = APPPATH . 'views/' . strtolower($name);
            if (!is_dir($view_dir)) {
                mkdir($view_dir, 0755, TRUE);
                file_put_contents($view_dir . '/index.php', "<!-- View for $name -->\n<div class=\"panel\">\n    <div class=\"panel-heading\">\n        <h3 class=\"panel-title\">$name List</h3>\n    </div>\n    <div class=\"panel-body\">\n        Welcome to your new module!\n    </div>\n</div>");
            }
        }
    }

    /**
     * Create a new model file
     * 
     * @param string $name Model name (table name)
     */
    public function model($name = '')
    {
        if (empty($name)) {
            echo "Error: Model name is required." . PHP_EOL;
            return;
        }

        $filename = ucfirst($name) . "_model.php";
        $path = APPPATH . 'models/' . $filename;

        if (file_exists($path)) {
            echo "Error: Model $name already exists." . PHP_EOL;
            return;
        }

        $classname = ucfirst($name) . "_model";
        $template = "<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class $classname extends Base_Model {

    protected \$table = '$name';
    protected \$useTimestamps = true;
    protected \$useSoftDelete = true;
    protected \$allowedFields = []; // Add fields that can be mass-assigned

    public function __construct()
    {
        parent::__construct();
    }
}
";
        if (file_put_contents($path, $template)) {
            echo "Model created successfully: $filename" . PHP_EOL;
        }
    }

    /**
     * Create a new faker seeder file
     * 
     * @param string $name Table name
     */
    public function faker($name = '')
    {
        if (empty($name)) {
            echo "Error: Table name for faker is required." . PHP_EOL;
            return;
        }

        $filename = ucfirst($name) . "Faker.php";
        $path = FCPATH . 'database/seeders/' . $filename;

        if (file_exists($path)) {
            echo "Error: Faker seeder $filename already exists." . PHP_EOL;
            return;
        }

        $classname = ucfirst($name) . "Faker";
        $template = "<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Faker Seeder for $name
 */
class $classname extends Seeder {

    public function run()
    {
        echo \"Generating fake data for $name...\" . PHP_EOL;
        
        // This is a basic faker. For complex data, you can use PHP Faker library.
        for (\$i = 1; \$i <= 10; \$i++) {
            \$data = [
                'name' => 'Sample ' . ucfirst('$name') . ' ' . \$i,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // You can customize the fields based on your table
            \$this->db->insert('$name', \$data);
            echo \".\";
        }
        
        echo PHP_EOL . \"10 fake records created in $name.\" . PHP_EOL;
    }
}
";
        if (file_put_contents($path, $template)) {
            echo "Faker seeder created successfully: $filename" . PHP_EOL;
            echo "Run it with: php artisan db:seed $classname" . PHP_EOL;
        }
    }

    /**
     * Generate a full CRUD scaffold
     * 
     * @param string $name Table name
     */
    public function crud($name = '')
    {
        if (empty($name)) {
            echo "Error: Table name for CRUD is required." . PHP_EOL;
            return;
        }

        echo "Generating CRUD for: $name" . PHP_EOL;

        // 1. Create Model
        $this->model($name);

        // 2. Create Controller
        $this->controller($name);

        // 3. Enhance the generated view with a sample table
        $view_path = APPPATH . 'views/' . strtolower($name) . '/index.php';
        $table_view = "
<div class=\"panel\">
    <div class=\"panel-heading\">
        <h3 class=\"panel-title\">" . ucfirst($name) . " List</h3>
        <div class=\"panel-options\">
            <a href=\"#\" class=\"btn btn-primary btn-sm\">Add New</a>
        </div>
    </div>
    <div class=\"panel-body\">
        <div class=\"table-responsive\">
            <table class=\"table table-bordered table-hover\">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Created At</th>
                        <th class=\"text-center\">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty(\$list)): ?>
                        <?php foreach (\$list as \$row): ?>
                        <tr>
                            <td><?php echo \$row['id']; ?></td>
                            <td><?php echo \$row['name'] ?? 'N/A'; ?></td>
                            <td><?php echo \$row['created_at']; ?></td>
                            <td class=\"text-center\">
                                <a href=\"#\" class=\"btn btn-default btn-xs\"><i class=\"fa fa-edit\"></i></a>
                                <a href=\"#\" class=\"btn btn-danger btn-xs\"><i class=\"fa fa-trash\"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan=\"4\" class=\"text-center\">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
";
        file_put_contents($view_path, $table_view);

        echo "CRUD generation completed successfully!" . PHP_EOL;
    }
}

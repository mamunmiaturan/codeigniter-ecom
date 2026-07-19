<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : MY_Model.php
 */

/**
 * Class Base_Model
 */
class Base_Model extends CI_Model
{
    /**
     * @var string Table name
     */
    protected $table;

    /**
     * @var string Primary key
     */
    protected $primaryKey = 'id';

    /**
     * @var string Return type
     */
    protected $returnType = 'array';

    /**
     * @var array
     */
    protected $allowedFields = [];

    /**
     * @var bool Use timestamps
     */
    protected $useTimestamps = false;
    protected $created_at = 'created_at';
    protected $updated_at = 'updated_at';
    protected $deleted_at = 'deleted_at';

    /**
     * @var bool Use soft delete
     */
    protected $useSoftDelete = false;

    /**
     * @var string
     */
    private $baseSelect = '*';

    /**
     * @var true
     */
    private $onlyTrashed;

    public function __construct()
    {
        parent::__construct();

        // if the table name is not set, use class name without model part
        if (!$this->table) {
            $this->table = strtolower(str_replace('_model', 's', get_class($this)));
        }
    }

    /**
     * where
     */
    public function where($key, $value = NULL, $escape = NULL): Base_Model
    {
        $this->db->where($key, $value, $escape);
        return $this;
    }

    /**
     * @param int|null $id
     * @return mixed
     */
    public function find($id = null)
    {
        if ($id) {
            $this->db->where('id', $id);
        }

        if ($this->useTimestamps and $this->useSoftDelete) {
            $this->db->where($this->deleted_at, null);
        }

        $result = $this->db->get($this->table);


        if ($this->returnType === 'object') {
            return $result->row();
        }

        return $result->row_array();
    }

    /**
     * @return mixed
     */
    public function findAll()
    {
        $this->db->select($this->baseSelect);

        /**
         * If useTimestamps and useSoftDelete is true
         *      If onlyTrashed is true
         *          show only deleted items
         *      else
         *          don't show deleted items
         */
        if ($this->useTimestamps && $this->useSoftDelete) {
            if ($this->onlyTrashed) {
                $this->db->where($this->deleted_at . ' IS NOT NULL'); // show only deleted items
            } else {
                $this->db->where($this->deleted_at, null); // don't show deleted items
            }
        }

        $result = $this->db->get($this->table);

        if ($this->returnType === 'object') {
            return $result->result();
        }

        return $result->result_array();
    }

    /**
     * @param $data
     * @return false
     */
    public function insert($data)
    {
        if (empty($data)) {
            return false;
        }

        // only allowed fields can be inserted into a database other data fields will be ignored
        if (!empty($this->allowedFields)) {
            $data = array_intersect_key($data, array_flip($this->allowedFields));
        }

        if ($this->useTimestamps) {
            if ($this->created_at)
                $data[$this->created_at] = date('Y-m-d H:i:s');
        }

        if ($this->db->insert($this->table, $data)) {
            $insert_id = $this->db->insert_id();
            $this->log_activity('create', $insert_id, $data);
            return $insert_id;
        }
        return false;
    }

    /**
     * @param int $id
     * @param $data
     * @return bool
     */
    public function update($id, $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }

        // only allowed fields can be inserted into a database other data fields will be ignored
        if (!empty($this->allowedFields)) {
            $data = array_intersect_key($data, array_flip($this->allowedFields));
        }

        if ($this->useTimestamps) {
            $data[$this->updated_at] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $id);
        if ($this->db->update($this->table, $data)) {
            $this->log_activity('update', $id, $data);
            return true;
        }
        return false;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);

        if ($this->useTimestamps and $this->useSoftDelete) {
            $data[$this->deleted_at] = date('Y-m-d H:i:s');
            if ($this->db->update($this->table, $data)) {
                $this->log_activity('delete', $id);
                return true;
            }
            return false;
        }

        if ($this->db->delete($this->table)) {
            $this->log_activity('delete', $id);
            return true;
        }
        return false;
    }

    /**
     * Log activity to database
     */
    protected function log_activity($action, $id, $data = [])
    {
        // Don't log if it's the activity_logs table itself
        if ($this->table === 'activity_logs' || $this->table === 'jobs') {
            return;
        }

        $ci =& get_instance();
        
        // Try to get user ID from session
        $user_id = null;
        if (isset($ci->session)) {
            $user_id = $ci->session->userdata('loggedin_userid') ?: $ci->session->userdata('user_id');
        }

        $ci->db->insert('activity_logs', [
            'user_id' => $user_id,
            'table_name' => $this->table,
            'row_id' => $id,
            'action' => $action,
            'payload' => !empty($data) ? json_encode($data) : null,
            'ip_address' => $ci->input->ip_address(),
            'user_agent' => $ci->input->user_agent()
        ]);
    }

    public function baseSelect(string $select): Base_Model
    {
        $this->baseSelect = $select;
        return $this;
    }

    public function limit(int $limit, int $offset = 0): Base_Model
    {
        $this->db->limit($limit, $offset);
        return $this;
    }

    public function withTrashed(): Base_Model
    {
        $this->useSoftDelete = false;
        return $this;
    }

    public function onlyTrashed(): Base_Model
    {
        $this->onlyTrashed = true;
        return $this;
    }

    public function orderBy(string $orderBy, string $order = 'ASC'): Base_Model
    {
        $this->db->order_by($orderBy, $order);
        return $this;
    }

    public function whereGroupStart(): Base_Model
    {
        $this->db->group_start();
        return $this;
    }

    public function orWhere(string $key, $value): Base_Model
    {
        $this->db->or_where($key, $value);
        return $this;
    }

    public function whereGroupEnd(): Base_Model
    {
        $this->db->group_end();
        return $this;
    }

    /**
     * Server-side DataTables Helper
     * 
     * @param array $postData The $_POST data from DataTables
     * @param array $columns Map of column index to table field name
     * @param array $searchable List of fields to search in
     * @return array Response for DataTables
     */
    public function get_datatable($postData, $columns = [], $searchable = [])
    {
        $draw = $postData['draw'];
        $start = $postData['start'];
        $rowperpage = $postData['length'];
        $searchValue = $postData['search']['value'];

        // Sorting
        $columnIndex = $postData['order'][0]['column'];
        $columnName = isset($columns[$columnIndex]) ? $columns[$columnIndex] : $this->primaryKey;
        $columnSortOrder = $postData['order'][0]['dir'];

        // 1. Total records without filtering
        $totalRecords = $this->db->count_all_results($this->table);

        // 2. Total records with filtering
        $this->db->from($this->table);
        if ($searchValue != '') {
            $this->db->group_start();
            $fields = !empty($searchable) ? $searchable : $this->allowedFields;
            foreach ($fields as $field) {
                $this->db->or_like($field, $searchValue);
            }
            $this->db->group_end();
        }
        $totalRecordwithFilter = $this->db->count_all_results();

        // 3. Fetch records
        $this->db->select('*');
        $this->db->from($this->table);
        if ($searchValue != '') {
            $this->db->group_start();
            $fields = !empty($searchable) ? $searchable : $this->allowedFields;
            foreach ($fields as $field) {
                $this->db->or_like($field, $searchValue);
            }
            $this->db->group_end();
        }
        $this->db->order_by($columnName, $columnSortOrder);
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result_array();

        return [
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $records
        ];
    }
}

class MY_Model extends Base_Model
{

    function __construct()
    {
        parent::__construct();
    }

    function get_list($table_name, $where_array = NULL, $single = FALSE, $columns = NULL)
    {
        if ($columns)
            $this->db->select($columns);

        if (!empty($where_array))
            $this->db->where($where_array);

        if ($single) {
            $method = 'row_array';
        } else {
            $method = 'result_array';
            $this->db->order_by('id', 'ASC');
        }
        return $this->db->get($table_name)->$method();
    }
}

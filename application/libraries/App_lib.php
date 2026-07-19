<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : App_lib.php
 */

class App_lib
{
    protected $CI;

    function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('app_model');
    }

    function pass_hashed($password)
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        return $hashed;
    }

    function verify_password($password, $encrypt_password): bool
    {
        return password_verify($password, $encrypt_password);
    }

    function getUserslList($all = '')
    {
        $result = $this->CI->app_model->get_users_list_by_role_exclude(array(1, 2, 3));
        $data = array('' => translate('select'));
        if ($all == 'all') {
            $data['all'] = translate('all_select');
        }
        foreach ($result as $row) {
            $data[$row->id] = $row->name . " (" . $row->user_id . ")";
        }
        return $data;
    }

    function get_users_list($role_id = null)
    {
        $result = $this->CI->app_model->get_users_list_by_role($role_id);
        $data = array('' => translate('select'));
        foreach ($result as $row) {
            $data[$row->id] = $row->name;
        }
        return $data;
    }

    function get_table_list($table, $where = [])
    {
        $result = $this->CI->app_model->get_table_records($table, $where);
        $data = array('' => translate('select'));
        foreach ($result as $row) {
            $name_field = isset($row->name) ? 'name' : (isset($row->fee_name) ? 'fee_name' : (isset($row->point_name) ? 'point_name' : 'id'));
            $data[$row->id] = $row->$name_field;
        }
        return $data;
    }

    function get_table($table, $id = NULL, $single = FALSE)
    {
        return $this->CI->app_model->get_table_records_ordered($table, $id, $single);
    }

    function upload_image($role)
    {
        $return_photo = 'default.png';
        $old_user_photo = $this->CI->input->post('old_user_photo');
        if (!empty($old_user_photo)) {
            $old_user_photo = basename($old_user_photo);
        }
        if (isset($_FILES["user_photo"]) && !empty($_FILES['user_photo']['name'])) {
            // MIME type guard — belt-and-suspenders beyond CI's extension check
            $allowed_mimes = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];
            $detected_mime = function_exists('mime_content_type')
                ? mime_content_type($_FILES['user_photo']['tmp_name'])
                : '';
            if ($detected_mime && !in_array($detected_mime, $allowed_mimes)) {
                log_message('error', 'upload_image: rejected MIME type ' . $detected_mime . ' for role=' . $role);
                return $old_user_photo ?: $return_photo;
            }

            $config['upload_path']   = './uploads/images/' . $role . '/';
            $config['allowed_types'] = 'jpg|png';
            $config['overwrite']     = FALSE;
            $config['encrypt_name']  = TRUE;
            $config['max_size']      = 2048; // 2 MB
            $this->CI->upload->initialize($config);
            if ($this->CI->upload->do_upload("user_photo")) {
                if (!empty($old_user_photo)) {
                    $unlink_path = 'uploads/images/' . $role . '/';
                    if (file_exists($unlink_path . $old_user_photo)) {
                        @unlink($unlink_path . $old_user_photo);
                    }
                }
                $return_photo = $this->CI->upload->data('file_name');
            }
        } else {
            if (!empty($old_user_photo)) {
                $return_photo = $old_user_photo;
            }
        }
        return $return_photo;
    }

    public function get_image_url($file_path = '')
    {
        $default_img = 'uploads/images/users/default.png';

        if (empty($file_path)) {
            return base_url($default_img);
        }

        // Normalize slashes
        $file_path = str_replace('\\', '/', $file_path);

        // 1. Direct check
        $path = 'uploads/images/' . $file_path;
        if (is_file($path)) {
            return base_url($path);
        }

        // 2. Check if the path is relative to the project root directly
        if (is_file($file_path)) {
            return base_url($file_path);
        }

        // 3. If starts with 'user/', replace with 'users/'
        if (strpos($file_path, 'user/') === 0) {
            $plural_path = 'users/' . substr($file_path, 5);
            $path = 'uploads/images/' . $plural_path;
            if (is_file($path)) {
                return base_url($path);
            }
        }

        // 4. If starts with 'users/', check 'user/' just in case
        if (strpos($file_path, 'users/') === 0) {
            $singular_path = 'user/' . substr($file_path, 6);
            $path = 'uploads/images/' . $singular_path;
            if (is_file($path)) {
                return base_url($path);
            }
        }

        // 5. If it is a bare filename (no slashes), check inside uploads/images/users/ and uploads/images/client/
        if (strpos($file_path, '/') === false) {
            $users_path = 'uploads/images/users/' . $file_path;
            if (is_file($users_path)) {
                return base_url($users_path);
            }
        }

        // 6. Fallback to default user image
        if (is_file($default_img)) {
            return base_url($default_img);
        }

        return base_url('uploads/images/users/default.png');
    }

    function get_lang_image_url($id = '', $thumb = TRUE)
    {
        $file_path = 'uploads/language_flags/flag_' . $id . '_thumb.png';
        if (file_exists($file_path)) {
            if ($thumb == TRUE) {
                $image_url = base_url($file_path);
            } else {
                $image_url = base_url('uploads/language_flags/flag_' . $id . '.png');
            }
        } else {
            if ($thumb == TRUE) {
                $image_url = base_url('uploads/language_flags/defualt_thumb.png');
            } else {
                $image_url = base_url('uploads/language_flags/default.png');
            }
        }
        return $image_url;
    }

    function generate_csrf()
    {
        return '<input type="hidden" name="' . $this->CI->security->get_csrf_token_name() . '" value="' . $this->CI->security->get_csrf_hash() . '" />';
    }

    function getRoles_emp($arra_id = [1, 2, 3, 4])
    {
        $rolelist = $this->CI->app_model->get_roles_excluding($arra_id);
        $role_array = array('' => translate('select'));
        foreach ($rolelist as $role) {
            $role_array[$role->id] = $role->name;
        }
        return $role_array;
    }

    function getRoles($logged_in_role)
    {
        $rolelist = $this->CI->app_model->get_roles_greater_than($logged_in_role);
        $role_array = array('' => translate('select'));
        foreach ($rolelist as $role) {
            $role_array[$role->id] = $role->name;
        }
        return $role_array;
    }

    function get_select_list($table)
    {
        return $this->CI->app_model->get_table_records($table);
    }

    function getSelectList($table, $all = '')
    {
        $arrayData = array("" => translate('select'));
        if ($all == 'all') {
            $arrayData['all'] = translate('all_select');
        }
        $result = $this->CI->app_model->get_table_records($table);
        foreach ($result as $row) {
            $arrayData[$row->id] = $row->name;
        }
        return $arrayData;
    }

    function get_credential_id($user_id, $users = TRUE)
    {
        $exclude_role_7 = ($users == TRUE);
        $result = $this->CI->app_model->get_credential_by_user($user_id, $exclude_role_7);
        return $result['id'] ?? null;
    }

    function get_animations_list()
    {
        $animations = array(
            'fadeIn' => "fadeIn",
            'fadeInUp' => "fadeInUp",
            'fadeInDown' => "fadeInDown",
            'fadeInLeft' => "fadeInLeft",
            'fadeInRight' => "fadeInRight",
            'bounceIn' => "bounceIn",
            'rotateInUpLeft' => "rotateInUpLeft",
            'rotateInDownLeft' => "rotateInDownLeft",
            'rotateInUpRight' => "rotateInUpRight",
            'rotateInDownRight' => "rotateInDownRight"
        );
        return $animations;
    }

    function get_months_list($m)
    {
        $months = array(
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July ',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        );
        return $months[$m];
    }

    function get_date_format()
    {
        $date = array(
            "%Y-%m-%d" => "yyyy-mm-dd",
            "%Y/%m/%d" => "yyyy/mm/dd",
            "%Y.%m.%d" => "yyyy.mm.dd",
            "%d-%b-%Y" => "dd-mmm-yyyy",
            "%d/%b/%Y" => "dd/mmm/yyyy",
            "%d.%b.%Y" => "dd.mmm.yyyy",
            "%d-%m-%Y" => "dd-mm-yyyy",
            "%d/%m/%Y" => "dd/mm/yyyy",
            "%d.%m.%Y" => "dd.mm.yyyy",
            "%m-%d-%Y" => "mm-dd-yyyy",
            "%m/%d/%Y" => "mm/dd/yyyy",
            "%m.%d.%Y" => "mm.dd.yyyy"
        );
        return $date;
    }

    // Get marital status list
    function get_marital_status()
    {
        $marital_status = array(
            '' => translate('select'),
            'single' => translate('single'),
            'married' => translate('married'),
            'unmarried' => translate('unmarried'),
            'divorced' => translate('divorced'),
            'widowed' => translate('widowed'),
            'separated' => translate('separated')
        );

        return $marital_status;
    }

    // Get religion list
    function get_religion()
    {
        $religion = array(
            '' => translate('select'),
            'Islam' => 'Islam',
            'Hinduism' => 'Hinduism',
            'Christianity' => 'Christianity',
            'Buddhism' => 'Buddhism',
            'Other' => translate('other')
        );

        return $religion;
    }

    // Get gender list
    function get_gender()
    {
        $gender = array(
            '' => translate('select'),
            'Male' => translate('male'),
            'Female' => translate('female'),
            'Other' => translate('other')
        );

        return $gender;
    }

    // Get nationality list
    function get_nationality()
    {
        $nationality = array(
            '' => translate('select'),
            'Bangladeshi' => 'Bangladeshi',
            'Indian' => 'Indian',
            'Pakistani' => 'Pakistani',
            'Nepali' => 'Nepali',
            'Bhutanese' => 'Bhutanese',
            'Other' => translate('other')
        );

        return $nationality;
    }

    // Get education level list
    function get_education_level()
    {
        $education = array(
            '' => translate('select'),
            'SSC' => 'SSC',
            'HSC' => 'HSC',
            'Diploma' => 'Diploma',
            'Bachelor' => 'Bachelor',
            'Masters' => 'Masters',
            'PhD' => 'PhD'
        );

        return $education;
    }

    // Get occupation list
    function get_occupation()
    {
        $occupation = array(
            '' => translate('select'),
            'Student' => translate('student'),
            'Teacher' => translate('teacher'),
            'Engineer' => translate('engineer'),
            'Doctor' => translate('doctor'),
            'Businessman' => translate('businessman'),
            'Farmer' => translate('farmer'),
            'Freelancer' => translate('freelancer'),
            'Job Holder' => translate('job_holder'),
            'Other' => translate('other')
        );

        return $occupation;
    }

    // Get yes/no option list
    function get_yes_no()
    {
        $yes_no = array(
            '' => translate('select'),
            'Yes' => translate('yes'),
            'No' => translate('no')
        );

        return $yes_no;
    }

    // Get status list
    function get_status()
    {
        $status = array(
            '' => translate('select'),
            'Active' => translate('active'),
            'Inactive' => translate('inactive'),
            'Pending' => translate('pending'),
            'Blocked' => translate('blocked')
        );

        return $status;
    }

    // Get blood group list
    function get_blood_group()
    {
        $blood_group = array(
            '' => translate('select'),
            'A+' => 'A+',
            'A-' => 'A-',
            'B+' => 'B+',
            'B-' => 'B-',
            'O+' => 'O+',
            'O-' => 'O-',
            'AB+' => 'AB+',
            'AB-' => 'AB-'
        );

        return $blood_group;
    }

    function timezone_list()
    {
        static $timezones = null;
        if ($timezones === null) {
            $timezones = [];
            $offsets = [];
            $now = new DateTime('now', new DateTimeZone('UTC'));
            foreach (DateTimeZone::listIdentifiers() as $timezone) {
                $now->setTimezone(new DateTimeZone($timezone));
                $offsets[] = $offset = $now->getOffset();
                $timezones[$timezone] = '(' . $this->format_GMT_offset($offset) . ') ' . $this->format_timezone_name($timezone);
            }
            array_multisort($offsets, $timezones);
        }
        return $timezones;
    }

    function format_GMT_offset($offset)
    {
        $hours = intval($offset / 3600);
        $minutes = abs(intval($offset % 3600 / 60));
        return 'GMT' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
    }

    function format_timezone_name($name)
    {
        $name = str_replace('/', ', ', $name);
        $name = str_replace('_', ' ', $name);
        $name = str_replace('St ', 'St. ', $name);
        return $name;
    }

    /**
     * Validate password complexity
     */
    public function validate_password($password)
    {
        if (strlen($password) < 8) {
            return "Password must be at least 8 characters long.";
        }
        if (!preg_match("#[0-9]+#", $password)) {
            return "Password must include at least one number.";
        }
        if (!preg_match("#[a-zA-Z]+#", $password)) {
            return "Password must include at least one letter.";
        }
        // Check for special characters
        if (!preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:\"\<\>,\.\?\\\]/', $password)) {
            return "Password must include at least one special character.";
        }
        return true;
    }
}

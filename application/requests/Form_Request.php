<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laravel-style Form Request for CodeIgniter 3.
 *
 * Encapsulates the validation rules (and optional custom messages) for a single
 * write action, wrapping CI's form_validation library. Usage in a controller:
 *
 *     require_once APPPATH . 'requests/Contact_Request.php';
 *     $req = new Contact_Request();
 *     if (!$req->validate()) {
 *         set_alert('error', $req->first_error());
 *         redirect($req->back());          // back to the form with old input + errors
 *     }
 *     $data = $req->validated();           // clean, whitelisted input
 *
 * On failure, the errors and the old POST input are flashed to the session
 * (`request_errors`, `old_input`) so the redirected-to view can repopulate.
 */
abstract class Form_Request
{
    /** @var CI_Controller */
    protected $CI;

    /** @var array<string,string> field => error message */
    protected $errors = [];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('form_validation');
        $this->CI->load->helper(['form', 'url']);
    }

    /**
     * CI form_validation rule config: array of ['field','label','rules'].
     * @return array<int,array<string,string>>
     */
    abstract public function rules(): array;

    /**
     * Optional custom messages, keyed by rule name (e.g. ['required' => '...']).
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * URL to redirect back to on validation failure. Defaults to the referring
     * page or the site root; override per-request for a specific form URL.
     */
    public function back(): string
    {
        $ref = (string) $this->CI->input->server('HTTP_REFERER');
        return $ref !== '' ? $ref : base_url();
    }

    /** Run validation. True on success; on failure flashes errors + old input. */
    public function validate(): bool
    {
        $this->CI->form_validation->reset_validation();
        $this->CI->form_validation->set_rules($this->rules());
        foreach ($this->messages() as $rule => $message) {
            $this->CI->form_validation->set_message($rule, $message);
        }

        if ($this->CI->form_validation->run() === true) {
            return true;
        }

        $this->errors = $this->CI->form_validation->error_array();
        $this->CI->session->set_flashdata('request_errors', $this->errors);
        $this->CI->session->set_flashdata('old_input', (array) $this->CI->input->post());
        return false;
    }

    /** All validation errors, keyed by field. @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** First error message (handy for a single set_alert). */
    public function first_error(): string
    {
        return $this->errors ? (string) reset($this->errors) : '';
    }

    /**
     * Whitelisted, trimmed input for exactly the fields declared in rules().
     * @return array<string,mixed>
     */
    public function validated(): array
    {
        $data = [];
        foreach ($this->rules() as $rule) {
            if (empty($rule['field'])) {
                continue;
            }
            $field = $rule['field'];
            $data[$field] = $this->CI->input->post($field);
        }
        return $data;
    }
}

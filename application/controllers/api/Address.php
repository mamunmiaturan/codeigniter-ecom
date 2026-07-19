<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Customer saved-address API (auth required). Every operation is scoped to the
 * authenticated customer via the model, so a customer can only touch their own
 * addresses. Each response returns the full current address list.
 *
 *  GET  /api/v1/customer/addresses            -> list
 *  POST /api/v1/customer/addresses            -> create {label,name,phone,division,district,area,address,landmark,postcode,is_default}
 *  POST /api/v1/customer/addresses/update     -> {id, ...}
 *  POST /api/v1/customer/addresses/delete     -> {id}
 *  POST /api/v1/customer/addresses/default    -> {id}
 */
class Address extends Api_Controller
{
    protected $require_auth = true;
    private $_body_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('customer_address_model');
    }

    public function index()
    {
        $this->_respond();
    }

    public function create()
    {
        $b = $this->_json_body();
        if ($err = $this->_validate($b)) {
            $this->fail($err, 422);
            return;
        }
        $this->customer_address_model->create($this->_uid(), $this->_collect($b));
        $this->_respond(201);
    }

    public function update()
    {
        $b = $this->_json_body();
        $id = (int) ($b['id'] ?? 0);
        if ($id <= 0) {
            $this->fail('id is required', 422);
            return;
        }
        if ($err = $this->_validate($b)) {
            $this->fail($err, 422);
            return;
        }
        if (!$this->customer_address_model->update_address($id, $this->_uid(), $this->_collect($b))) {
            $this->fail('Address not found', 404);
            return;
        }
        $this->_respond();
    }

    public function remove()
    {
        $b = $this->_json_body();
        if (!$this->customer_address_model->delete_address((int) ($b['id'] ?? 0), $this->_uid())) {
            $this->fail('Address not found', 404);
            return;
        }
        $this->_respond();
    }

    public function set_default()
    {
        $b = $this->_json_body();
        if (!$this->customer_address_model->set_default((int) ($b['id'] ?? 0), $this->_uid())) {
            $this->fail('Address not found', 404);
            return;
        }
        $this->_respond();
    }

    // ------------------------------------------------------------------

    private function _uid()
    {
        return (int) $this->auth_user['id'];
    }

    private function _respond($status = 200)
    {
        $rows = $this->customer_address_model->get_all($this->_uid());
        $this->ok(['items' => array_map([$this, '_shape'], $rows)], $status);
    }

    private function _shape($a)
    {
        return [
            'id'         => (int) $a['id'],
            'label'      => $a['label'],
            'name'       => $a['name'],
            'phone'      => $a['phone'],
            'division'   => $a['division'],
            'district'   => $a['district'],
            'area'       => $a['area'],
            'address'    => $a['address'],
            'landmark'   => $a['landmark'],
            'postcode'   => $a['postcode'],
            'is_default' => (bool) $a['is_default'],
        ];
    }

    private function _validate($b)
    {
        if (trim((string) ($b['name'] ?? '')) === '')    return 'name is required';
        if (trim((string) ($b['phone'] ?? '')) === '')   return 'phone is required';
        if (trim((string) ($b['address'] ?? '')) === '') return 'address is required';
        return null;
    }

    private function _collect($b)
    {
        return [
            'label'      => $this->_s($b, 'label'),
            'name'       => $this->_s($b, 'name'),
            'phone'      => $this->_s($b, 'phone'),
            'division'   => $this->_s($b, 'division'),
            'district'   => $this->_s($b, 'district'),
            'area'       => $this->_s($b, 'area'),
            'address'    => $this->_s($b, 'address'),
            'landmark'   => $this->_s($b, 'landmark'),
            'postcode'   => $this->_s($b, 'postcode'),
            'is_default' => !empty($b['is_default']) ? 1 : 0,
        ];
    }

    private function _s($b, $k)
    {
        $v = trim((string) ($b[$k] ?? ''));
        return $v === '' ? null : $v;
    }

    private function _json_body()
    {
        if ($this->_body_cache !== null) {
            return $this->_body_cache;
        }
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        $this->_body_cache = is_array($decoded) ? $decoded : ($this->input->post() ?: []);
        return $this->_body_cache;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Queuedashboard extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('queue_model');
    }

    public function index()
    {
        $this->data['title'] = 'Queue Dashboard';
        $this->data['main_menu'] = 'queue_dashboard';
        $this->data['sub_page'] = 'queue/dashboard';
        
        $this->data['stats'] = $this->queue_model->get_stats();
        $this->data['pending_jobs'] = $this->queue_model->get_pending_jobs();
        $this->data['failed_jobs'] = $this->queue_model->get_failed_jobs();
        
        $this->load->view('layout/index', $this->data);
    }

    public function retry_failed($id)
    {
        $this->queue_model->retry_failed_job((int)$id);
        set_alert('success', 'Job pushed back to queue for retry.');
        redirect(base_url('queuedashboard'));
    }

    public function clear_failed()
    {
        $this->queue_model->clear_failed_jobs();
        set_alert('success', 'Failed jobs cleared.');
        redirect(base_url('queuedashboard'));
    }
}

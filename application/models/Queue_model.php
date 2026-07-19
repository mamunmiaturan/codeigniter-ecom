<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Queue Model - Handles database operations for background jobs queue
 */
class Queue_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    const MAX_ATTEMPTS = 3;

    /**
     * Get next available job from the queue (not yet exhausted its retries).
     */
    public function get_next_job(string $queue_name)
    {
        return $this->db
            ->order_by('priority', 'DESC')
            ->order_by('id', 'ASC')
            ->where('queue', $queue_name)
            ->where('attempts <', self::MAX_ATTEMPTS)
            ->limit(1)
            ->get('jobs')
            ->row();
    }

    /**
     * Reserve/lock a job by incrementing its attempts counter.
     */
    public function reserve_job(int $job_id, int $attempts)
    {
        $this->db->where('id', $job_id);
        return $this->db->update('jobs', [
            'attempts'    => $attempts + 1,
            'reserved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete a successfully completed job.
     */
    public function delete_job(int $job_id)
    {
        $this->db->where('id', $job_id);
        return $this->db->delete('jobs');
    }

    /**
     * Move a permanently failed job to the dead-letter table.
     * The job is removed from `jobs` so it stops blocking the queue.
     *
     * @param object $job       The original job row
     * @param string $exception Human-readable failure reason / output
     */
    public function bury_job($job, string $exception): void
    {
        $this->db->trans_start();

        $this->db->insert('failed_jobs', [
            'queue'     => $job->queue,
            'payload'   => $job->payload,
            'exception' => $exception,
            'attempts'  => $job->attempts,
            'failed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->where('id', $job->id)->delete('jobs');

        $this->db->trans_complete();
    }

    public function get_stats()
    {
        return [
            'pending' => $this->db->count_all_results('jobs'),
            'failed'  => $this->db->count_all_results('failed_jobs'),
        ];
    }

    public function get_pending_jobs()
    {
        return $this->db->get('jobs')->result();
    }

    public function get_failed_jobs()
    {
        return $this->db->get('failed_jobs')->result();
    }

    public function retry_failed_job(int $job_id)
    {
        $this->db->trans_start();
        $job = $this->db->where('id', $job_id)->get('failed_jobs')->row();
        if ($job) {
            $this->db->insert('jobs', [
                'queue'        => $job->queue,
                'payload'      => $job->payload,
                'attempts'     => 0,
                'available_at' => date('Y-m-d H:i:s'),
                'created_at'   => date('Y-m-d H:i:s')
            ]);
            $this->db->where('id', $job_id)->delete('failed_jobs');
        }
        $this->db->trans_complete();
    }

    public function clear_failed_jobs()
    {
        $this->db->empty_table('failed_jobs');
    }
}

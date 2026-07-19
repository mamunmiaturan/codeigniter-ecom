<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">Queue Dashboard</h4>
            </div>
            <div class="panel-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="well text-center">
                            <h3><?= $stats['pending'] ?></h3>
                            <p>Pending Jobs</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="well text-center">
                            <h3><?= $stats['failed'] ?></h3>
                            <p>Failed Jobs</p>
                        </div>
                    </div>
                </div>

                <h4>Failed Jobs</h4>
                <?php if (!empty($failed_jobs)): ?>
                    <a href="<?= base_url('queuedashboard/clear_failed') ?>" class="btn btn-danger btn-sm mb-2" onclick="return confirm('Clear all failed jobs?')">Clear All Failed Jobs</a>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Queue</th>
                                <th>Payload</th>
                                <th>Exception</th>
                                <th>Failed At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($failed_jobs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No failed jobs</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($failed_jobs as $job): ?>
                                    <tr>
                                        <td><?= $job->id ?></td>
                                        <td><?= html_escape($job->queue) ?></td>
                                        <td><pre><?= html_escape($job->payload) ?></pre></td>
                                        <td><pre><?= html_escape($job->exception) ?></pre></td>
                                        <td><?= html_escape($job->failed_at) ?></td>
                                        <td>
                                            <a href="<?= base_url('queuedashboard/retry_failed/'.$job->id) ?>" class="btn btn-primary btn-xs">Retry</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h4>Pending Jobs</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Queue</th>
                                <th>Payload</th>
                                <th>Attempts</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pending_jobs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No pending jobs</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pending_jobs as $job): ?>
                                    <tr>
                                        <td><?= $job->id ?></td>
                                        <td><?= html_escape($job->queue) ?></td>
                                        <td><pre><?= html_escape($job->payload) ?></pre></td>
                                        <td><?= html_escape($job->attempts) ?></td>
                                        <td><?= html_escape($job->created_at) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

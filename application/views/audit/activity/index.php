<?php
ob_start();
?>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label">
            <?= translate('User'); ?>
        </label>
        <select name="user_name" class="form-control" data-plugin-selectTwo data-width="100%">
            <option value="">
                <?= translate('all'); ?>
            </option>
            <?php foreach ($all_users as $u): ?>
                <option value="<?= html_escape($u); ?>" <?= ($filters['f_user'] ?? '') === $u ? 'selected' : ''; ?>>
                    <?= html_escape($u); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('date_from'); ?></label>
        <input type="date" name="date_from" class="form-control"
            value="<?= html_escape($filters['f_date_from'] ?? ''); ?>">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('date_to'); ?></label>
        <input type="date" name="date_to" class="form-control" value="<?= html_escape($filters['f_date_to'] ?? ''); ?>">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('Action'); ?></label>
        <select name="action" class="form-control" data-plugin-selectTwo data-width="100%">
            <option value=""><?= translate('all'); ?></option>
            <option value="create" <?= ($filters['f_action'] ?? '') === 'create' ? 'selected' : ''; ?>>CREATE</option>
            <option value="update" <?= ($filters['f_action'] ?? '') === 'update' ? 'selected' : ''; ?>>UPDATE</option>
            <option value="delete" <?= ($filters['f_action'] ?? '') === 'delete' ? 'selected' : ''; ?>>DELETE</option>
        </select>
    </div>
</div>

<?php
$filter_fields = ob_get_clean();
$this->load->view('layout/filter', [
    'filter_panel_id' => 'activitylog',
    'exclude' => [],
    'filter_fields' => $filter_fields,
]);
?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6 col-xs-6">
                        <h4 class="panel-title" style="line-height: 26px;">
                            <i class="fa fa-history"></i> <?php echo translate('System_activity_logs'); ?>
                        </h4>
                    </div>
                    <div class="col-md-6 col-xs-6 text-right">
                        <?php if (is_superadmin_loggedin()): ?>
                            <!-- POST, not a link: this wipes the activity log. As a GET it
                                 was triggerable by any crawler or prefetcher. The CSRF field
                                 is injected into method="post" forms by MY_Controller::_output(). -->
                            <form action="<?php echo base_url('activity-logs/clear'); ?>" method="post"
                                  style="display:inline-block;margin:0"
                                  onsubmit="return confirm('<?php echo translate('are_you_sure') ?: 'Are you sure?'; ?>');">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i> <?php echo translate('clear') ?: 'Clear logs'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <div class="export_title"><?php echo translate('System Activity Logs'); ?></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo translate('User'); ?></th>
                                <th><?php echo translate('Action'); ?></th>
                                <th><?php echo translate('Table'); ?></th>
                                <th><?php echo translate('Record ID'); ?></th>
                                <th><?php echo translate('IP Address'); ?></th>
                                <th><?php echo translate('Time'); ?></th>
                                <th><?php echo translate('Data Diff'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php
                                $count = 1;
                                foreach ($logs as $row):
                                    $label = 'default';
                                    if ($row['action'] == 'create')
                                        $label = 'success';
                                    if ($row['action'] == 'update')
                                        $label = 'info';
                                    if ($row['action'] == 'delete')
                                        $label = 'danger';

                                    $table_colors = ['primary', 'success', 'info', 'warning', 'danger', 'default', 'dark'];
                                    $tbl_hash = abs(crc32($row['table_name'] ?? ''));
                                    $table_label = $table_colors[$tbl_hash % 7];
                                    ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td style="white-space: nowrap;">
                                            <strong><?php echo html_escape($row['user_name'] ?? 'System'); ?></strong></td>
                                        <td>
                                            <span class="label label-<?php echo $label; ?>"
                                                style="min-width: 60px; display: inline-block; text-align: center;">
                                                <?php echo strtoupper($row['action']); ?>
                                            </span>
                                        </td>
                                        <td><span
                                                class="label label-<?php echo $table_label; ?>"><?php echo html_escape($row['table_name']); ?></span>
                                        </td>
                                        <td><span class="badge badge-primary"><?php echo $row['row_id']; ?></span></td>
                                        <td><small><?php echo $row['ip_address']; ?></small></td>
                                        <td style="white-space: nowrap;">
                                            <?= _d($row['created_at']) . ' ' . time_ago($row['created_at']) ?>
                                        </td>
                                        <td>
                                            <?php
                                            /**
                                             * Normalize old/new payload into a plain value safe for JSON+HTML embedding.
                                             * Handles: null (legacy rows), JSON string (current writer), or already-decoded array.
                                             */
                                            $normalize_payload = static function ($raw) {
                                                if ($raw === null || $raw === '') {
                                                    return null;
                                                }
                                                if (is_string($raw)) {
                                                    $decoded = json_decode($raw, true);
                                                    return ($decoded !== null) ? $decoded : $raw;
                                                }
                                                return $raw;
                                            };
                                            $old_val = $normalize_payload($row['old_data'] ?? null);
                                            $new_val = $normalize_payload($row['new_data'] ?? null);
                                            ?>
                                            <?php if ($old_val !== null || $new_val !== null): ?>
                                                <button class="btn btn-default btn-xs" onclick="showDiff(this)"
                                                    data-old="<?php echo htmlspecialchars(json_encode($old_val), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-new="<?php echo htmlspecialchars(json_encode($new_val), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-search-plus"></i> Details
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Diff Modal -->
<div class="modal fade" id="diffModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">Data Change Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><span class="label label-danger">Previous State</span></h5>
                        <pre id="old-data" style="background: #fdf2f2; color: #a94442;"></pre>
                    </div>
                    <div class="col-md-6">
                        <h5><span class="label label-success">New State</span></h5>
                        <pre id="new-data" style="background: #f2fdf2; color: #3c763d;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url('assets/backend/js/app/activity-diff.js'); ?>"></script>
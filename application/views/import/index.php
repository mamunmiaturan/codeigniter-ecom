<?php
ob_start();
?>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('uploaded_by'); ?></label>
        <select name="uploader" class="form-control" data-plugin-selectTwo data-width="100%">
            <option value=""><?= translate('all'); ?></option>
            <?php foreach ($all_uploaders as $u): ?>
                <option value="<?= html_escape($u); ?>" <?= ($filters['f_uploader'] ?? '') === $u ? 'selected' : ''; ?>><?= html_escape($u); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('date_from'); ?></label>
        <input type="date" name="date_from" class="form-control" value="<?= html_escape($filters['f_date_from'] ?? ''); ?>">
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
        <label class="control-label"><?= translate('status'); ?></label>
        <select name="status" class="form-control" data-plugin-selectTwo data-width="100%">
            <option value=""><?= translate('all'); ?></option>
            <option value="Pending"    <?= ($filters['f_status'] ?? '') === 'Pending'    ? 'selected' : ''; ?>>Pending</option>
            <option value="Processing" <?= ($filters['f_status'] ?? '') === 'Processing' ? 'selected' : ''; ?>>Processing</option>
            <option value="Completed"  <?= ($filters['f_status'] ?? '') === 'Completed'  ? 'selected' : ''; ?>>Completed</option>
            <option value="Failed"     <?= ($filters['f_status'] ?? '') === 'Failed'     ? 'selected' : ''; ?>>Failed</option>
        </select>
    </div>
</div>
<?php
$filter_fields = ob_get_clean();
$this->load->view('layout/filter', [
    'filter_panel_id' => 'import',
    'filter_fields'   => $filter_fields,
]);
?>

<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-file-import"></i> <?php echo translate('imports') . " " . translate('list'); ?>
		</h4>
	</header>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered table-hover table-condensed table-default" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th><?php echo translate('sl'); ?></th>
						<th style="white-space: nowrap;"><?php echo translate('original_filename'); ?></th>
						<th style="white-space: nowrap;"><?php echo translate('uploaded_by'); ?></th>
						<th><?php echo translate('rows'); ?> (T/S/F)</th>
						<th><?php echo translate('date'); ?></th>
						<th><?php echo translate('status'); ?></th>
						<th><?php echo translate('error_message'); ?></th>
						<th><?php echo translate('action'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					if (!empty($imports)) {
						foreach ($imports as $row) {
							$status_badge = '';
							if ($row['status'] == 'Pending') {
								$status_badge = '<span class="label label-warning">' . translate('Pending') . '</span>';
							} elseif ($row['status'] == 'Processing') {
								$status_badge = '<span class="label label-primary">' . translate('Processing') . '</span>';
							} elseif ($row['status'] == 'Completed') {
								$status_badge = '<span class="label label-success">' . translate('Completed') . '</span>';
							} elseif ($row['status'] == 'Failed') {
								$status_badge = '<span class="label label-danger">' . translate('Failed') . '</span>';
							}
					?>
							<tr>
								<td><?php echo $i++; ?></td>
								<td><?php echo html_escape($row['original_filename']); ?></td>
								<td><?php echo html_escape($row['importer_name'] ?? 'System'); ?></td>
								<td style="white-space: nowrap;">
									<div style="line-height: 1.6;">
										<span class="label label-default" style="display: inline-block; min-width: 85px; text-align: left;">Total: <?php echo html_escape($row['total_rows']); ?></span><br>
										<span class="label label-success" style="display: inline-block; min-width: 85px; text-align: left; margin-top: 4px;">Success: <?php echo html_escape($row['success_rows']); ?></span><br>
										<span class="label label-danger" style="display: inline-block; min-width: 85px; text-align: left; margin-top: 4px;">Failed: <?php echo html_escape($row['failed_rows']); ?></span>
									</div>
								</td>
								<td><?php echo $status_badge; ?></td>
								<td>
									<?php if (!empty($row['error_message'])) { 
										$short_error = (strlen($row['error_message']) > 45) ? substr($row['error_message'], 0, 42) . '...' : $row['error_message'];
									?>
										<span class="text-danger clickable-error" style="font-size: 11px; cursor: pointer; text-decoration: underline;" onclick="showErrorModal(<?php echo htmlspecialchars(json_encode($row['error_message']), ENT_QUOTES, 'UTF-8'); ?>)" data-toggle="tooltip" title="Click to view details">
											<i class="fas fa-exclamation-triangle" style="margin-right: 3px;"></i><?php echo html_escape($short_error); ?>
										</span>
									<?php } else { ?>
										<span class="text-muted">-</span>
									<?php } ?>
								</td>
								<td style="white-space: nowrap;">
									<span><i class="far fa-calendar-alt text-primary"></i> <?php echo time_ago($row['created_at']); ?></span><br>
									<span class="text-muted" style="font-size: 11px;"><i class="far fa-clock"></i> <?php echo time_ago($row['created_at']); ?></span>
								</td>
								<td>
									<?php if ($row['status'] == 'Pending' && get_permission('user', 'is_add')) { ?>
										<?php echo form_open('import/approve/' . encrypt_id($row['id']), ['class' => 'inline-form', 'style' => 'display: inline-block;']); ?>
											<button type="submit" class="btn btn-primary btn-circle icon" data-toggle="tooltip" data-original-title="<?php echo translate('approve'); ?>">
												<i class="fas fa-check-circle"></i>
											</button>
										<?php echo form_close(); ?>
									<?php } ?>
									<?php if ($row['status'] == 'Failed' && get_permission('user', 'is_add')) { ?>
										<?php echo form_open('import/retry/' . encrypt_id($row['id']), ['class' => 'inline-form', 'style' => 'display: inline-block;']); ?>
											<button type="submit" class="btn btn-warning btn-circle icon" data-toggle="tooltip" data-original-title="<?php echo translate('retry'); ?>">
												<i class="fas fa-redo"></i>
											</button>
										<?php echo form_close(); ?>
									<?php } ?>
									<a href="<?php echo base_url('import/download_file/' . $row['id']); ?>" class="btn btn-info btn-circle icon" data-toggle="tooltip" data-original-title="<?php echo translate('download'); ?>">
										<i class="fas fa-download"></i>
									</a>
								</td>
							</tr>
					<?php 
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
</section>

<!-- Import Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="errorModalLabel"><i class="fas fa-exclamation-triangle text-danger"></i> <?php echo translate('import_error_details'); ?></h4>
            </div>
            <div class="modal-body">
                <pre id="errorModalContent" style="white-space: pre-line; background: #f8f9fa; border: 1px solid #e2e8f0; padding: 15px; border-radius: 4px; font-family: inherit; font-size: 13px; color: #333; max-height: 400px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo translate('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
	function showErrorModal(errorText) {
		$('#errorModalContent').text(errorText);
		$('#errorModal').modal('show');
	}
</script>

<?php
ob_start();
?>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('search'); ?></label>
        <input type="text" name="search" class="form-control" placeholder="Title or message..." value="<?= html_escape($filters['f_search'] ?? ''); ?>">
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
            <option value="unread" <?= ($filters['f_status'] ?? '') === 'unread' ? 'selected' : ''; ?>>Unread</option>
            <option value="read"   <?= ($filters['f_status'] ?? '') === 'read'   ? 'selected' : ''; ?>>Read</option>
        </select>
    </div>
</div>	
<?php
$filter_fields = ob_get_clean();
$this->load->view('layout/filter', [
    'filter_panel_id' => 'notification',
    'filter_fields'   => $filter_fields,
]);
?>

<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-bell"></i> <?php echo translate('notifications'); ?>
		</h4>
	</header>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered table-hover table-condensed table-default" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th><?php echo translate('sl'); ?></th>
						<th><?php echo translate('user'); ?></th>
						<th><?php echo translate('title'); ?></th>
						<th><?php echo translate('message'); ?></th>
						<th><?php echo translate('status'); ?></th>
						<th><?php echo translate('date'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					if (!empty($notifications)) {
						foreach ($notifications as $row) {
							$status_badge = $row['is_read'] == 1 
								? '<span class="label label-default">' . translate('read') . '</span>' 
								: '<a href="' . base_url('notification/mark_single_as_read/' . $row['id']) . '" class="label label-danger mark-read-btn" data-id="' . $row['id'] . '" style="cursor:pointer; text-decoration:none;">' . translate('unread') . '</a>';
					?>
							<tr>
								<td><?php echo $i++; ?></td>
								<td><?php echo html_escape($row['user_name'] ?? 'System'); ?></td>
								<td><strong><?php echo html_escape($row['title']); ?></strong></td>
								<td><?php echo html_escape($row['message']); ?></td>
								<td><?php echo $status_badge; ?></td>
								<td><?php echo time_ago($row['created_at']); ?></td>
							</tr>
					<?php 
						}
					} else {
					?>
						<tr>
							<td colspan="6" class="text-center">
								<span class="text-muted"><?php echo translate('no_notifications_found'); ?></span>
							</td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
</section>

<script type="text/javascript">
	$(document).ready(function () {
		// Handle marking a single notification as read via AJAX
		$(document).on('click', '.mark-read-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var url = $btn.attr('href');
			
			$.ajax({
				url: url,
				method: 'GET',
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						$btn.replaceWith('<span class="label label-default">' + "<?php echo translate('read'); ?>" + '</span>');
						if (typeof fetchNotifications === 'function') {
							fetchNotifications();
						}
					}
				}
			});
		});
	});
</script>

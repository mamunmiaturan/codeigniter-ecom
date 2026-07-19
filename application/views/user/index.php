<section class="panel app-filter-panel mb-md">
	<header class="panel-heading" id="toggle-filter-btn" style="cursor: pointer; user-select: none;">
		<h4 class="panel-title" style="display:flex;align-items:center;justify-content:space-between;">
			<span><i class="fas fa-filter"></i> <?php echo translate('filter'); ?></span>
			<i class="fas fa-chevron-down toggle-icon" style="transition: transform 0.2s ease;"></i>
		</h4>
	</header>
	<div class="panel-body" id="filter-collapse-body" style="display: none;">
		<div class="row">
			<div class="col-md-3">
				<div class="form-group">
					<label class="control-label"><?php echo translate('role'); ?></label>
					<select id="filter_role" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
						<?php foreach ($roles as $role): ?>
							<option value="<?php echo $role->id; ?>" <?php echo ($role->id == $act_role ? 'selected' : ''); ?>>
								<?php echo html_escape($role->name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label class="control-label"><?php echo translate('gender'); ?></label>
					<select id="filter_gender" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
						<option value=""><?php echo translate('all'); ?></option>
						<?php
						$gender_array = $this->app_lib->get_gender();
						foreach ($gender_array as $key => $value) {
							echo '<option value="' . $key . '">' . $value . '</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label class="control-label"><?php echo translate('blood_group'); ?></label>
					<select id="filter_blood_group" class="form-control" data-plugin-selectTwo data-width="100%">
						<option value=""><?php echo translate('all'); ?></option>
						<?php
						$blood_groups = $this->app_lib->get_blood_group();
						foreach ($blood_groups as $bg) {
							echo '<option value="' . $bg . '">' . $bg . '</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<select id="filter_status" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
						<option value=""><?php echo translate('all'); ?></option>
						<option value="Active"><?php echo translate('active'); ?></option>
						<option value="Inactive"><?php echo translate('inactive'); ?></option>
					</select>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<?php foreach ($roles as $role): ?>
				<li class="role-tab-item <?php if ($role->id == $act_role) echo 'active'; ?>">
					<a href="#" class="role-tab" data-id="<?php echo (int) $role->id; ?>">
						<i class="far fa-user-circle"></i>
						<span class="hidden-xs"> <?php echo html_escape($role->name); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
			<?php if (get_permission('user', 'is_add')) { ?>
				<li class="pull-right">
					<button class="btn btn-default btn-xs" data-toggle="modal" data-target="#importUsersModal" style="margin-top: 4px; margin-right: 15px;">
						<i class="fas fa-file-import"></i> <?php echo translate('import_users'); ?>
					</button>
				</li>
			<?php } ?>
		</ul>
		<input type="hidden" id="active_role_id" value="<?php echo (int) $act_role; ?>">
		<div class="tab-content">
			<div class="tab-pane box active">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="user-server-table">
					<thead>
						<tr>
							<th><?php echo translate('sl'); ?></th>
							<th><?php echo translate('photo'); ?></th>
							<th><?php echo translate('name'); ?></th>
							<th><?php echo translate('user_id'); ?></th>
							<th><?php echo translate('email'); ?></th>
							<th><?php echo translate('mobile_no'); ?></th>
							<th><?php echo translate('created_by'); ?></th>
							<th><?php echo translate('action'); ?></th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</section>

<!-- Import Users Modal -->
<div class="modal fade" id="importUsersModal" tabindex="-1" role="dialog" aria-labelledby="importUsersModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form action="<?php echo base_url('import/import_csv'); ?>" method="post" enctype="multipart/form-data">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title" id="importUsersModalLabel"><i class="fas fa-file-import"></i> <?php echo translate('import_users'); ?></h4>
				</div>
				<div class="modal-body">
					<div class="alert alert-info">
						<p><strong><?php echo translate('instructions'); ?>:</strong></p>
						<ul>
							<li>Upload CSV files only.</li>
							<li>Required Columns: Name, Email, Password.</li>
							<li>Optional Columns: Mobile, Gender, Blood_Group, Role_ID (e.g. 2).</li>
						</ul>
						<p style="margin-top: 10px;">
							<a href="<?php echo base_url('import/download_sample_csv'); ?>" class="btn btn-xs btn-primary">
								<i class="fas fa-download"></i> <?php echo translate('download_sample_csv'); ?>
							</a>
						</p>
					</div>
					<div class="form-group">
						<label class="control-label"><?php echo translate('select_csv_file'); ?> <span class="required">*</span></label>
						<input type="file" name="csv_file" class="form-control" accept=".csv" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo translate('close'); ?></button>
					<button type="submit" class="btn btn-primary"><?php echo translate('import'); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
		var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
		var csrfData = {};
		var activeRoleId = String(<?php echo (int) $act_role; ?>);

		function setActiveRole(roleId) {
			activeRoleId = String(roleId);
			$('#active_role_id').val(activeRoleId);
		}

		function syncFilterRoleSelect(roleId) {
			var $filter = $('#filter_role');
			$filter.val(roleId);
			if ($filter.data('select2')) {
				$filter.trigger('change.select2');
			}
		}

		if (activeRoleId) {
			syncFilterRoleSelect(activeRoleId);
		}

		var table;
		function reloadUserTable() {
			table.ajax.reload(null, true);
		}

		// Initialize server-side DataTable
		table = $('#user-server-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[2, "asc"]], // Default order by name ASC
			"pageLength": 25,
			"autoWidth": false,
			"deferRender": true,
			"columns": [
				{ "data": 0, "name": "sl" },
				{ "data": 1, "name": "photo" },
				{ "data": 2, "name": "name" },
				{ "data": 3, "name": "user_id" },
				{ "data": 4, "name": "email" },
				{ "data": 5, "name": "mobile_no" },
				{ "data": 6, "name": "created_by" },
				{ "data": 7, "name": "action" }
			],
			"dom": '<"row align-items-center mb-3"<"col-md-4 d-flex align-items-center"l><"col-md-4 text-center"B><"col-md-4 d-flex justify-content-md-end"f>><"table-responsive"t><"row mt-3"<"col-md-6"i><"col-md-6 d-flex justify-content-md-end"p>>',
			"lengthMenu": [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, "All"]
			],
			"buttons": [
				{
					extend: "copyHtml5",
					text: '<i class="far fa-copy"></i>',
					className: "btn btn-secondary btn-sm",
				},
				{
					extend: "excelHtml5",
					text: '<i class="far fa-file-excel"></i>',
					className: "btn btn-secondary btn-sm",
				},
				{
					extend: "csvHtml5",
					text: '<i class="far fa-file-alt"></i>',
					className: "btn btn-secondary btn-sm",
				},
				{
					extend: "print",
					text: '<i class="fa fa-print"></i>',
					className: "btn btn-secondary btn-sm",
				},
				{
					extend: "colvis",
					text: '<i class="fas fa-columns"></i>',
					className: "btn btn-secondary btn-sm",
				}
			],
			"ajax": {
				"url": "<?php echo base_url('user/get_users_server_side'); ?>",
				"type": "POST",
				"data": function(d) {
					d[csrfName] = csrfHash;
					d.active_role_id = parseInt($('#active_role_id').val() || activeRoleId, 10);
					d.gender = $('#filter_gender').val();
					d.blood_group = $('#filter_blood_group').val();
					d.status = $('#filter_status').val();
				},
				"dataSrc": function(json) {
					if (json.csrf) {
						csrfHash = json.csrf[csrfName];
						csrfData = json.csrf;
						$.ajaxSetup({ data: csrfData });
					} else if (json.csrfHash) {
						csrfHash = json.csrfHash;
						csrfData[csrfName] = csrfHash;
						$.ajaxSetup({ data: csrfData });
					}
					return json.data;
				}
			},
			"columnDefs": [
				{ "targets": [0, 1, 7], "orderable": false },
				{ "targets": [1, 7], "className": "center text-center" }
			],
			"drawCallback": function(settings) {
				$('[data-toggle="tooltip"]').tooltip();
			},
			"language": {
				"search": "_INPUT_",
				"searchPlaceholder": "Search...",
				"lengthMenu": "Show _MENU_ entries",
				"info": "Showing _START_ to _END_ of _TOTAL_ entries",
				"paginate": {
					"previous": "Prev",
					"next": "Next"
				}
			}
		});

		// Switch status change via AJAX button
		$(document).on('click', '.btn-status-toggle', function () {
			var btn = $(this);
			var currentActive = btn.attr('data-active'); // "1" or "0"
			var nextState = (currentActive == "1") ? "false" : "true";
			var user_id = btn.data('id');
			
			$.ajax({
				type: 'POST',
				url: "<?php echo base_url('user/status'); ?>",
				data: { user_id: user_id, status: nextState },
				dataType: 'html',
				success: function (data) {
					// Toggle the data-active attribute
					var isNowActive = (nextState === "true");
					btn.attr('data-active', isNowActive ? "1" : "0");
					
					// Update tooltip
					var tooltipText = isNowActive ? "<?php echo translate('deactivate'); ?>" : "<?php echo translate('activate'); ?>";
					btn.attr('data-original-title', tooltipText).tooltip('hide').attr('title', tooltipText);
					
					// Update icon and colors
					var icon = btn.find('i');
					if (isNowActive) {
						icon.removeClass('fa-toggle-off text-danger').addClass('fa-toggle-on text-success');
					} else {
						icon.removeClass('fa-toggle-on text-success').addClass('fa-toggle-off text-danger');
					}
					
					swal({
						type: 'success',
						title: "Successfully",
						text: data,
						showCloseButton: true,
						focusConfirm: false,
						buttonsStyling: false,
						confirmButtonClass: 'btn btn-default swal2-btn-default',
						footer: '*Note : You can undo this action at any time'
					});
				}
			});
		});

		// Check filter collapse state from localStorage cache
		var isFilterCollapsed = localStorage.getItem('user_filter_collapsed');
		if (isFilterCollapsed === 'false') {
			$('#filter-collapse-body').show();
			$('#toggle-filter-btn').find('.toggle-icon').css('transform', 'rotate(180deg)');
		} else {
			$('#filter-collapse-body').hide();
			$('#toggle-filter-btn').find('.toggle-icon').css('transform', 'rotate(0deg)');
		}

		// Toggle click handler for collapsible filter panel
		$(document).on('click', '#toggle-filter-btn', function() {
			var body = $('#filter-collapse-body');
			var icon = $(this).find('.toggle-icon');
			
			if (body.is(':visible')) {
				body.slideUp(200);
				icon.css('transform', 'rotate(0deg)');
				localStorage.setItem('user_filter_collapsed', 'true');
			} else {
				body.slideDown(200);
				icon.css('transform', 'rotate(180deg)');
				localStorage.setItem('user_filter_collapsed', 'false');
			}
		});

		$(document).on('change', '#filter_role', function() {
			var roleId = $(this).val() || activeRoleId;
			setActiveRole(roleId);
			$('.nav-tabs li.role-tab-item').removeClass('active');
			$('.role-tab[data-id="' + roleId + '"]').parent().addClass('active');
			reloadUserTable();
		});

		$(document).on('change', '#filter_gender, #filter_blood_group, #filter_status', function() {
			reloadUserTable();
		});

		$(document).on('click', '.role-tab', function (e) {
			e.preventDefault();
			var roleId = $(this).attr('data-id');
			setActiveRole(roleId);
			$('.nav-tabs li.role-tab-item').removeClass('active');
			$(this).parent().addClass('active');
			syncFilterRoleSelect(roleId);
			reloadUserTable();
		});
	});
</script>
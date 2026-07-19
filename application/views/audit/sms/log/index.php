<?php
ob_start();
?>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('date_from'); ?></label>
        <input type="text" name="date_from" class="form-control" value="<?= html_escape($date_from ?? ''); ?>"
            data-plugin-datepicker data-plugin-options='{"format":"yyyy-mm-dd","autoclose":true}' placeholder="YYYY-MM-DD">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('date_to'); ?></label>
        <input type="text" name="date_to" class="form-control" value="<?= html_escape($date_to ?? ''); ?>"
            data-plugin-datepicker data-plugin-options='{"format":"yyyy-mm-dd","autoclose":true}' placeholder="YYYY-MM-DD">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-none">
        <label class="control-label"><?= translate('status'); ?></label>
        <select name="status" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
            <option value=""><?= translate('all'); ?></option>
            <option value="Success" <?= ($status ?? '') === 'Success' ? 'selected' : ''; ?>><?= translate('success'); ?></option>
            <option value="Pending" <?= ($status ?? '') === 'Pending' ? 'selected' : ''; ?>><?= translate('pending'); ?></option>
            <option value="Failed"  <?= ($status ?? '') === 'Failed'  ? 'selected' : ''; ?>><?= translate('failed'); ?></option>
        </select>
    </div>
</div>
<?php
$filter_fields = ob_get_clean();
$this->load->view('layout/filter', [
    'filter_panel_id' => 'sms_log',
    'exclude'         => ['date_from', 'date_to', 'status'],
    'filter_fields'   => $filter_fields,
]);
?>

<style>.btn-view-msg { cursor: pointer; color: #337ab7; }</style>

<!-- Message Detail Modal -->
<div class="modal fade" id="msgModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-comment-alt"></i> <?php echo translate('message'); ?></h4>
            </div>
            <div class="modal-body">
                <p id="msgModalContent" style="white-space: pre-wrap; word-break: break-word; margin: 0;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo translate('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-list"></i> <?php echo translate('sms_logs'); ?>
                </h4>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed mb-none" id="sms-server-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo translate('recipient'); ?></th>
                                <th><?php echo translate('mobile_no'); ?></th>
                                <th><?php echo translate('message'); ?></th>
                                <th><?php echo translate('status'); ?></th>
                                <th><?php echo translate('date'); ?></th>
                                <th><?php echo translate('sent_by'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		$(document).on('click', '.btn-view-msg', function () {
			$('#msgModalContent').text($(this).data('msg'));
			$('#msgModal').modal('show');
		});

		var csrfName     = '<?php echo $this->security->get_csrf_token_name(); ?>';
		var csrfHash     = '<?php echo $this->security->get_csrf_hash(); ?>';
		var filterDateFrom   = '<?= html_escape($date_from ?? ''); ?>';
		var filterDateTo     = '<?= html_escape($date_to   ?? ''); ?>';
		var filterStatus     = '<?= html_escape($status    ?? ''); ?>';

		var table = $('#sms-server-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[5, "desc"]], // Default order by date DESC
			"pageLength": 25,
			"autoWidth": false,
			"responsive": true,
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
				"url": "<?php echo base_url('sms/get_sms_logs_server_side'); ?>",
				"type": "POST",
				"data": function(d) {
					d[csrfName]       = csrfHash;
					d.date_from       = filterDateFrom;
					d.date_to         = filterDateTo;
					d.filter_status   = filterStatus;
				},
				"dataSrc": function(json) {
					if (json.error) {
						console.error('SMS logs DataTables error:', json.error);
						return [];
					}
					if (json.csrfHash) {
						csrfHash = json.csrfHash;
					}
					return json.data || [];
				},
				"error": function(xhr) {
					console.error('SMS logs Ajax error:', xhr.status, xhr.responseText);
				}
			},
			"columnDefs": [
				{ "targets": [0, 4], "orderable": false },
				{ "targets": [3], "orderable": false, "className": "msg-cell" }
			],
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
	});
</script>

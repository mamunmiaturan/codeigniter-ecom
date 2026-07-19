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
            <option value="success"  <?= ($status ?? '') === 'success'  ? 'selected' : ''; ?>><?= translate('success'); ?></option>
            <option value="failed"   <?= ($status ?? '') === 'failed'   ? 'selected' : ''; ?>><?= translate('failed'); ?></option>
        </select>
    </div>
</div>
<?php
$filter_fields = ob_get_clean();
$this->load->view('layout/filter', [
    'filter_panel_id' => 'email_log',
    'exclude'         => ['date_from', 'date_to', 'status'],
    'filter_fields'   => $filter_fields,
]);
?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-12 col-xs-12 text-right">
                        <h4 class="panel-title" style="line-height: 26px;">
                            <i class="fas fa-envelope-open-text"></i> <?php echo translate('Email_Logs'); ?>
                        </h4>
                        <?php if (get_permission('email_log', 'is_delete') && audit_logs_view_unrestricted()): ?>
                            <!-- POST, not a link: this wipes the email log. As a GET it was
                                 triggerable by any crawler or prefetcher. The CSRF field is
                                 injected into method="post" forms by MY_Controller::_output(). -->
                            <form action="<?php echo base_url('email-logs/clear'); ?>" method="post"
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
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed mb-none" id="email-logs-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo translate('recipient'); ?></th>
                                <th><?php echo translate('subject'); ?></th>
                                <th><?php echo translate('status'); ?></th>
                                <th><?php echo translate('date'); ?></th>
                                <th><?php echo translate('details'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)): ?>
                                <?php $i = 1;
                                foreach ($logs as $row):
                                ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo html_escape($row['recipient']); ?></td>
                                        <?php $subj = $row['subject'] ?? '-'; $short_subj = mb_strlen($subj) > 60 ? mb_substr($subj, 0, 60) . '...' : $subj; ?>
                                        <td><span class="btn-view-msg" data-msg="<?php echo htmlspecialchars($subj, ENT_QUOTES, 'UTF-8'); ?>"><?php echo html_escape($short_subj); ?></span></td>
                                        <td><?php echo status_badge($row['status']); ?></td>
                                        <td><?php echo time_ago($row['time']); ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'failed'): ?>
                                                <button class="btn btn-default btn-xs btn-view-error"
                                                    data-error="<?php echo htmlspecialchars($row['error'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fa fa-eye"></i> <?php echo translate('view_error'); ?>
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center"><?php echo translate('no_records_found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<style>.btn-view-msg { cursor: pointer; color: #337ab7; }</style>

<!-- Message Detail Modal -->
<div class="modal fade" id="msgModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-envelope"></i> <?php echo translate('subject'); ?></h4>
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

<!-- Error Detail Modal -->
<div class="modal fade" id="emailErrorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-exclamation-circle text-danger"></i> <?php echo translate('view_error'); ?></h4>
            </div>
            <div class="modal-body">
                <pre id="emailErrorContent" style="white-space: pre-wrap; word-break: break-all; max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 12px; border-radius: 4px; font-size: 12px;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo translate('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(document).on('click', '.btn-view-msg', function () {
            $('#msgModalContent').text($(this).data('msg'));
            $('#msgModal').modal('show');
        });

        $(document).on('click', '.btn-view-error', function () {
            var error = $(this).data('error');
            $('#emailErrorContent').text(error);
            $('#emailErrorModal').modal('show');
        });

        $('#email-logs-table').DataTable({
            "ordering": true,
            "order": [[4, "desc"]],
            "pageLength": 25,
            "autoWidth": false,
            "responsive": true,
            "dom": '<"row align-items-center mb-3"<"col-md-4 d-flex align-items-center"l><"col-md-4 text-center"B><"col-md-4 d-flex justify-content-md-end"f>><"table-responsive"t><"row mt-3"<"col-md-6"i><"col-md-6 d-flex justify-content-md-end"p>>',
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "buttons": [
                { extend: "copyHtml5",  text: '<i class="far fa-copy"></i>',       className: "btn btn-secondary btn-sm" },
                { extend: "excelHtml5", text: '<i class="far fa-file-excel"></i>', className: "btn btn-secondary btn-sm" },
                { extend: "csvHtml5",   text: '<i class="far fa-file-alt"></i>',   className: "btn btn-secondary btn-sm" },
                { extend: "print",      text: '<i class="fa fa-print"></i>',       className: "btn btn-secondary btn-sm" },
                { extend: "colvis",     text: '<i class="fas fa-columns"></i>',    className: "btn btn-secondary btn-sm" }
            ],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search...",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "paginate": { "previous": "Prev", "next": "Next" }
            }
        });
    });
</script>

<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('payment-settings'); ?>" class="btn btn-default btn-sm"><i class="fas fa-cog"></i> <?php echo translate('payment_settings') ?: 'Payment Settings'; ?></a>
        </div>
        <h2 class="panel-title"><i class="fas fa-receipt"></i> <?php echo translate('payment_transactions') ?: 'Payment Transactions'; ?></h2>
    </header>
    <div class="panel-body">
        <div class="row mb-md">
            <div class="col-md-3">
                <select id="filter_status" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
                    <option value="">All statuses</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <table class="table table-bordered table-hover table-condensed" width="100%" id="txn-table">
            <thead>
                <tr>
                    <th>SL</th><th>Date</th><th>Order</th><th>Customer</th><th>Gateway</th><th>Txn ID</th><th class="text-right">Amount</th><th>Status</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</section>
<script type="text/javascript">
$(function () {
    var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    var d = {}; d[csrfName] = csrfHash; $.ajaxSetup({ data: d });
    var t = $('#txn-table').DataTable({
        processing: true, serverSide: true, ordering: false, pageLength: 25, autoWidth: false,
        columns: [{data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},{data:7}],
        columnDefs: [{targets:[0,6,7],className:'text-center'}],
        ajax: {
            url: '<?php echo base_url('payment-settings/get_transactions_server_side'); ?>', type: 'POST',
            data: function (x) { x[csrfName] = csrfHash; x.status = $('#filter_status').val(); },
            dataSrc: function (j) { if (j.csrfHash) { csrfHash = j.csrfHash; d[csrfName] = csrfHash; $.ajaxSetup({ data: d }); } return j.data; }
        },
        language: { search: '_INPUT_', searchPlaceholder: 'Search order / txn / gateway...' }
    });
    $(document).on('change', '#filter_status', function () { t.ajax.reload(); });
});
</script>

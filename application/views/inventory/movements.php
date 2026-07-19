<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <?php if (get_permission('inventory_source', 'is_edit')): ?>
            <a href="<?php echo base_url('inventory_source/transfer'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-exchange-alt"></i> <?php echo translate('stock_transfer') ?: 'Stock Transfer'; ?></a>
            <?php endif; ?>
            <a href="<?php echo base_url('inventory_source/report'); ?>" class="btn btn-default btn-sm"><i class="fas fa-warehouse"></i> <?php echo translate('warehouse_report') ?: 'Warehouse Report'; ?></a>
        </div>
        <h2 class="panel-title"><i class="fas fa-list"></i> <?php echo translate('stock_movements') ?: 'Stock Movements'; ?></h2>
    </header>
    <div class="panel-body">
        <div class="row mb-md">
            <div class="col-md-3">
                <select id="filter_type" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
                    <option value="">All types</option>
                    <option value="adjust">Adjust</option>
                    <option value="allocation">Allocation (orders)</option>
                    <option value="transfer_in">Transfer In</option>
                    <option value="transfer_out">Transfer Out</option>
                    <option value="in">In</option>
                    <option value="out">Out</option>
                </select>
            </div>
        </div>
        <table class="table table-bordered table-hover table-condensed" width="100%" id="mv-table">
            <thead>
                <tr>
                    <th>SL</th><th>Date</th><th>Product</th><th>Warehouse</th><th>Type</th><th class="text-center">Qty</th><th>Reason</th>
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
    var t = $('#mv-table').DataTable({
        processing: true, serverSide: true, ordering: false, pageLength: 25, autoWidth: false,
        columns: [{data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6}],
        columnDefs: [{targets:[0,5],className:'text-center'}],
        ajax: {
            url: '<?php echo base_url('inventory_source/get_movements_server_side'); ?>', type: 'POST',
            data: function (x) { x[csrfName] = csrfHash; x.type = $('#filter_type').val(); },
            dataSrc: function (j) { if (j.csrfHash) { csrfHash = j.csrfHash; d[csrfName] = csrfHash; $.ajaxSetup({ data: d }); } return j.data; }
        },
        language: { search: '_INPUT_', searchPlaceholder: 'Search product / reference...' }
    });
    $(document).on('change', '#filter_type', function () { t.ajax.reload(); });
});
</script>

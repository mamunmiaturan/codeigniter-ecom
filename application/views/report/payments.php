<?php
$sym = html_escape($currency ?? '৳');
$qs  = http_build_query(['from' => $from, 'to' => $to]);
$status_class = function ($st) {
    $m = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'];
    return $m[$st] ?? 'secondary';
};
$method_label = function ($m) {
    $labels = ['cod' => translate('cash_on_delivery') ?: 'Cash on Delivery', 'online' => translate('online') ?: 'Online'];
    return $labels[$m] ?? ucfirst((string) $m);
};

// Totals across the breakdown.
$total_orders = 0;
$total_amount = 0.0;
foreach ($rows as $r) {
    $total_orders += (int) $r['orders'];
    $total_amount += (float) $r['amount'];
}
?>
<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('report/export/payments?' . $qs); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-file-csv"></i> <?php echo translate('export_csv') ?: 'Export CSV'; ?>
            </a>
            <a href="<?php echo base_url('report'); ?>" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?>
            </a>
        </div>
        <h2 class="panel-title"><i class="fas fa-credit-card"></i> <?php echo translate('payments_report') ?: 'Payments Report'; ?></h2>
    </header>
    <div class="panel-body">
        <form method="get" action="<?php echo base_url('report/payments'); ?>" class="form-inline" style="margin-bottom:20px;">
            <div class="form-group" style="margin-right:10px;">
                <label style="margin-right:5px;"><?php echo translate('from') ?: 'From'; ?></label>
                <input type="date" name="from" value="<?php echo html_escape($from); ?>" class="form-control input-sm">
            </div>
            <div class="form-group" style="margin-right:10px;">
                <label style="margin-right:5px;"><?php echo translate('to') ?: 'To'; ?></label>
                <input type="date" name="to" value="<?php echo html_escape($to); ?>" class="form-control input-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> <?php echo translate('apply') ?: 'Apply'; ?></button>
        </form>

        <table class="table table-bordered table-hover table-condensed" width="100%">
            <thead>
                <tr>
                    <th><?php echo translate('payment_method') ?: 'Payment Method'; ?></th>
                    <th><?php echo translate('payment_status') ?: 'Payment Status'; ?></th>
                    <th class="text-center"><?php echo translate('orders') ?: 'Orders'; ?></th>
                    <th class="text-right"><?php echo translate('amount') ?: 'Amount'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:20px;"><?php echo translate('no_data_found') ?: 'No data for this range.'; ?></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo html_escape($method_label($r['payment_method'])); ?></td>
                        <td><span class="badge badge-<?php echo $status_class($r['payment_status']); ?>"><?php echo ucfirst(html_escape($r['payment_status'])); ?></span></td>
                        <td class="text-center"><?php echo number_format((int) $r['orders']); ?></td>
                        <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $r['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right"><?php echo translate('total') ?: 'Total'; ?></th>
                        <th class="text-center"><?php echo number_format($total_orders); ?></th>
                        <th class="text-right"><?php echo $sym; ?> <?php echo number_format($total_amount, 2); ?></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</section>

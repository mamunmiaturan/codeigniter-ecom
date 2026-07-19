<?php
$sym = html_escape($currency ?? '৳');
$qs  = http_build_query(['from' => $from, 'to' => $to]);
?>
<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('report/export/customers?' . $qs); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-file-csv"></i> <?php echo translate('export_csv') ?: 'Export CSV'; ?>
            </a>
            <a href="<?php echo base_url('report'); ?>" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?>
            </a>
        </div>
        <h2 class="panel-title"><i class="fas fa-users"></i> <?php echo translate('customers_report') ?: 'Customers Report'; ?></h2>
    </header>
    <div class="panel-body">
        <form method="get" action="<?php echo base_url('report/customers'); ?>" class="form-inline" style="margin-bottom:20px;">
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

        <div class="row">
            <div class="col-sm-4 col-xs-12">
                <div class="stat-card-custom">
                    <div class="stat-icon-box bg-custom-blue"><i class="fas fa-user-plus"></i></div>
                    <div class="stat-content-box">
                        <span class="stat-count-text"><?php echo number_format((int) $new_customers); ?></span>
                        <span class="stat-label-text"><?php echo translate('new_customers') ?: 'New Customers'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-hover table-condensed" width="100%">
            <thead>
                <tr>
                    <th><?php echo translate('sl') ?: 'SL'; ?></th>
                    <th><?php echo translate('name') ?: 'Name'; ?></th>
                    <th><?php echo translate('email') ?: 'Email'; ?></th>
                    <th class="text-center"><?php echo translate('orders') ?: 'Orders'; ?></th>
                    <th class="text-right"><?php echo translate('total_spend') ?: 'Total Spend'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:20px;"><?php echo translate('no_data_found') ?: 'No data for this range.'; ?></td></tr>
                <?php else: $i = 1; foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo html_escape($r['name']); ?></td>
                        <td><?php echo html_escape($r['email'] ?: '—'); ?></td>
                        <td class="text-center"><span class="badge badge-primary"><?php echo number_format((int) $r['orders']); ?></span></td>
                        <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $r['spend'], 2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

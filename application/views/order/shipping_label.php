<?php
defined('BASEPATH') or exit('No direct script access allowed');
$site = get_global_setting('site_name') ?: 'Bazaar';
$sym  = get_global_setting('currency_symbol') ?: '৳';
$cod  = ($order['payment_method'] === 'cod' && $order['payment_status'] !== 'paid') ? (float) $order['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Shipping Label <?php echo html_escape($order['order_number']); ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#111; padding:24px; }
        .label { border:2px solid #111; border-radius:8px; padding:18px; max-width:440px; }
        .row { display:flex; justify-content:space-between; border-bottom:1px dashed #999; padding-bottom:8px; margin-bottom:12px; }
        .to { font-size:15px; line-height:1.5; }
        .to strong { font-size:20px; }
        .cod { font-size:22px; font-weight:bold; border:2px solid #111; padding:8px 14px; display:inline-block; margin-top:12px; }
        .muted { color:#555; font-size:12px; }
        .print-btn { margin-bottom:16px; }
        @media print { .print-btn { display:none; } body { padding:0; } }
    </style>
</head>
<body>
    <div class="print-btn"><button onclick="window.print()">🖨 Print</button></div>
    <div class="label">
        <div class="row">
            <div><strong><?php echo html_escape($site); ?></strong><div class="muted">Cash on delivery · Bangladesh</div></div>
            <div style="text-align:right;"><div class="muted">ORDER</div><strong><?php echo html_escape($order['order_number']); ?></strong></div>
        </div>
        <div class="to">
            <div class="muted">DELIVER TO:</div>
            <strong><?php echo html_escape($order['customer_name']); ?></strong><br>
            <?php echo html_escape($order['customer_phone']); ?><br>
            <?php echo html_escape(implode(', ', array_filter([$order['shipping_address'], $order['shipping_area'], $order['shipping_district'], $order['shipping_division'], $order['shipping_postcode']]))); ?>
        </div>
        <?php if (!empty($shipment['carrier'])): ?>
            <div style="margin-top:12px;">Carrier: <strong><?php echo html_escape($shipment['carrier']); ?></strong><?php if (!empty($shipment['tracking_number'])): ?> · <span class="muted">Tracking:</span> <?php echo html_escape($shipment['tracking_number']); ?><?php endif; ?></div>
        <?php endif; ?>
        <?php if ($cod > 0): ?>
            <div class="cod">COLLECT (COD): <?php echo html_escape($sym) . ' ' . number_format($cod, 2); ?></div>
        <?php else: ?>
            <div style="margin-top:12px;" class="muted"><strong>PREPAID</strong> — do not collect cash.</div>
        <?php endif; ?>
    </div>
</body>
</html>

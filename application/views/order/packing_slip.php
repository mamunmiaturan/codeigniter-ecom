<?php
defined('BASEPATH') or exit('No direct script access allowed');
$site = get_global_setting('site_name') ?: 'Bazaar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Packing Slip <?php echo html_escape($order['order_number']); ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#222; font-size:13px; padding:24px; }
        .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #222; padding-bottom:12px; margin-bottom:18px; }
        .head h1 { margin:0; font-size:20px; }
        .muted { color:#666; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { border:1px solid #ddd; padding:8px; text-align:left; }
        th { background:#f5f5f5; }
        .text-center { text-align:center; }
        .print-btn { margin-bottom:16px; }
        @media print { .print-btn { display:none; } body { padding:0; } }
    </style>
</head>
<body>
    <div class="print-btn"><button onclick="window.print()">🖨 Print</button></div>
    <div class="head">
        <div><h1><?php echo html_escape($site); ?></h1><div class="muted">Warehouse packing slip</div></div>
        <div class="text-center" style="text-align:right;">
            <h2 style="margin:0;">PACKING SLIP</h2>
            <div><strong><?php echo html_escape($order['order_number']); ?></strong></div>
            <div class="muted"><?php echo date('d M Y', strtotime($order['placed_at'])); ?></div>
        </div>
    </div>
    <table style="border:none;margin-bottom:10px;">
        <tr style="border:none;">
            <td style="border:none;vertical-align:top;">
                <strong>Ship to</strong><br>
                <?php echo html_escape($order['customer_name']); ?><br>
                <?php echo html_escape($order['customer_phone']); ?><br>
                <?php echo html_escape(implode(', ', array_filter([$order['shipping_address'], $order['shipping_area'], $order['shipping_district'], $order['shipping_division'], $order['shipping_postcode']]))); ?>
            </td>
            <td style="border:none;vertical-align:top;text-align:right;">
                <strong>Total items</strong> <?php echo (int) $order['item_count']; ?><br>
                <strong>Payment</strong> <?php echo strtoupper($order['payment_method']); ?>
            </td>
        </tr>
    </table>
    <table>
        <thead>
            <tr><th style="width:40px;" class="text-center">✓</th><th>Product</th><th>SKU</th><th class="text-center">Qty</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td class="text-center">&nbsp;</td>
                    <td><?php echo html_escape($it['product_name']); ?><?php if (!empty($it['variant_name'])): ?> <span class="muted">(<?php echo html_escape($it['variant_name']); ?>)</span><?php endif; ?></td>
                    <td><?php echo html_escape($it['sku'] ?: '—'); ?></td>
                    <td class="text-center"><?php echo (int) $it['quantity']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="muted" style="margin-top:34px;">Packed by: ______________________ &nbsp;&nbsp;&nbsp; Checked by: ______________________</p>
</body>
</html>

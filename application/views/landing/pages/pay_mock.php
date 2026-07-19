<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mock Payment Gateway</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/vendors/bootstrap/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/vendors/bootstrap-icons/bootstrap-icons.css'); ?>">
    <style>body{background:#eef1f6;} .gw{max-width:460px;margin:8vh auto;}</style>
</head>
<body>
    <div class="gw">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex align-items-center" style="gap:8px;">
                <i class="bi bi-shield-lock"></i> <strong>Mock Payment Gateway</strong>
                <span class="badge bg-warning text-dark ms-auto">TEST</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">This is a simulated gateway for testing. No real payment is taken.</p>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Order</span><span class="fw-semibold"><?php echo html_escape($order['order_number']); ?></span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-muted">Amount</span><span class="fw-bold fs-5"><?php echo shop_money($order['total']); ?></span></div>
                <div class="d-grid gap-2">
                    <form action="<?php echo base_url('payment/mock/pay'); ?>" method="post" class="m-0 d-grid">
                        <input type="hidden" name="order_number" value="<?php echo html_escape($order['order_number']); ?>">
                        <button class="btn btn-success btn-lg" type="submit"><i class="bi bi-check2-circle me-1"></i> Pay <?php echo shop_money($order['total']); ?></button>
                    </form>
                    <form action="<?php echo base_url('payment/mock/cancel'); ?>" method="post" class="m-0 d-grid">
                        <input type="hidden" name="order_number" value="<?php echo html_escape($order['order_number']); ?>">
                        <button class="btn btn-outline-danger" type="submit">Cancel payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

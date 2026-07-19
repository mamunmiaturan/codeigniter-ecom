<section class="panel">
    <div class="tabs-custom">
        <div class="tab-content">
            <div class="tab-pane box active" id="security-tab">

                <!-- ========================================================
                     2FA STATUS CARD
                ======================================================== -->
                <div class="panel-body">
                    <h4 class="mb-sm">
                        <i class="fas fa-shield-alt text-primary"></i>
                        Two-Factor Authentication (2FA)
                        <?php if ($tfa_enabled): ?>
                            <span class="label label-success" style="font-size:12px;vertical-align:middle;">Enabled</span>
                        <?php else: ?>
                            <span class="label label-default" style="font-size:12px;vertical-align:middle;">Disabled</span>
                        <?php endif; ?>
                    </h4>
                    <p class="text-muted" style="max-width:600px;">
                        Two-factor authentication adds an extra layer of security. Once enabled, you will need to enter a
                        6-digit code from your authenticator app (Google Authenticator, Authy, etc.) each time you log in.
                    </p>

                    <?php
                    // Show freshly generated backup codes once after enable/regenerate
                    $new_backup_codes = $this->session->flashdata('2fa_backup_codes');
                    if (!empty($new_backup_codes)): ?>
                    <div class="alert alert-warning" style="max-width:520px;">
                        <strong><i class="fas fa-exclamation-triangle"></i> Save these backup codes now!</strong>
                        <p style="margin:8px 0 4px;">Each code can only be used once. Store them in a safe place.</p>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                            <?php foreach ($new_backup_codes as $code): ?>
                                <code style="background:#fff3cd;padding:4px 10px;border-radius:4px;font-size:14px;letter-spacing:1px;"><?php echo html_escape($code); ?></code>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$tfa_enabled): ?>
                    <!-- ---- SETUP FORM ---- -->
                    <div class="row mt-md">
                        <div class="col-md-5">
                            <h5>Step 1 — Scan the QR code</h5>
                            <p class="text-muted small">Open your authenticator app and scan the code below, or enter the secret key manually.</p>
                            <?php if ($tfa_qr_url): ?>
                            <div style="background:#fff;padding:10px;display:inline-block;border:1px solid #ddd;border-radius:6px;">
                                <img src="<?php echo html_escape($tfa_qr_url); ?>" alt="QR Code" width="200" height="200" style="display:block;">
                            </div>
                            <div class="mt-sm">
                                <small class="text-muted">Manual key:</small><br>
                                <code style="font-size:13px;letter-spacing:2px;"><?php echo html_escape($tfa_secret); ?></code>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5 col-md-offset-1">
                            <h5>Step 2 — Enter the 6-digit code</h5>
                            <p class="text-muted small">Enter the code shown in your app to verify and activate 2FA.</p>
                            <?php echo form_open(base_url('profile/enable_2fa'), ['class' => 'form-horizontal']); ?>
                            <div class="form-group">
                                <div class="col-sm-8">
                                    <input type="text"
                                           name="totp_code"
                                           id="totp_code"
                                           class="form-control"
                                           placeholder="000000"
                                           maxlength="6"
                                           autocomplete="one-time-code"
                                           style="font-size:22px;letter-spacing:6px;text-align:center;"
                                           required>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-8">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-check"></i> Enable 2FA
                                    </button>
                                </div>
                            </div>
                            <?php echo form_close(); ?>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- ---- MANAGEMENT (2FA is ON) ---- -->
                    <div class="row mt-md">
                        <div class="col-md-5">
                            <div class="panel panel-default">
                                <div class="panel-heading"><strong><i class="fas fa-key"></i> Backup Codes</strong></div>
                                <div class="panel-body">
                                    <p class="text-muted small">
                                        You have <strong><?php echo (int) $backup_count; ?></strong> backup code(s) remaining.
                                        Use a backup code to log in if you lose access to your authenticator app.
                                    </p>
                                    <?php echo form_open(base_url('profile/regenerate_backup_codes')); ?>
                                    <button type="submit" class="btn btn-warning btn-xs"
                                            onclick="return confirm('This will invalidate all current backup codes. Continue?');">
                                        <i class="fas fa-sync-alt"></i> Regenerate Backup Codes
                                    </button>
                                    <?php echo form_close(); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 col-md-offset-1">
                            <div class="panel panel-danger">
                                <div class="panel-heading"><strong><i class="fas fa-times-circle"></i> Disable 2FA</strong></div>
                                <div class="panel-body">
                                    <p class="text-muted small">Confirm your password to disable two-factor authentication.</p>
                                    <?php echo form_open(base_url('profile/disable_2fa'), ['class' => 'form-horizontal']); ?>
                                    <div class="form-group">
                                        <div class="col-sm-9">
                                            <input type="password"
                                                   name="current_password"
                                                   class="form-control input-sm"
                                                   placeholder="Current password"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-9">
                                            <button type="submit" class="btn btn-danger btn-xs"
                                                    onclick="return confirm('Are you sure you want to disable 2FA?');">
                                                <i class="fas fa-shield-alt"></i> Disable 2FA
                                            </button>
                                        </div>
                                    </div>
                                    <?php echo form_close(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /.panel-body -->
            </div>
        </div>
    </div>
</section>

<div class="auth-wrapper">
    <div class="auth-side-form">
        <div class="form-container">

            <div class="logo-box">
                <img src="<?php echo base_url('uploads/app_image/logo.png'); ?>" alt="Auth">
            </div>

            <h4 style="text-align:center;margin-bottom:6px;">Two-Factor Verification</h4>
            <p style="text-align:center;color:#888;font-size:13px;margin-bottom:20px;">
                Enter the 6-digit code from your authenticator app, or a backup code.
            </p>

            <?php echo form_open('authentication/verify_2fa/form'); ?>

            <div class="form-group">
                <label for="totp_code">Verification Code</label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <input type="text"
                           name="totp_code"
                           id="totp_code"
                           class="form-control"
                           placeholder="6-digit code or backup code"
                           maxlength="9"
                           autocomplete="one-time-code"
                           autofocus
                           required>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-auth-submit">
                    <i class="fas fa-check-circle"></i> Verify
                </button>
            </div>

            <div style="text-align:center;margin-top:12px;">
                <a href="<?php echo base_url('authentication'); ?>" style="font-size:13px;color:#888;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
    <div class="auth-side-banner"></div>
</div>

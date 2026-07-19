<div class="auth-wrapper">
    <div class="auth-side-form">
        <div class="form-container">
            <div class="logo-box">
                <img src="<?php echo base_url('uploads/app_image/logo.png'); ?>" alt="Auth">
            </div>

            <div class="form-header">
                <h3>Reset Password</h3>
                <p>Enter your new password below.</p>
            </div>

            <?php echo form_open($this->uri->uri_string()); ?>
            <input type="hidden" name="reset_key" value="<?php echo html_escape($this->input->get('key')); ?>">

            <div class="form-group <?php if (form_error('password')) echo 'has-error'; ?>">
                <label for="password">New Password</label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-key"></i>
                    </div>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter new password" required>
                    <div class="password-toggle toggle-password" data-target="password">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                <?php if (form_error('password')): ?>
                    <span class="error-text"><?php echo form_error('password'); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?php if (form_error('c_password')) echo 'has-error'; ?>">
                <label for="c_password">Confirm Password</label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" class="form-control" name="c_password" id="c_password" placeholder="Confirm new password" required>
                    <div class="password-toggle toggle-password" data-target="c_password">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                <?php if (form_error('c_password')): ?>
                    <span class="error-text"><?php echo form_error('c_password'); ?></span>
                <?php endif; ?>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-submit">
                    Update Password <i class="fas fa-check-circle"></i>
                </button>
            </div>

            <script src="<?php echo base_url('assets/backend/js/app/password-toggle.js'); ?>"></script>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
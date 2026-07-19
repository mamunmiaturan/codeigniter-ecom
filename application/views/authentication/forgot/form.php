<div class="auth-wrapper">
    <div class="auth-side-form">
        <div class="form-container">
            <div class="logo-box">
                <img src="<?php echo base_url('uploads/app_image/logo.png'); ?>" alt="Auth">
            </div>

            <div class="form-header">
                <h3>Forgot Password?</h3>
                <p>Enter your email to reset your password.</p>
            </div>

            <?php
            if ($this->session->flashdata('reset_res')) {
                if ($this->session->flashdata('reset_res') == 'TRUE') {
                    echo '<div class="alert-box success">Password reset email sent successfully.</div>';
                } elseif ($this->session->flashdata('reset_res') == 'FALSE') {
                    echo '<div class="alert-box danger">You entered the wrong Email.</div>';
                }
            }
            ?>

            <?php echo form_open($this->uri->uri_string()); ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Your email address" required autoFocus>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-submit">
                    Restore Password <i class="fas fa-key"></i>
                </button>
            </div>

            <a href="<?php echo base_url('authentication'); ?>" class="back-link" style="display: block; text-align: center; margin-top: 25px; color: var(--text-muted); text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<div class="auth-wrapper">
    <div class="auth-side-form">
        <div class="form-container">

            <!-- Logo -->
            <div class="logo-box">
                <img src="<?php echo base_url('uploads/app_image/logo.png'); ?>" alt="Auth">
            </div>

            <div class="form-header">
                <h3>Create your account</h3>
                <p>Register to start shopping.</p>
            </div>

            <!-- FORM: posts to the storefront customer registration handler -->
            <?php echo form_open(base_url('account/register')); ?>

            <!-- Full name -->
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="text" name="name" id="name" class="form-control"
                        value="<?php echo set_value('name'); ?>" placeholder="Enter full name" required autofocus>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" name="email" id="email" class="form-control"
                        value="<?php echo set_value('email'); ?>" placeholder="Enter email address" required>
                </div>
            </div>

            <!-- Phone (optional) -->
            <div class="form-group">
                <label for="phone">Phone <span class="text-muted">(optional)</span></label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-phone"></i>
                    </div>
                    <input type="text" name="phone" id="phone" class="form-control"
                        value="<?php echo set_value('phone'); ?>" placeholder="Enter phone number">
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="Create a password" required>
                </div>
                <span class="error-text" style="color: var(--text-muted); font-weight: 500;">At least 8 characters, with upper/lowercase, a number and a symbol.</span>
            </div>

            <!-- Confirm password -->
            <div class="form-group">
                <label for="password_confirm">Confirm Password <span class="required">*</span></label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" name="password_confirm" id="password_confirm" class="form-control"
                        placeholder="Re-enter password" required>
                </div>
            </div>

            <!-- Submit -->
            <div class="btn-container">
                <button type="submit" class="btn-submit">
                    Create Account <i class="fas fa-user-plus"></i>
                </button>
            </div>

            <p class="auth-alt" style="text-align:center; margin-top:22px; margin-bottom:0; color: var(--text-muted); font-weight:600;">
                Already have an account?
                <a href="<?php echo base_url('login'); ?>" style="color: var(--primary); text-decoration:none;">Log in</a>
            </p>

            <?php echo form_close(); ?>
        </div>
    </div>
</div>

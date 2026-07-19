<div class="auth-wrapper">
    <div class="auth-side-form">
        <div class="form-container">

            <!-- Logo -->
            <div class="logo-box">
                <img src="<?php echo base_url('uploads/app_image/logo.png'); ?>" alt="Auth">
            </div>

            <!-- FORM -->
            <?php echo form_open(base_url('authentication')); ?>

            <!-- Email -->
            <div class="form-group <?php echo form_error('email') ? 'has-error' : ''; ?>">
                <label for="email">Email Address <span class="required">*</span></label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        value="<?php echo set_value('email'); ?>"
                        placeholder="Enter email address"
                        required
                        autofocus>
                </div>
                <?php if (form_error('email')): ?>
                    <span class="error-text"><?php echo form_error('email'); ?></span>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group <?php echo form_error('password') ? 'has-error' : ''; ?>">
                <label for="password">Password <span class="required">*</span></label>
                <div class="input-group">
                    <div class="icon-box">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Enter password"
                        required>
                    <button type="button" class="password-toggle" id="togglePassword"
                        aria-label="Show password" aria-pressed="false"
                        aria-controls="password">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <?php if (form_error('password')): ?>
                    <span class="error-text"><?php echo form_error('password'); ?></span>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <label class="remember-check">
                    <input type="checkbox" name="remember">
                    Remember Me
                </label>
                <a href="<?php echo base_url('authentication/' . route_hash('forgot')); ?>" class="forgot-pass">Forgot Password?</a>
            </div>

            <!-- reCAPTCHA (only when configured in settings) -->
            <?php
            $ci =& get_instance();
            $ci->load->library('recaptcha');
            if ($ci->recaptcha->is_configured()):
            ?>
                <div class="form-group" style="margin-bottom:18px;">
                    <?php echo $ci->recaptcha->getWidget(); ?>
                    <?php echo $ci->recaptcha->getScriptTag(); ?>
                </div>
            <?php endif; ?>

            <!-- Submit -->
            <div class="btn-container">
                <button type="submit" class="btn-submit">
                    Log In <i class="fas fa-sign-in-alt"></i>
                </button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- SCRIPT -->
<script src="<?php echo base_url('assets/backend/js/app/password-toggle.js'); ?>"></script>
<?php
$sec_error = $this->input->get('sec_error');
if ($sec_error):
?>
    <script type="text/javascript">
        swal({
            toast: true,
            position: 'top-end',
            type: 'error',
            title: 'Security Alert! Session mismatch detected. You have been safely logged out.',
            confirmButtonClass: 'btn btn-default',
            buttonsStyling: false,
            timer: 3000
        })
    </script>
<?php endif; ?>

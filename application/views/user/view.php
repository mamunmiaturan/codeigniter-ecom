<?php $currency_symbol = $global_config['currency_symbol']; ?>
<div class="row">
    <div class="col-md-12 mb-lg">
        <div class="profile-card-premium">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar-ring">
                    <?php
                    $folder = ($user['role_id'] == 7) ? 'client/' : 'user/';
                    ?>
                    <img class="profile-avatar-img" src="<?= $this->app_lib->get_image_url($folder . ($user['photo'] ?? '')); ?>" alt="Profile">
                </div>
                <ul class="profile-social-icons">
                    <li>
                        <a href="<?= (empty($user['facebook_url']) ? '#' : $user['facebook_url']); ?>" target="_blank">
                            <span class="fab fa-facebook-f"></span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= (empty($user['twitter_url']) ? '#' : $user['twitter_url']); ?>" target="_blank">
                            <span class="fab fa-twitter"></span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= (empty($user['linkedin_url']) ? '#' : $user['linkedin_url']); ?>" target="_blank">
                            <span class="fab fa-linkedin-in"></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="profile-details-wrapper">
                <h5 class="profile-main-title">
                    <?= html_escape($user['name'] ?? 'N/A'); ?>
                    <span class="profile-role-badge"><?= html_escape($user['role'] ?? 'User'); ?></span>
                </h5>
                <div class="profile-email-sub">
                    <i class="far fa-envelope"></i> <?= html_escape($user['email'] ?? 'N/A') ?>
                </div>

                <div class="profile-info-grid">
                    <!-- User ID -->
                    <div class="profile-info-item">
                        <div class="profile-info-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div>
                            <div class="profile-info-label"><?= translate('user_id'); ?></div>
                            <div class="profile-info-value"><?= html_escape(!empty($user['user_id']) ? $user['user_id'] : 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Mobile Number -->
                    <div class="profile-info-item">
                        <div class="profile-info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <div class="profile-info-label"><?= translate('mobile_no'); ?></div>
                            <div class="profile-info-value"><?= html_escape(!empty($user['mobile_no']) ? $user['mobile_no'] : 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Gender -->
                    <div class="profile-info-item">
                        <div class="profile-info-icon">
                            <i class="fas fa-venus-mars"></i>
                        </div>
                        <div>
                            <div class="profile-info-label"><?= translate('gender'); ?></div>
                            <div class="profile-info-value"><?= html_escape(!empty($user['gender']) ? ucfirst($user['gender']) : 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Blood Group -->
                    <div class="profile-info-item">
                        <div class="profile-info-icon">
                            <i class="fas fa-tint text-danger"></i>
                        </div>
                        <div>
                            <div class="profile-info-label"><?= translate('blood_group'); ?></div>
                            <div class="profile-info-value"><?= html_escape(!empty($user['blood_group']) ? $user['blood_group'] : 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Date of Birth -->
                    <div class="profile-info-item">
                        <div class="profile-info-icon">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                        <div>
                            <div class="profile-info-label"><?= translate('date_of_birth'); ?></div>
                            <div class="profile-info-value"><?= html_escape(!empty($user['dob']) ? _d($user['dob']) : 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Nationality -->
                    <div class="profile-info-item">
                        <div class="profile-info-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <div class="profile-info-label"><?= translate('nationality'); ?></div>
                            <div class="profile-info-value"><?= html_escape(!empty($user['nationality']) ? $user['nationality'] : 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="panel-group" id="accordion">
            <div class="panel panel-accordion">
                <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h4 class="panel-title" style="flex-grow: 1; margin: 0;">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#profile" style="display: inline-block;">
                            <i class="fas fa-user-edit"></i> <?php echo translate('profile'); ?>
                        </a>
                    </h4>
                    <?php if (get_permission('user', 'is_edit')): ?>
                    <div class="auth-pan" style="margin: 0; float: none !important;">
                        <button class="btn btn-default btn-circle" id="authentication_btn">
                            <i class="fas fa-unlock-alt"></i> <?php echo translate('authentication'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div id="profile" class="accordion-body collapse <?php echo ($this->session->flashdata('profile_tab') ? 'in' : ''); ?>">
                    <?php echo form_open_multipart(base_url('user/' . route_hash('update'))); ?>                    <div class="panel-body">
                        <input type="hidden" name="user_id" id="user_id" value="<?php echo encrypt_id($user['id']); ?>">
                        <div class="row">
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="far fa-user"></i></span>
                                        <input class="form-control" name="name" type="text" value="<?php echo set_value('name', $user['name']); ?>">
                                    </div>
                                    <span class="error"><?php echo form_error('name'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('mobile_no')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('mobile_no'); ?> <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fas fa-phone-volume"></i></span>
                                        <input class="form-control" name="mobile_no" type="text" value="<?php echo set_value('mobile_no', $user['mobile_no']); ?>">
                                    </div>
                                    <span class="error"><?php echo form_error('mobile_no'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('email')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('email'); ?> <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="far fa-envelope-open"></i></span>
                                        <input type="text" class="form-control" name="email" id="email" value="<?php echo set_value('email', $user['email']); ?>" />
                                    </div>
                                    <span class="error"><?php echo form_error('email'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('dob')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('date_of_birth'); ?> </label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fas fa-birthday-cake"></i></span>
                                        <input type="text" class="form-control" id="dob" name="dob" value="<?php echo set_value('dob', $user['dob']); ?>" data-plugin-datepicker data-plugin-options='{ "startView": 2 }'>
                                    </div>
                                    <span class="error"><?php echo form_error('dob'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('gender')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('gender'); ?></label>
                                    <?php
                                    $gender_array = $this->app_lib->get_gender();
                                    echo form_dropdown("gender", $gender_array, set_value('gender', $user['gender']), "class='form-control' data-plugin-selectTwo data-width='100%'
                                                    data-minimum-results-for-search='Infinity'");
                                    ?>
                                    <span class="error"><?php echo form_error('gender'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('blood_group')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('blood_group'); ?></label>
                                    <?php
                                    $bloodarray = $this->app_lib->get_blood_group();
                                    echo form_dropdown("blood_group", $bloodarray, set_value("blood_group", $user['blood_group']), "class='form-control' data-plugin-selectTwo
                                                    data-width='100%' data-minimum-results-for-search='Infinity' ");
                                    ?>
                                    <span class="error"><?php echo form_error('blood_group'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('religion')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('religion'); ?></label>
                                    <?php
                                    $religion_array = $this->app_lib->get_religion();
                                    echo form_dropdown("religion", $religion_array, set_value("religion", $user['religion']), "class='form-control' data-plugin-selectTwo
                                                    data-width='100%' data-minimum-results-for-search='Infinity'");
                                    ?>
                                    <span class="error"><?php echo form_error('religion'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('marital_status')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('marital_status'); ?></label>
                                    <?php
                                    $marital_array = $this->app_lib->get_marital_status();
                                    echo form_dropdown("marital_status", $marital_array, set_value("marital_status", $user['marital_status']), "class='form-control' data-plugin-selectTwo
                                                    data-width='100%' data-minimum-results-for-search='Infinity' ");
                                    ?>
                                    <span class="error"><?php echo form_error('marital_status'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-sm">
                                <div class="form-group">
                                    <label class="control-label"><?php echo translate('age'); ?></label>
                                    <?php
                                    $computed_age = 0;
                                    if (!empty($user['dob'])) {
                                        try {
                                            $dob = new DateTime($user['dob']);
                                            $today = new DateTime();
                                            $computed_age = $dob->diff($today)->y;
                                        } catch (Exception $e) {
                                            $computed_age = 0;
                                        }
                                    }
                                    ?>
                                    <input type="text" class="form-control" id="age" name="age" value="<?php echo html_escape($computed_age); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('educational_qualification')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('educational_qualification'); ?></label>
                                    <?php
                                    $education_array = $this->app_lib->get_education_level();
                                    echo form_dropdown("educational_qualification", $education_array, set_value("educational_qualification", $user['educational_qualification']), "class='form-control' data-plugin-selectTwo
                                                    data-width='100%' data-minimum-results-for-search='Infinity' ");
                                    ?>
                                    <span class="error"><?php echo form_error('educational_qualification'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('nationality')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('nationality'); ?></label>
                                    <?php
                                    $nationality_array = $this->app_lib->get_nationality();
                                    echo form_dropdown("nationality", $nationality_array, set_value("nationality", $user['nationality']), "class='form-control' data-plugin-selectTwo
                                                    data-width='100%' data-minimum-results-for-search='Infinity' ");
                                    ?>
                                    <span class="error"><?php echo form_error('nationality'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('nid_no')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('nid_no'); ?></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" name="nid_no" value="<?php echo set_value('nid_no', $user['nid_no']); ?>">
                                    </div>
                                    <span class="error"><?php echo form_error('nid_no'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('user_role')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('role'); ?> <span class="required">*</span></label>
                                    <?php
                                    $logged_in_role = loggedin_role_id();
                                    $role_list = $this->app_lib->getRoles($logged_in_role);
                                    echo form_dropdown("user_role", $role_list, set_value('user_role', $user['role_id']), "class='form-control'
                                                    data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
                                    ?>
                                    <span class="error"><?php echo form_error('user_role'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-sm">
                                <div class="form-group <?php if (form_error('status')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('status'); ?></label>
                                    <?php
                                    $status_array = $this->app_lib->get_status();
                                    $selected_status = $user['status'] ?? ($user['active'] == 1 ? 'Active' : 'Inactive');
                                    echo form_dropdown("status", $status_array, set_value("status", $selected_status), "class='form-control' data-plugin-selectTwo
                                                    data-width='100%' data-minimum-results-for-search='Infinity' ");
                                    ?>
                                    <span class="error"><?php echo form_error('status'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="input-file-now"><?php echo translate('profile_picture'); ?></label>
                                    <input type="file" name="user_photo" class="dropify" data-allowed-file-extensions="jpg png" data-height="120" data-default-file="<?php echo $this->app_lib->get_image_url('user/' . $user['photo']); ?>" />
                                    <input type="hidden" name="old_user_photo" value="<?php echo $user['photo']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-sm">
                                <div class="form-group <?php if (form_error('address')) echo 'has-error'; ?>">
                                    <label class="control-label"><?php echo translate('address'); ?></label>
                                    <textarea class="form-control" rows="3" name="address" placeholder="<?php echo translate('address'); ?>"><?php echo set_value('address', $user['address']); ?></textarea>
                                    <span class="error"><?php echo form_error('address'); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if (get_permission('user', 'is_edit')): ?>
                        <div class="row">
                            <div class="col-md-offset-10 col-md-2">
                                <button type="submit" name="submit" value="update" class="btn btn btn-success btn-block"> <i class="fas fa-edit"></i> <?php echo translate('update'); ?></button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Authentication And Account Inactive Modal -->
    <div id="authentication_modal" class="zoom-anim-dialog modal-block modal-block-primary mfp-hide">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-unlock-alt"></i> <?php echo translate('authentication'); ?>
                </h4>
            </header>
            <div class="panel-body">
                <div class="form-group">
                    <label for="password" class="control-label"><?php echo translate('password'); ?> <span class="required">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control password" name="password" id="reset_password" />
                        <span class="input-group-addon toggle-password" data-target="#reset_password" style="cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <span class="control-label" id="password-msg"></span>
                </div>
                <div class="form-group mb-md">
                    <div class="checkbox-replace">
                        <label class="i-checks">
                            <input type="checkbox" name="authentication" id="cb_authentication">
                            <i></i> <?php echo translate('login_authentication_deactivate'); ?>
                        </label>
                    </div>
                </div>
            </div>
            <footer class="panel-footer">
                <div class="text-right">
                    <button class="btn btn-default mr-xs" id="userPassUpdate" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing"><?php echo translate('update'); ?></button>
                    <button class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
                </div>
            </footer>
        </section>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            // Robust DOB to Age auto-calculator function
            function calculateAge(birthDateString) {
                if (!birthDateString) return '';
                var birthDate = new Date(birthDateString);
                if (isNaN(birthDate.getTime())) {
                    var parts = birthDateString.split(/[-/.]/);
                    if (parts.length === 3) {
                        if (parts[0].length === 4) {
                            birthDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                        } else if (parts[2].length === 4) {
                            birthDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
                        }
                    }
                }
                if (isNaN(birthDate.getTime())) return '';
                var today = new Date();
                var age = today.getFullYear() - birthDate.getFullYear();
                var m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 0 ? age : 0;
            }

            // Attach listener for both manual changes and bootstrap datepicker events
            $('#dob').on('change changeDate', function() {
                var dobVal = $(this).val();
                var computedAge = calculateAge(dobVal);
                $('#age').val(computedAge);
            });

            // user authentication modal show
            $('#authentication_btn').on('click', function() {
                var status = "<?php echo html_escape($user['active'] ?? ''); ?>";
                if (status === '0') {
                    $('#cb_authentication').prop('checked', true);
                    $('#cb_authentication').prop('disabled', true);
                    $('#reset_password').val("");
                    $('#reset_password').prop('disabled', true);
                } else {
                    $('#cb_authentication').prop('checked', false);
                    $('#cb_authentication').prop('disabled', false);
                    $('#reset_password').val("");
                    $('#reset_password').prop('disabled', false);
                }
                mfp_modal('#authentication_modal');
            });

            // user authentication update submit via AJAX
            $('#userPassUpdate').on('click', function() {
                var btn = $(this);
                var password = $('#reset_password').val();
                var authentication = $('#cb_authentication').is(':checked') ? 1 : 0;
                var user_id = $('#user_id').val();
                $('#password-msg').removeClass('text-danger').html('');

                $.ajax({
                    url: "<?php echo base_url('user/' . route_hash('change_password')); ?>",
                    type: "POST",
                    data: {
                        user_id: user_id,
                        password: password,
                        authentication: authentication
                    },
                    dataType: "json",
                    beforeSend: function() {
                        btn.button('loading');
                    },
                    success: function(data) {
                        if (data.status == 'success') {
                            window.location.reload();
                        } else {
                            $('#password-msg').html(data.msg).addClass('text-danger');
                        }
                    },
                    complete: function() {
                        btn.button('reset');
                    }
                });
            });
        });
    </script>
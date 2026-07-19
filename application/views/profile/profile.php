<?php
$currency_symbol = $global_config['currency_symbol'];
?>
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
                    <div class="auth-pan" style="margin: 0; float: none !important;">
                        <button class="btn btn-default btn-circle" id="authentication_btn">
                            <i class="fas fa-unlock-alt"></i> <?php echo translate('authentication'); ?>
                        </button>
                    </div>
                </div>
                <div id="profile" class="accordion-body collapse <?php echo ($this->session->flashdata('profile_tab') ? 'in' : ''); ?>">
                    <?php echo form_open_multipart(base_url('profile/' . route_hash('index'))); ?>                    <div class="panel-body">
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
                        <div class="row">
                            <div class="col-md-offset-10 col-md-2">
                                <button type="submit" name="submit" value="update" class="btn btn btn-success btn-block"> <i class="fas fa-edit"></i> <?php echo translate('update'); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Login Password Change Modal -->
<div id="authentication_modal" class="zoom-anim-dialog modal-block modal-block-primary mfp-hide">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title">
                <i class="fas fa-unlock-alt"></i> <?= translate('authentication'); ?>
            </h4>
        </header>
        <div class="panel-body">
            <!-- Current Password -->
            <div class="form-group mb-sm">
                <label for="current_password" class="control-label"><?= translate('current_password'); ?> <span class="required">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control" name="current_password" id="current_password" autocomplete="off" />
                    <span class="input-group-addon toggle-password" data-target="#current_password" style="cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <span class="error" id="current_password-msg"></span>
            </div>

            <!-- New Password -->
            <div class="form-group mb-sm">
                <label for="new_password" class="control-label"><?= translate('new_password'); ?> <span class="required">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control password" name="new_password" id="new_password" autocomplete="off" />
                    <span class="input-group-addon toggle-password" data-target="#new_password" style="cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <span class="error" id="new_password-msg"></span>
            </div>

            <!-- Confirm Password -->
            <div class="form-group mb-md">
                <label for="confirm_password" class="control-label"><?= translate('confirm_password'); ?> <span class="required">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control password" name="confirm_password" id="confirm_password" autocomplete="off" />
                    <span class="input-group-addon toggle-password" data-target="#confirm_password" style="cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <span class="error" id="confirm_password-msg"></span>
            </div>
        </div>
        <footer class="panel-footer">
            <div class="text-right">
                <button class="btn btn-default mr-xs" id="userPassUpdate"
                    data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing"><?= translate('update'); ?></button>
                <button class="btn btn-default modal-dismiss"><?= translate('close'); ?></button>
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
            $('#current_password').val("");
            $('#new_password').val("");
            $('#confirm_password').val("");
            $('.error').html('').removeClass('text-danger');
            $('.form-group').removeClass('has-error');
            mfp_modal('#authentication_modal');
        });

        // user authentication update submit via AJAX
        $('#userPassUpdate').on('click', function() {
            var btn = $(this);
            var current_password = $('#current_password').val();
            var new_password = $('#new_password').val();
            var confirm_password = $('#confirm_password').val();
            
            // Reset error messages and styles
            $('.error').html('').removeClass('text-danger');
            $('.form-group').removeClass('has-error');

            $.ajax({
                url: "<?= base_url('profile/' . route_hash('change_password')) ?>",
                type: "POST",
                data: {
                    current_password: current_password,
                    new_password: new_password,
                    confirm_password: confirm_password
                },
                dataType: "json",
                beforeSend: function() {
                    btn.button('loading');
                },
                success: function(data) {
                    if (data.status == 'success') {
                        window.location.reload();
                    } else {
                        if (data.errors) {
                            $.each(data.errors, function(key, val) {
                                if (val) {
                                    $('#' + key + '-msg').html(val).addClass('text-danger');
                                    $('#' + key).closest('.form-group').addClass('has-error');
                                }
                            });
                        }
                    }
                },
                complete: function() {
                    btn.button('reset');
                }
            });
        });
    });
</script>
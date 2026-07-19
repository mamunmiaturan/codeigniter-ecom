<header class="header">
    <div class="logo-env" style="display:flex; align-items:center;">
        <a href="<?php echo base_url('dashboard'); ?>" class="logo" style="text-align:left; flex:0 0 auto; white-space:nowrap; padding-left:15px; width:auto; min-width:0;">
            <img src="<?php echo get_logo_url(); ?>" alt="Logo" class="logo-default">
            <img src="<?php echo asset_ver('uploads/app_image/logo-white.png'); ?>" alt="Logo" class="logo-darkmode">
        </a>
        <div class="sidebar-toggle" data-toggle-class="sidebar-left-collapsed" data-target="html" data-fire-event="sidebar-left-toggle" style="padding-left:14px; padding-right:8px; cursor:pointer; flex:0 0 auto;">
            <i class="fa fa-bars" style="color:#ffffff; font-size:18px; display:block; line-height:1;"></i>
        </div>
    </div>

    <div class="header-left"></div>

    <div class="header-right">
        <ul class="header-menu">
            <!-- Visit Storefront -->
            <li class="header-menu-item">
                <a href="<?php echo base_url('/'); ?>" target="_blank" rel="noopener" title="<?php echo translate('visit_store') ?: 'Visit Store'; ?>"
                   style="display:inline-flex; align-items:center; color:#fff;">
                    <i class="fas fa-store" style="color:#ffffff; font-size:18px;"></i>
                </a>
            </li>
            <!-- Dark Mode Toggle -->
            <li class="header-menu-item">
                <a href="javascript:void(0)" onclick="toggleDarkMode()" id="darkModeToggle" title="Toggle Theme">
                    <i class="fas <?= (($theme_config['dark_skin'] ?? 'false') == 'true' ? 'fa-sun' : 'fa-moon') ?>" style="color: #ffffff; font-size: 18px;"></i>
                </a>
            </li>
            <!-- Language Switcher -->
            <li class="dropdown language-switcher">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-language" style="color: #ffffff; font-size: 18px;"></i>
                    <span class="hidden-xs" style="color: #ffffff; margin-left: 5px;"></span>
                </a>
                <ul class="dropdown-menu">
                    <?php
                    $set_lang = $this->session->userdata('set_lang');
                    $languages = $this->db->get_where('language_list', array('status' => "Active"))->result();
                    foreach ($languages as $lang):
                        $lang_code = (!empty($lang->code) ? $lang->code : strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $lang->name)));
                        $is_active = ($set_lang == $lang_code);
                    ?>
                        <li class="<?= $is_active ? 'active-lang' : '' ?>">
                            <a href="<?= base_url('language/' . route_hash('set_language') . '/' . $lang_code) ?>" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                <span>
                                    <img src="<?= $this->app_lib->get_lang_image_url($lang->id) ?>" alt="" role="presentation" width="16" style="margin-right: 8px;">
                                    <?= ucfirst($lang->name) ?>
                                </span>
                                <?php if ($is_active) { ?>
                                    <i class="fas fa-check-circle" style="color: #5956ea; font-size: 12px; margin-left: 10px;"></i>
                                <?php } ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <!-- Active Users Online Dropdown (Superman & Admin only) -->
            <?php if (in_array(loggedin_role_id(), [ROLE_SUPERMAN_ID, ROLE_ADMIN_ID])): ?>
            <li class="dropdown" id="active-users-dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" id="active-users-btn">
                    <i class="fas fa-users" style="color: #ffffff; font-size: 18px;"></i>
                    <span class="badge badge-success" id="active-users-badge-count" style="position: absolute; top: 12px; right: 5px; font-size: 9px; padding: 2px 4px; border-radius: 50%; display: none; background-color: #22c55e;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right header-menubox" style="width: 280px;">
                    <div class="notification-title" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                        <i class="fas fa-circle text-success" style="font-size: 10px; margin-right: 5px; animation: pulse-green 1.5s infinite;"></i>
                        <?php echo translate('active_users'); ?>
                        <span class="pull-right label label-success" id="active-users-text-count" style="background-color: rgba(34, 197, 94, 0.15) !important; color: #22c55e !important; border: 1px solid rgba(34, 197, 94, 0.25) !important;">0 Active</span>
                    </div>
                    <div class="content" style="max-height: 250px; overflow-y: auto; padding: 0;">
                        <ul class="list-unstyled" id="active-users-list-items" style="margin: 0; padding: 0;">
                            <li class="no-active-users-placeholder" style="padding: 15px; text-align: center; color: #8b929a;">
                                <i class="fas fa-spinner fa-spin mr-sm"></i> <?php echo translate('loading'); ?>...
                            </li>
                        </ul>
                    </div>
                </div>
            </li>
            <?php endif; ?>
            <!-- Notification Bell -->
            <li class="dropdown notification-bell" id="notification-bell-dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" id="notification-bell-btn">
                    <i class="fas fa-bell" style="color: #ffffff; font-size: 18px;"></i>
                    <span class="badge badge-danger" id="notification-badge-count" style="position: absolute; top: 12px; right: 5px; font-size: 9px; padding: 2px 4px; border-radius: 50%; display: none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right header-menubox">
                    <div class="notification-title">
                        <i class="fas fa-bell"></i> <?php echo translate('Notifications'); ?>
                        <span class="pull-right label label-default" id="notification-text-count">0 New</span>
                    </div>
                    <div class="content">
                        <ul class="list-unstyled" id="notification-list-items">
                            <li class="no-notifications-placeholder">
                                <?php echo translate('no_new_notifications'); ?>
                            </li>
                        </ul>
                        <div style="border-top: 1px solid #f5f5f5; display: flex; padding: 8px; gap: 6px;">
                            <a href="#" id="mark-notifications-read-btn" class="btn btn-default btn-xs" style="flex: 1; text-align: center; font-weight: 600; font-size: 11px; padding: 5px 0; display: none; margin: 0;"><?php echo translate('Mark_All_Read'); ?></a>
                            <a href="<?php echo base_url('notification'); ?>" class="btn btn-primary btn-xs" style="flex: 1; text-align: center; font-weight: 600; font-size: 11px; padding: 5px 0; background: #5956ea; border-color: #5956ea; color: #fff; margin: 0;"><?php echo translate('View_All'); ?></a>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
        <?php
        $loggerPhoto = $this->session->userdata('logger_photo');
        $userProfileImg = $this->app_lib->get_image_url($loggerPhoto);
        ?>
        <!-- Profile Box -->
        <div id="userbox" class="userbox">
            <a href="#" data-toggle="dropdown" style="display: flex; align-items: center; gap: 8px;">
                <figure class="profile-picture">
                    <img src="<?php echo html_escape($userProfileImg); ?>" alt="user-image" height="35" style="border: 2px solid rgba(255,255,255,0.2);">
                </figure>
                <div class="profile-info hidden-xs">
                    <span class="name" style="color: #fff; font-weight: 600; display: block; line-height: 1.2; font-size: 14px;"><?php echo html_escape($this->session->userdata('name')); ?></span>
                    <span class="role" style="color: rgba(255,255,255,0.7); font-size: 11px;"><?php echo ucfirst(loggedin_role_name() ?? 'User'); ?></span>
                </div>
                <i class="fa custom-caret"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right header-menubox" style="min-width: 240px; padding: 0;">
                <div class="user-header" style="background: var(--primary-color, #5956ea); padding: 20px; text-align: center; color: #fff;">
                    <img src="<?php echo html_escape($userProfileImg); ?>" alt="user" class="img-circle" style="width: 70px; height: 70px; border: 3px solid rgba(255,255,255,0.3); margin-bottom: 10px;">
                    <h4 style="margin: 0; font-weight: 600; font-size: 18px;"><?php echo html_escape($this->session->userdata('name')); ?></h4>
                    <p style="margin: 0; font-size: 12px; opacity: 0.8;"><?php echo ucfirst(loggedin_role_name() ?? 'User'); ?></p>
                </div>
                <ul class="dropdown-user list-unstyled" style="padding: 10px 0;">
                    <li>
                        <a href="<?php echo base_url('profile/' . route_hash('index') . '/' . get_loggedin_user_id()); ?>" style="padding: 10px 20px; display: block;">
                            <i class="fas fa-user-circle mr-sm"></i> <?php echo translate('my_profile'); ?>
                        </a>
                    </li>
                    <?php if ($this->session->has_userdata('previous_session')) { ?>
                        <li>
                            <a href="<?php echo base_url('authentication/' . route_hash('restore_previous_session')); ?>" style="padding: 10px 20px; display: block;">
                                <i class="fas fa-undo mr-sm"></i> <?php echo translate('back_to_admin'); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <li>
                        <a href="<?php echo base_url('profile/' . route_hash('password')); ?>" style="padding: 10px 20px; display: block;">
                            <i class="fas fa-key mr-sm"></i> <?php echo translate('change_password'); ?>
                        </a>
                    </li>
                    <?php if (get_permission('global_setting', 'is_view')) { ?>
                        <li>
                            <a href="<?php echo base_url('settings'); ?>" style="padding: 10px 20px; display: block;">
                                <i class="fas fa-cog mr-sm"></i> <?php echo translate('system_settings'); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <li class="divider" style="margin: 5px 0;"></li>
                    <li>
                        <a href="#" onclick="confirmLogout(event)" style="padding: 10px 20px; display: block; color: #d9534f; font-weight: 600;">
                            <i class="fas fa-sign-out-alt mr-sm"></i> <?php echo translate('logout'); ?>
                        </a>
                    </li>
                    <script>
                    function confirmLogout(e){
                        e.preventDefault();
                        swal({
                            title: '<?php echo translate('are_you_sure'); ?>',
                            text: '<?php echo translate('logout'); ?>?',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonClass: 'btn swal2-btn-default swal-confirm-primary',
                            cancelButtonClass: 'btn btn-default swal2-btn-default',
                            cancelButtonText: '<?php echo translate('cancel'); ?>',
                            confirmButtonText: '<?php echo translate('yes_continue'); ?>',
                            reverseButtons: true,
                            buttonsStyling: false
                        }).then(function(result){
                            if(result.value) window.location.href='<?php echo base_url('authentication/' . route_hash('logout')); ?>';
                        });
                    }
                    </script>
                    <style>
                    .swal-confirm-primary {
                        background-color: var(--primary-color) !important;
                        border-color: var(--primary-color) !important;
                        color: #fff !important;
                    }
                    .swal-confirm-primary:hover {
                        filter: brightness(0.9);
                    }
                    </style>
                </ul>
            </div>
        </div>
    </div>

</header>
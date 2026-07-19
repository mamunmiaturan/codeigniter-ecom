<head>
	<meta charset="UTF-8">
	<script>
	/* Restore the persisted mini (collapsed) sidebar state before the page paints,
	   so navigating between pages keeps the collapsed sidebar instead of resetting. */
	(function(){try{if(localStorage.getItem('sidebar-left-collapsed')==='1'){document.documentElement.className+=' sidebar-left-collapsed';}}catch(e){}})();
	</script>
	<title><?php echo html_escape($title . " - " . get_site_name()); ?></title>
	<meta name="keywords" content="">
	<meta name="description" content="">
	<meta name="author" content="Mamun Mia Turan">
	<!-- Favicons -->
	<?php
	$favicon = get_global_setting('favicon');
	if (empty($favicon)) $favicon = 'favicon.png';
	$favicon_path = 'uploads/app_image/' . $favicon;
	$favicon_url = base_url($favicon_path . '?v=' . time());
	if (!file_exists(FCPATH . $favicon_path)) {
		$favicon_url = get_logo_url();
	}
	?>
	<link rel="icon" href="<?php echo $favicon_url; ?>">
	<link rel="apple-touch-icon" href="<?php echo $favicon_url; ?>">
	<link rel="shortcut icon" href="<?php echo $favicon_url; ?>">
	<!-- Mobile Metas -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

	<!-- Web Fonts  -->
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Poppins:wght@300;400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

	<!-- Vendor -->
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/bootstrap/css/bootstrap.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/font-awesome/css/all.min.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/select2/css/select2.min.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/select2-bootstrap-theme/select2-bootstrap.min.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.min.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/daterangepicker/daterangepicker.css'); ?>">
	<!-- Jquery Datatables CSS -->
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/datatables/media/css/dataTables.bootstrap.min.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/magnific-popup/magnific-popup.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/dropify/css/dropify.min.css'); ?>">
	<!-- Theme Custom CSS -->
	<link rel="stylesheet" href="<?php echo asset_ver('assets/backend/css/custom-style.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/css/skins/default.css'); ?>">
	<!-- Sweetalert CSS -->
	<link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/sweetalert/sweetalert-custom.css'); ?>">
	<!-- Custom CSS -->
	<link rel="stylesheet" href="<?php echo asset_ver('assets/backend/css/app.css'); ?>">
	<!-- Jquery JS-->
	<script src="<?php echo base_url('assets/backend/vendor/jquery/jquery.js'); ?>"></script>
	<!-- Pusher JS -->
	<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>

	<?php
	if (isset($headerelements)) {
		foreach ($headerelements as $type => $element) {
			if ($type == 'css') {
				if (count($element)) {
					foreach ($element as $keycss => $css) {
						echo '<link rel="stylesheet" href="' . base_url('assets/' . $css) . '">' . "\n";
					}
				}
			} elseif ($type == 'js') {
				if (count($element)) {
					foreach ($element as $keyjs => $js) {
						echo '<script type="text/javascript" src="' . base_url('assets/' . $js) . '"></script>' . "\n";
					}
				}
			}
		}
	}
	?>

	<!-- If user have enabled CSRF proctection this function will take care of the ajax requests and append custom header for CSRF -->
	<script type="text/javascript">
		var base_url = '<?php echo base_url(); ?>';
		var theme_mode = '<?php echo (($theme_config['dark_skin'] ?? 'false') == 'true' ? 'true' : 'false'); ?>';
		var csrfData = <?php echo json_encode(csrf_jquery_token()); ?>;
		$(function($) {
			$.ajaxSetup({ data: csrfData });

			// Auto-sync CSRF token from every JSON response that returns a csrf field.
			// Covers all controllers that use jsonResponse() or return {csrf:{name:hash}}.
			$(document).ajaxSuccess(function(event, xhr, settings) {
				try {
					var res = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
					if (res && res.csrf && typeof res.csrf === 'object') {
						csrfData = res.csrf;
						$.ajaxSetup({ data: csrfData });
						var name = Object.keys(res.csrf)[0];
						var val  = res.csrf[name];
						$('input[name="' + name + '"]').val(val);
					}
				} catch(e) {}
			});
		});

		// Pusher Real-time Initialization
		<?php if (getenv('PUSHER_ENABLED') === 'true'): ?>
			var pusher = new Pusher('<?= getenv('PUSHER_APP_KEY') ?>', {
				cluster: '<?= getenv('PUSHER_APP_CLUSTER') ?>'
			});
			var channel = pusher.subscribe('activity-channel');
			channel.bind('new-log', function(data) {
				if (typeof showLiveLog === 'function') {
					showLiveLog(data);
				}
			});
			channel.bind('online-users-updated', function(data) {
				if (typeof refreshActiveUsers === 'function') {
					refreshActiveUsers();
				}
			});
		<?php endif; ?>

		<?php if (is_loggedin() && in_array(loggedin_role_id(), [ROLE_SUPERMAN_ID, ROLE_ADMIN_ID])): ?>
			// Active Users Online Global Tracking (Superman & Admin only)
			function escapeHtml(text) {
				if (!text) return '';
				return text
					.toString()
					.replace(/&/g, "&amp;")
					.replace(/</g, "&lt;")
					.replace(/>/g, "&gt;")
					.replace(/"/g, "&quot;")
					.replace(/'/g, "&#039;");
			}

			function refreshActiveUsers() {
				$.ajax({
					url: "<?= base_url('ajax/heartbeat') ?>",
					type: "POST",
					data: csrfData,
					dataType: 'json',
					success: function(response) {
						if (response.status === 'success') {
							updateActiveUsersUI(response.active_users, response.count, response.unread_count);

						}
					}
				});
			}

			function updateActiveUsersUI(users, count, unreadCount = 0) {
				var $badge = $('#active-users-badge-count');
				var $textCount = $('#active-users-text-count');

				if ($textCount.length) {
					$textCount.text(count + ' Active');
				}

				if ($badge.length) {
					if (count > 0) {
						$badge.css('background-color', '#22c55e').text(count).show();
					} else {
						$badge.hide();
					}
				}

				var $list = $('#active-users-list-items');
				if ($list.length) {
					$list.empty();

					if (users.length === 0) {
						$list.append('<li class="no-active-users-placeholder" style="padding: 15px; text-align: center; color: #8b929a;">No active users online.</li>');
						return;
					}

					users.forEach(function(user) {
						var lastMsgHtml = `<div style="font-size: 10.5px; color: #8b929a; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 2px;">${escapeHtml(user.role_name)}</div>`;

						var timeHtml = '';
						if (user.last_message_time) {
							timeHtml = `<div style="font-size: 10px; color: #b1b5c4; margin-left: auto; flex-shrink: 0; align-self: flex-start; margin-top: 2px;">${escapeHtml(user.last_message_time)}</div>`;
						}

						var clickClass = '';
						var cursorStyle = 'cursor: default;';

						var item = `
						<li class="${clickClass}" data-id="${user.cred_id}" data-name="${user.name}" data-photo="${user.photo}" style="border-bottom: 1px solid rgba(0, 0, 0, 0.04); display: flex; align-items: center; padding: 10px 15px; gap: 10px; ${cursorStyle} transition: background-color 0.2s;">
							<div style="position: relative; flex-shrink: 0;">
								<img src="${user.photo}" alt="avatar" class="img-circle" style="width: 32px; height: 32px; border: 1.5px solid rgba(0,0,0,0.1); object-fit: cover;">
								<span class="active-dot" style="position: absolute; bottom: 0; right: 0; width: 9px; height: 9px; background: #22c55e; border: 1.5px solid #fff; border-radius: 50%;"></span>
							</div>
							<div style="flex: 1; min-width: 0; text-align: left;">
								<div style="font-weight: 600; font-size: 13px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;">${user.name}</div>
								${lastMsgHtml}
							</div>
							${timeHtml}
						</li>`;
						$list.append(item);
					});
				}
			}

			$(document).ready(function() {
				refreshActiveUsers();
				// Send heartbeat every 20 seconds
				setInterval(refreshActiveUsers, 20000);
			});
		<?php endif; ?>



		function toggleDarkMode() {
			var $html = $('html');
			var $icon = $('#darkModeToggle i');
			var isDark = $html.hasClass('dark');

			if (isDark) {
				$html.removeClass('dark').addClass('sidebar-light');
				$icon.removeClass('fa-sun').addClass('fa-moon');
			} else {
				$html.removeClass('sidebar-light').addClass('dark');
				$icon.removeClass('fa-moon').addClass('fa-sun');
			}

			$.ajax({
				url: "<?= base_url('settings/' . route_hash('toggle_theme')) ?>",
				type: "POST",
				data: csrfData,
				success: function(data) {
					// Successfully synced background and JSON caches
				},
				error: function() {
					// Safe fallback
					window.location.reload();
				}
			});
		}
	</script>

	<?php if (($theme_config['border_mode'] ?? 'true') == 'false'): ?>
		<link rel="stylesheet" href="<?php echo base_url('assets/backend/css/skins/square-borders.css'); ?>">
	<?php endif; ?>

	<?php $this->load->view('layout/dynamic'); ?>
</head>
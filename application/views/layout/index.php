<!doctype html>
<?php
$rtl_languages = ['arabic', 'hebrew', 'urdu', 'persian', 'farsi'];
$current_lang = $this->session->userdata('set_lang') ?: 'english';
$is_rtl = in_array(strtolower($current_lang), $rtl_languages);
$is_auth = in_array(strtolower($this->router->fetch_class()), ['authentication', 'register']);
?>
<html class="fixed <?php echo $is_auth ? '' : 'sidebar-left-sm'; ?> <?php echo (isset($theme_config['dark_skin']) && $theme_config['dark_skin'] == 'true' ? 'dark' : ($is_auth ? '' : 'sidebar-light')); ?>" <?php echo $is_rtl ? 'dir="rtl"' : ''; ?>>

<!-- Html Header -->
<?php $this->load->view('layout/header'); ?>

<!-- Html Body -->

<body>
<?php if ($is_auth): ?>
<link rel="stylesheet" href="<?php echo asset_ver('assets/backend/css/auth.css'); ?>">
<?php
// Resolve the primary colour for the auth (login/register/…) pages. When no user
// is logged in, $theme_config may be unset, so fall back to the saved GLOBAL theme
// (theme_settings id=1) before the hardcoded default — so the login reflects the
// configured brand/theme colour, not a stray purple.
$__auth_pc = ($theme_config['primary_color'] ?? '') ?: ((function_exists('get_theme_setting_row') && ($__g = get_theme_setting_row()) && !empty($__g['primary_color'])) ? $__g['primary_color'] : '#5956ea');
?>
<style>
.auth-wrapper,
.auth-wrapper .input-group,
.auth-wrapper .input-group:focus-within,
.auth-wrapper .icon-box,
.auth-wrapper .password-toggle:hover,
.auth-wrapper .forgot-pass,
.auth-wrapper .remember-check input {
    --primary: <?php echo $__auth_pc; ?>;
}
.auth-wrapper .auth-alt a { color: <?php echo $__auth_pc; ?> !important; }
.auth-wrapper .input-group:focus-within {
    border-color: <?php echo $__auth_pc; ?> !important;
}
.auth-wrapper .icon-box {
    color: <?php echo $__auth_pc; ?> !important;
}
.auth-wrapper .forgot-pass {
    color: <?php echo $__auth_pc; ?> !important;
}
.auth-wrapper .remember-check input {
    accent-color: <?php echo $__auth_pc; ?> !important;
}
.auth-wrapper .btn-submit {
    background: <?php echo $__auth_pc; ?> !important;
}
.auth-wrapper .btn-submit:hover {
    background: <?php echo $__auth_pc; ?> !important;
    filter: brightness(0.9);
}
body {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 100vh !important;
    background-color: #f0f3f8 !important;
    margin: 0 !important;
    padding: 0 !important;
}
</style>
	<?php $this->load->view($sub_page); ?>
</body>
</html>
<?php return; endif; ?>
	<!-- Preloader removed: the page now paints as soon as its CSS is ready and
	     fills in progressively, instead of hiding everything behind a full-screen
	     overlay until all assets/images finish downloading. -->
	<section class="body">
		<!-- Top Navbar -->
		<?php $this->load->view('layout/navbar'); ?>
		<div class="inner-wrapper">
			<!-- Sidebar -->
			<?php $this->load->view('layout/sidebar'); ?>
			<!-- Page Main Content -->
			<section role="main" class="content-body">
				<header class="page-header">
					<a class="page-title-icon" href="<?php echo base_url('dashboard'); ?>"><i class="fas fa-home"></i></a>
					<h2><?php echo $title; ?></h2>
				</header>
				<?php $this->load->view($sub_page); ?>
			</section>
		</div>
	</section>

	<!-- JS Script -->
	<?php $this->load->view('layout/script', array('load_pdfmake' => !empty($load_pdfmake))); ?>

	<?php
	$alert_success = $this->session->userdata('alert-message-success');
	$alert_error = $this->session->userdata('alert-message-error');
	$alert_info = $this->session->userdata('alert-message-info');

	$alertclass = "";
	$alert_message = "";

	if ($alert_success !== NULL) {
		$alertclass = "success";
		$alert_message = $alert_success;
		$this->session->unset_userdata('alert-message-success');
	} else if ($alert_error !== NULL) {
		$alertclass = "error";
		$alert_message = $alert_error;
		$this->session->unset_userdata('alert-message-error');
	} else if ($alert_info !== NULL) {
		$alertclass = "info";
		$alert_message = $alert_info;
		$this->session->unset_userdata('alert-message-info');
	}

	if ($alertclass != ''):
	?>
		<script type="text/javascript">
			// sweetalert.min.js is loaded with `defer`, so it isn't available while
			// this inline script parses. Run the toast on DOMContentLoaded — deferred
			// scripts are guaranteed to have executed by then, so swal() is defined.
			document.addEventListener('DOMContentLoaded', function () {
				swal({
					toast: true,
					position: 'top-end',
					type: '<?php echo $alertclass; ?>',
					title: '<?php echo $alert_message; ?>',
					showConfirmButton: false,
					buttonsStyling: false,
					timer: 3000
				});
			});
		</script>
	<?php endif; ?>

	<!-- CSRF token store -->
	<script type="text/javascript">
		/**
		 * Single source of truth for the CSRF token in JS.
		 *
		 * csrf_regenerate is ON, so the token rotates on every POST — the value
		 * rendered into the page at load is stale as soon as anything posts (the
		 * DataTables handlers post on page load). Every such handler returns the
		 * new token as `csrfHash`, so refresh from any JSON response that carries
		 * one. The CSRF cookie is HttpOnly and cannot be read from JS.
		 */
		window.CsrfToken = {
			name: '<?php echo $this->security->get_csrf_token_name(); ?>',
			hash: '<?php echo $this->security->get_csrf_hash(); ?>',
			pair: function () {
				var d = {};
				d[this.name] = this.hash;
				return d;
			}
		};
		$(document).ajaxSuccess(function (event, xhr, settings, data) {
			if (data && data.csrfHash) {
				window.CsrfToken.hash = data.csrfHash;
				$('meta[name="csrf-token"]').attr('content', data.csrfHash);
			}
		});
	</script>

	<!-- Sweetalert -->
	<script type="text/javascript">
		function confirm_modal(delete_url) {
			swal({
				title: "<?php echo translate('are_you_sure'); ?>",
				text: "<?php echo translate('delete_this_information'); ?>",
				type: "warning",
				showCancelButton: true,
				confirmButtonClass: "btn btn-default swal2-btn-default",
				cancelButtonClass: "btn btn-default swal2-btn-default",
				confirmButtonText: "<?php echo translate('yes_continue'); ?>",
				cancelButtonText: "<?php echo translate('cancel'); ?>",
				buttonsStyling: false,
				footer: "<?php echo translate('deleted_note'); ?>"
			}).then((result) => {
				if (result.value) {
					// POST, never GET: these endpoints mutate. A GET delete is
					// triggerable by any crawler, prefetcher or <img> tag.
					$.ajax({
						url: delete_url,
						type: 'POST',
						data: window.CsrfToken.pair(),
						success: function(data) {
							swal({
								title: "<?php echo translate('deleted'); ?>",
								text: "<?php echo translate('information_deleted'); ?>",
								buttonsStyling: false,
								showCloseButton: true,
								focusConfirm: false,
								confirmButtonClass: "btn btn-default swal2-btn-default",
								type: "success"
							}).then((result) => {
								if (result.value) {
									window.location.reload(true);
								}
							});
						}
					});
				}
			});
		}
	</script>

	<!-- Notifications JS Polling & Real-time Popup Configuration -->
	<script type="text/javascript">
		window.NotificationConfig = {
			isLoggedIn: <?php echo is_loggedin() ? 'true' : 'false'; ?>,
			getUnreadUrl: '<?php echo base_url("notification/get_unread_notifications"); ?>',
			markAllReadUrl: '<?php echo base_url("notification/mark_notifications_as_read"); ?>',
			markSingleReadUrl: '<?php echo base_url("notification/mark_single_as_read/"); ?>',
			readTranslation: '<?php echo translate("read"); ?>',
			noNewTranslation: '<?php echo translate("no_new_notifications"); ?>',
			baseUrl: '<?php echo base_url("notification"); ?>'
		};
	</script>
	<script src="<?php echo base_url('assets/backend/js/notifications.js'); ?>" type="text/javascript"></script>

	<!-- Jquery Datatables JS -->
</body>

</html>
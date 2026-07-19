<!-- Vendor -->
<script defer src="<?php echo base_url('assets/backend/vendor/jquery-browser-mobile/jquery.browser.mobile.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/jquery-ui/jquery-ui.min.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/bootstrap/js/bootstrap.min.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/nanoscroller/nanoscroller.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/jquery-placeholder/jquery-placeholder.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/select2/js/select2.min.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/dropify/js/dropify.min.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/modernizr/modernizr.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/moment/moment.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/daterangepicker/daterangepicker.js'); ?>"></script>

<!-- Jquery Datatables JS -->
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/media/js/jquery.dataTables.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/media/js/dataTables.bootstrap.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/dataTables.buttons.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/buttons.bootstrap.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/buttons.html5.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/buttons.print.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/buttons.colVis.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/JSZip-2.5.0/jszip.min.js');?>"></script>
<?php /* pdfmake requires CSP unsafe-eval; load only on pages that opt in via $load_pdfmake = true */ ?>
<?php if (!empty($load_pdfmake)): ?>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/pdfmake-0.1.32/pdfmake.min.js');?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/datatables/extras/TableTools/pdfmake-0.1.32/vfs_fonts.js');?>"></script>
<?php endif; ?>

<!-- Magnific Popup -->
<script defer src="<?php echo base_url('assets/backend/vendor/jquery-appear/jquery-appear.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/jquery-validation/jquery.validate.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/magnific-popup/jquery.magnific-popup.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/screenfull/screenfull.min.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/vendor/sweetalert/sweetalert.min.js'); ?>"></script>

<!-- Components and Setting -->
<script defer src="<?php echo base_url('assets/backend/js/custom.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/js/plug.init.js'); ?>"></script>
<script defer src="<?php echo base_url('assets/backend/js/app.js'); ?>"></script>

<!-- i18n strings for validator-i18n.js (type=application/json is not executed — CSP safe) -->
<script id="js-i18n" type="application/json">
{"required":"<?php echo addslashes(translate('this_value_is_required')); ?>","email":"<?php echo addslashes(translate('enter_valid_email')); ?>"}
</script>
<script defer src="<?php echo base_url('assets/backend/js/app/validator-i18n.js'); ?>"></script>

<!-- CSRF ajax wiring — reads from <meta name="csrf-*"> injected by MY_Controller::_output() -->
<script defer src="<?php echo base_url('assets/backend/js/app/csrf-ajax.js'); ?>"></script>

<!-- App route config for command palette (type=application/json — CSP safe) -->
<script id="js-app-routes" type="application/json">
{"dashboard":"<?php echo base_url('dashboard'); ?>","logout":"<?php echo base_url('authentication/' . route_hash('logout')); ?>"}
</script>
<link rel="stylesheet" href="<?php echo base_url('assets/backend/css/cmd-palette.css'); ?>">
<script defer src="<?php echo base_url('assets/backend/js/app/cmd-palette.js'); ?>"></script>

<script>
/* Persist the mini (collapsed) sidebar state whenever it is toggled, so it
   survives page navigation. The matching restore script runs early in <head>. */
document.addEventListener('DOMContentLoaded', function () {
	if (!window.jQuery) return;
	jQuery(window).on('sidebar-left-toggle', function () {
		try {
			if (document.documentElement.classList.contains('sidebar-left-collapsed')) {
				localStorage.setItem('sidebar-left-collapsed', '1');
			} else {
				localStorage.removeItem('sidebar-left-collapsed');
			}
		} catch (e) {}
	});

	// Global tooltip init (delegated) — covers time_ago() spans and any
	// [data-toggle="tooltip"] element, including rows added later by DataTables.
	try {
		$('body').tooltip({ selector: '[data-toggle="tooltip"]', container: 'body', trigger: 'hover' });
	} catch (e) {}
});
</script>

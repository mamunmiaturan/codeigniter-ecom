<!-- Dynamic Theme Styles -->
<style type="text/css">
	<?php
	$font_family = $theme_config['font_family'] ?? 'System Default';
	$font_stack = "";
	switch ($font_family) {
		case 'Inter':
			$font_stack = "'Inter', sans-serif";
			break;
		case 'Roboto':
			$font_stack = "'Roboto', sans-serif";
			break;
		case 'Poppins':
			$font_stack = "'Poppins', sans-serif";
			break;
		default:
			$font_stack = "inherit";
			break;
	}
	?>:root {
		--primary-color: <?php echo $theme_config['primary_color'] ?? '#007bff'; ?>;
		--secondary-color: <?php echo $theme_config['secondary_color'] ?? '#6c757d'; ?>;
		--sidebar-bg: <?php echo $theme_config['sidebar_color'] ?? '#ffffff'; ?>;
		--sidebar-text: <?php echo $theme_config['sidebar_text_color'] ?? '#007bff'; ?>;
		--navbar-bg: <?php echo $theme_config['navbar_color'] ?? '#ffffff'; ?>;
		--navbar-text: <?php echo $theme_config['navbar_text_color'] ?? '#333333'; ?>;
		--font-family: <?php echo $font_stack; ?>;
		--font-size: <?php
			$__fs = (string) ($theme_config['font_size'] ?? 'medium');
			$__fsmap = ['extra_small' => '12px', 'small' => '13px', 'medium' => '14px', 'large' => '16px', 'extra_large' => '18px'];
			if (isset($__fsmap[$__fs])) { echo $__fsmap[$__fs]; }
			elseif (preg_match('/^\d{1,2}(\.\d+)?px$/', $__fs)) { echo $__fs; }
			elseif (is_numeric($__fs)) { echo max(8, min(30, (float) $__fs)) . 'px'; }
			else { echo '14px'; }
		?>;
	}

	html.dark {
		--sidebar-bg: #171717 !important;
		--sidebar-text: #ffffff !important;
		--navbar-bg: #171717 !important;
		--navbar-text: #a2a5b9 !important;
	}

	html.dark .header,
	html.dark .header .logo-env {
		background: #171717 !important;
		border-bottom: 1px solid #232330 !important;
	}

	html {
		font-size: var(--font-size) !important;
	}

	body,
	.content-body,
	.panel,
	table.dataTable,
	.form-control,
	.nav-main li a {
		font-size: var(--font-size) !important;
	}

	/* Toaster + SweetAlert dialogs always render at a fixed, STANDARD size.
	   SweetAlert sizes everything in rem/em relative to the root <html>
	   font-size, so the theme font-size setting (meant for main content +
	   sidebar) was scaling notifications too. Pin their base font-size so they
	   never grow/shrink with the content font-size. */
	.swal2-container,
	.swal2-popup {
		font-size: 16px !important;
	}
	.swal2-popup.swal2-toast {
		font-size: 14px !important;
	}

	/* Buttons scale WITH the font-size setting (part of the main UI) — except
	   SweetAlert's own buttons, which stay standard inside the pinned dialog. */
	.btn:not(.swal2-styled) {
		font-size: var(--font-size) !important;
	}

	/* Navbar user dropdown stays a STANDARD size (not scaled by font-size). */
	.header-menubox,
	.header-menubox a,
	.dropdown-user > li > a {
		font-size: 14px !important;
	}

	body {
		font-family: var(--font-family) !important;
	}

	.btn-primary,
	.btn-success,
	.btn-info,
	.panel-highlight .panel-heading,
	.tabs-custom .nav-tabs>li.active>a,
	.tabs-custom .nav-tabs>li.active>a:hover,
	.tabs-custom .nav-tabs>li.active>a:focus,
	.sidebar-left .nav-main>li.nav-active>a,
	.sidebar-left .nav-main>li.nav-active>a:hover {
		background-color: var(--primary-color) !important;
		border-color: var(--primary-color) !important;
		color: #ffffff !important;
	}

	/* Dynamic Secondary & Default Button Styles */
	.btn-secondary,
	.btn-default {
		background-color: var(--secondary-color) !important;
		border-color: var(--secondary-color) !important;
		color: #ffffff !important;
	}

	.btn-secondary:hover,
	.btn-secondary:focus,
	.btn-secondary:active,
	.btn-secondary.active,
	.btn-default:hover,
	.btn-default:focus,
	.btn-default:active,
	.btn-default.active {
		background-color: var(--secondary-color) !important;
		border-color: var(--secondary-color) !important;
		opacity: 0.9 !important;
		color: #ffffff !important;
	}

	/* DataTables Export Buttons styling using Secondary Color */
	.dt-buttons .btn,
	.dt-buttons .btn-secondary {
		background-color: var(--secondary-color) !important;
		border-color: var(--secondary-color) !important;
		color: #ffffff !important;
		border-bottom: 1px solid var(--secondary-color) !important;
		box-shadow: none !important;
		margin: 5px !important;
		padding: 5px 5px !important;
		border-radius: 4px !important;
		transition: all 0.2s ease-in-out !important;
	}

	.dt-buttons .btn:hover,
	.dt-buttons .btn-secondary:hover,
	.dt-buttons .btn:focus,
	.dt-buttons .btn-secondary:focus {
		opacity: 0.9 !important;
		background-color: var(--secondary-color) !important;
		border-color: var(--secondary-color) !important;
		color: #ffffff !important;
	}

	/* Page title: the base theme sizes this at 2rem (~28px) and floats the icon
	   and <h2> separately, so they never line up. Make the header a flex row so
	   the home icon + title are vertically centred, and size the title to match
	   the sidebar/nav font exactly (var(--font-size)). */
	.page-header {
		display: flex !important;
		align-items: center !important;
		gap: 6px;
	}
	.page-header h2 {
		color: var(--secondary-color) !important;
		background: none !important;
		border: none !important;
		font-size: var(--font-size) !important;
		height: auto !important;
		line-height: 1.4 !important;
		float: none !important;
		margin: 0 !important;
		padding: 0 6px !important;
	}
	.page-header .page-title-icon {
		float: none !important;
		margin: 0 !important;
	}

	.page-header .breadcrumbs a,
	.page-header .breadcrumbs span,
	.page-header .breadcrumbs i,
	.page-title-icon i {
		color: var(--primary-color) !important;
		background: none !important;
		border: none !important;
	}

	/* Sidebar parent "+" / "–" expand indicator. The base theme top-aligns it
	   (so it never lines up with the label) and hard-codes a purple colour.
	   Vertically centre it and paint it with the theme colour... */
	.sidebar-left ul.nav-main li.nav-parent > a:after {
		top: 50% !important;
		transform: translateY(-50%) !important;
		padding: 0 22px !important;
		color: var(--primary-color) !important;
	}

	/* The active/expanded sidebar row sits on the primary-colour background, so
	   its ICON, label, and the "+"/"–" indicator must ALL be pure white or they
	   blend into the background and vanish. The theme tints the active icon via
	   ID-scoped rules (#sidebar-left ...), so this override is ALSO ID-scoped —
	   a class selector can't outrank them. Covers every Font Awesome variant. */
	#sidebar-left .nav-main > li.nav-active > a,
	#sidebar-left .nav-main > li.nav-active > a i,
	#sidebar-left .nav-main > li.nav-active > a .fa,
	#sidebar-left .nav-main > li.nav-active > a .fas,
	#sidebar-left .nav-main > li.nav-active > a .far,
	#sidebar-left .nav-main > li.nav-active > a .fab,
	#sidebar-left .nav-main > li.nav-active > a:after,
	#sidebar-left .nav-main > li.nav-expanded > a,
	#sidebar-left .nav-main > li.nav-expanded > a i,
	#sidebar-left .nav-main > li.nav-expanded > a .fa,
	#sidebar-left .nav-main > li.nav-expanded > a .fas,
	#sidebar-left .nav-main > li.nav-expanded > a .far,
	#sidebar-left .nav-main > li.nav-expanded > a .fab,
	#sidebar-left .nav-main > li.nav-expanded > a:after {
		color: #ffffff !important;
	}

	/* Index-page action buttons ("Add / New / Create") live in the page header
	   or a panel heading. Views mix full .btn with .btn-sm, so they render at
	   inconsistent, oversized dimensions. Normalise every action button to one
	   compact, STANDARD size regardless of the markup used. */
	.page-header .btn,
	.panel-heading .btn,
	.panel-actions .btn,
	.panel-title .btn {
		font-size: 13px !important;
		padding: 6px 14px !important;
		line-height: 1.45 !important;
		border-radius: 4px !important;
	}

	/* ...and always paint them with the theme (primary) colour + white text,
	   so the primary action follows the active theme instead of a hard-coded
	   green/blue. */
	.page-header .btn-primary,
	.page-header .btn-success,
	.panel-heading .btn-primary,
	.panel-heading .btn-success,
	.panel-actions .btn-primary,
	.panel-actions .btn-success,
	.panel-title .btn-primary,
	.panel-title .btn-success {
		background-color: var(--primary-color) !important;
		border-color: var(--primary-color) !important;
		color: #ffffff !important;
	}

	.page-header .btn-primary:hover,
	.page-header .btn-success:hover,
	.panel-heading .btn-primary:hover,
	.panel-heading .btn-success:hover,
	.panel-actions .btn-primary:hover,
	.panel-actions .btn-success:hover,
	.panel-title .btn-primary:hover,
	.panel-title .btn-success:hover {
		background-color: var(--primary-color) !important;
		border-color: var(--primary-color) !important;
		color: #ffffff !important;
		opacity: 0.9 !important;
	}

	.tabs-custom .nav-tabs>li.active>a i {
		color: #ffffff !important;
	}

	aside#sidebar-left,
	.sidebar-left,
	.sidebar-left-wrapper,
	.sidebar-header,
	.sidebar-left .nano,
	.sidebar-left .nano-content,
	.sidebar-left .nav-main .nav-children,
	html.sidebar-light aside#sidebar-left,
	html.sidebar-light .sidebar-left,
	html.dark aside#sidebar-left,
	html.dark .sidebar-left {
		background: var(--sidebar-bg) !important;
	}

	.sidebar-left .nav-main>li>a,
	.sidebar-left .nav-main>li>a i,
	.sidebar-left .nav-main .nav-children>li>a,
	.sidebar-left .nav-main .nav-parent>a:after,
	.sidebar-left .nav-main .nav-children>li>a:before {
		color: var(--navbar-text) !important;
		border-left-color: transparent !important;
	}

	.sidebar-left .nav-main>li>a:hover,
	.sidebar-left .nav-main>li>a:hover i,
	.sidebar-left .nav-main .nav-children>li>a:hover,
	.sidebar-left .nav-main>li.nav-active>a,
	.sidebar-left .nav-main>li.nav-active>a i,
	.sidebar-left .nav-main .nav-children li.nav-active>a {
		color: var(--sidebar-text) !important;
		border-left-color: var(--sidebar-text) !important;
	}

	.sidebar-left .sidebar-header .sidebar-title {
		color: var(--sidebar-text) !important;
		background: var(--sidebar-bg) !important;
	}

	.header {
		background: var(--navbar-bg) !important;
	}

	.header .userbox .name,
	.header .userbox .role,
	.header .sidebar-toggle i {
		color: var(--navbar-text) !important;
	}

	.header .header-right i {
		color: var(--navbar-text) !important;
	}

	/* Sidebar Active State Fix */
	.sidebar-left .nav-main>li.nav-active>a {
		background: rgba(0, 0, 0, 0.1) !important;
		box-shadow: -2px 0 0 var(--primary-color) inset !important;
	}

	.sidebar-left .nav-main li.nav-expanded>a {
		background: rgba(0, 0, 0, 0.05) !important;
	}

	/* Force sub-menu items to not have white backgrounds */
	.sidebar-left .nav-main .nav-children li.nav-active>a {
		background: rgba(0, 0, 0, 0.1) !important;
	}

	/* DataTables search fields and length menus alignment and styling fixes */
	.dataTables_wrapper .table-responsive,
	.dataTables_wrapper table.dataTable {
		margin-top: 5px !important;
	}

	.dataTables_wrapper .dataTables_filter {
		width: 100% !important;
		display: flex !important;
		justify-content: flex-end !important;
	}

	.dataTables_wrapper .dataTables_filter label {
		width: 100% !important;
		max-width: 250px !important;
		margin: 0 !important;
	}

	.dataTables_wrapper .dataTables_filter label input {
		width: 100% !important;
		height: 38px !important;
		display: inline-block !important;
		box-sizing: border-box !important;
		border: 1px solid #ced4da !important;
		border-radius: 0.375rem !important;
		padding: 0.375rem 0.75rem !important;
		background-color: #fff !important;
	}

	html.dark .dataTables_wrapper .dataTables_filter label input {
		background-color: #2b3035 !important;
		border-color: #495057 !important;
		color: #fff !important;
	}

	/* Align Length Menu Selector */
	.dataTables_wrapper .dataTables_length {
		display: flex !important;
		align-items: center !important;
	}

	.dataTables_wrapper .dataTables_length label {
		display: inline-flex !important;
		align-items: center !important;
		gap: 8px !important;
		margin: 0 !important;
	}

	.dataTables_wrapper .dataTables_length select {
		width: auto !important;
		height: 38px !important;
		display: inline-block !important;
		border: 1px solid #ced4da !important;
		border-radius: 0.375rem !important;
		padding: 0.375rem 2.25rem 0.375rem 0.75rem !important;
		background-color: #fff !important;
	}

	html.dark .dataTables_wrapper .dataTables_length select {
		background-color: #2b3035 !important;
		border-color: #495057 !important;
		color: #fff !important;
	}

	/* Adjust tab-pane Datatables spacing when used in panels/tabs */
	.tab-pane .dataTables_wrapper>.row:first-child {
		margin-top: 0px !important;
		margin-bottom: 15px !important;
	}

	/* Dynamic Primary Accent overrides for Dark Mode to respect custom Color Palette */
	html.dark .nav-main li.nav-active>a,
	html.dark .nav-main li.nav-expanded>a {
		color: var(--primary-color) !important;
	}

	html.dark .nav-main li.nav-active>a i,
	html.dark .nav-main li.nav-expanded>a i {
		color: var(--primary-color) !important;
	}

	html.dark .tabs-custom .nav-tabs>li.active>a,
	html.dark .tabs-custom .nav-tabs>li.active>a:hover,
	html.dark .tabs-custom .nav-tabs>li.active>a:focus {
		background: var(--primary-color) !important;
		border-color: var(--primary-color) !important;
		color: #ffffff !important;
	}

	html.dark .nav-tabs li.active a,
	html.dark .nav-tabs li.active a:hover,
	html.dark .nav-tabs li.active a:focus {
		color: var(--primary-color) !important;
	}

	html.dark .btn-primary {
		background-color: var(--primary-color) !important;
		border-color: var(--primary-color) !important;
		color: #ffffff !important;
	}

	html.dark .panel-heading {
		border-bottom-color: var(--primary-color) !important;
	}

	html.dark .page-header h2 {
		color: #ffffff !important;
	}

	html.dark .page-header .breadcrumbs a,
	html.dark .page-header .breadcrumbs span,
	html.dark .page-header .breadcrumbs i,
	html.dark .page-title-icon i {
		color: var(--primary-color) !important;
	}

	html.dark .dataTables_wrapper .dataTables_paginate .paginate_button.active,
	html.dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
		background: var(--primary-color) !important;
		color: #ffffff !important;
		border-color: var(--primary-color) !important;
	}

	/* Dynamic Theme Settings page headers and controls */
	.headers-line {
		color: var(--primary-color) !important;
	}

	html.dark .headers-line {
		color: var(--primary-color) !important;
	}

	/* Dynamic Radio selections */
	.radio-custom.radio-primary input[type="radio"]:checked+label::after {
		background-color: var(--primary-color) !important;
	}

	.radio-custom.radio-primary input[type="radio"]:checked+label::before {
		border-color: var(--primary-color) !important;
	}

	/* Compact Sidebar Menu & Submenu Gap Reduction */
	.sidebar-left .nav-main>li>a {
		padding: 8px 25px !important;
	}

	.sidebar-left .nav-main .nav-children>li>a {
		padding: 4px 15px 4px 34px !important;
	}

	.sidebar-left .nav-main .nav-children {
		padding: 4px 0 !important;
	}
</style>
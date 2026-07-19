<?php
$active_tab = $this->session->flashdata('active');
$this->load->helper('theme');
?>
<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li <?php echo (empty($active_tab) || $active_tab == 1 ? "class='active'" : ''); ?>>
				<a href="#general" data-toggle="tab">
					<i class="fas fa-chalkboard-teacher"></i>
					<span class="hidden-xs"> <?php echo translate('general') . " " . translate('setting'); ?></span>
				</a>
			</li>

			<li <?php echo ($active_tab == 2 ? "class='active'" : ''); ?>>
				<a href="#upload" data-toggle="tab">
					<i class="fab fa-uikit"></i>
					<span class="hidden-xs"> <?php echo translate('logo'); ?></span>
				</a>
			</li>
			<li <?php echo ($active_tab == 3 ? "class='active'" : ''); ?>>
				<a href="#social" data-toggle="tab">
					<i class="fas fa-share-alt"></i>
					<span class="hidden-xs"> Social URL</span>
				</a>
			</li>
			<li <?php echo ($active_tab == 4 ? "class='active'" : ''); ?>>
				<a href="#setting" data-toggle="tab">
					<i class="fas fa-cog"></i>
					<span class="hidden-xs"> Setting</span>
				</a>
			</li>
			<li <?php echo ($active_tab == 5 ? "class='active'" : ''); ?>>
				<a href="#diagnostics" data-toggle="tab">
					<i class="fas fa-heartbeat"></i>
					<span class="hidden-xs"> System Health</span>
				</a>
			</li>
			<li <?php echo ($active_tab == 6 ? "class='active'" : ''); ?>>
				<a href="#theme" data-toggle="tab">
					<i class="fas fa-paint-roller"></i>
					<span class="hidden-xs"> <?php echo translate('theme') . " " . translate('setting'); ?></span>
				</a>
			</li>
			<li <?php echo ($active_tab == 7 ? "class='active'" : ''); ?>>
				<a href="#ops" data-toggle="tab">
					<i class="fas fa-sliders-h"></i>
					<span class="hidden-xs"> <?php echo translate('operations_settings'); ?></span>
				</a>
			</li>
		</ul>

		<div class="tab-content">
			<!-- General Setting Tab -->
			<div class="tab-pane box <?php echo (empty($active_tab) || $active_tab == 1 ? 'active' : ''); ?>" id="general">
				<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('system_name'); ?></label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="site_name" value="<?php echo set_value('site_name', isset($global_config['site_name']) ? $global_config['site_name'] : ''); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('system_email'); ?></label>
					<div class="col-md-10">
						<input type="email" class="form-control" name="site_email" value="<?php echo set_value('site_email', isset($global_config['site_email']) ? $global_config['site_email'] : ''); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('mobile_no'); ?></label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="mobile_no" value="<?php echo set_value('mobile_no', isset($global_config['mobile_no']) ? $global_config['mobile_no'] : ''); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('address'); ?></label>
					<div class="col-md-10">
						<textarea name="address" rows="2" class="form-control" aria-required="true"><?php echo set_value('address', $global_config['address']); ?></textarea>
					</div>
				</div>
				<?php if (get_permission('global_setting', 'is_edit')): ?>
					<footer class="panel-footer mt-md">
						<div class="row">
							<div class="col-md-2 col-md-offset-10">
								<button type="submit" class="btn btn btn-primary btn-block" name="general_setting" value="1"><?php echo translate('save'); ?></button>
							</div>
						</div>
					</footer>
				<?php endif; ?>
				<?php echo form_close(); ?>
			</div>

			<!-- Logo Tab -->
			<div class="tab-pane box <?php echo ($active_tab == 2 ? 'active' : ''); ?>" id="upload">
				<?php echo form_open_multipart($this->uri->uri_string()); ?>
				<div class="headers-line">
					<i class="fab fa-envira"></i> <?php echo translate('logo'); ?>
				</div>
				<div class="row">
					<?php
					$app_path = 'uploads/app_image/';
					$logo = (isset($global_config['logo']) && !empty($global_config['logo']) ? $global_config['logo'] : 'logo.png');
					$logo_path = $app_path . $logo;
					if (!file_exists(FCPATH . $logo_path)) {
						$logo_path = $app_path . 'defualt.png';
					}

					$text_logo_path = $app_path . 'logo-small.png';
					if (!file_exists(FCPATH . $text_logo_path)) {
						$text_logo_path = $app_path . 'defualt.png';
					}

					$print_logo_path = $app_path . 'printing-logo.png';
					if (!file_exists(FCPATH . $print_logo_path)) {
						$print_logo_path = $app_path . 'defualt.png';
					}

					$favicon = (isset($global_config['favicon']) && !empty($global_config['favicon']) ? $global_config['favicon'] : 'favicon.png');
					$favicon_path = $app_path . $favicon;
					if (!file_exists(FCPATH . $favicon_path)) {
						$favicon_path = $app_path . 'defualt.png';
					}
					?>
					<div class="col-md-3">
						<div class="form-group">
							<label class="control-label"><?php echo translate('system_logo'); ?></label>
							<input type="file" name="logo_file" class="dropify" data-allowed-file-extensions="png jpg jpeg" data-default-file="<?php echo base_url($logo_path); ?>" />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label class="control-label"><?php echo translate('text_logo'); ?></label>
							<input type="file" name="text_logo" class="dropify" data-allowed-file-extensions="png jpg jpeg" data-default-file="<?php echo base_url($text_logo_path); ?>" />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label class="control-label"> Printing Logo</label>
							<input type="file" name="print_file" class="dropify" data-allowed-file-extensions="png jpg jpeg" data-default-file="<?php echo base_url($print_logo_path); ?>" />
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							<label class="control-label"> Favicon</label>
							<input type="file" name="favicon_file" class="dropify" data-allowed-file-extensions="png ico jpg jpeg" data-default-file="<?php echo base_url($favicon_path); ?>" />
						</div>
					</div>
				</div>
				<?php if (get_permission('global_setting', 'is_edit')): ?>
					<footer class="panel-footer">
						<div class="row">
							<div class="col-md-2 col-md-offset-10">
								<button type="submit" class="btn btn btn-primary btn-block" name="logo" value="1"><?php echo translate('upload'); ?></button>
							</div>
						</div>
					</footer>
				<?php endif; ?>
				<?php echo form_close(); ?>
			</div>

			<!-- Setting Tab (Requested Fields) -->
			<div class="tab-pane box <?php echo ($active_tab == 4 ? 'active' : ''); ?>" id="setting">
				<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('currency'); ?></label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="currency" placeholder="e.g. BDT" value="<?php echo set_value('currency', $global_config['currency']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('currency_symbol'); ?></label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="currency_symbol" placeholder="e.g. ৳" value="<?php echo set_value('currency_symbol', $global_config['currency_symbol']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('language'); ?></label>
					<div class="col-md-10">
						<?php
						$languages = $this->db->select('id,code,name')->where('status', 'Active')->get('language_list')->result();
						$array = array();
						foreach ($languages as $lang) {
							$array[$lang->name] = ucfirst($lang->name);
						}
						echo form_dropdown("translation", $array, set_value('translation', $global_config['translation']), "class='form-control'
							data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
						?>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('timezone'); ?></label>
					<div class="col-md-10">
						<?php
						$timezones = $this->app_lib->timezone_list();
						echo form_dropdown("timezone", $timezones, set_value('timezone', $global_config['timezone']), "class='form-control'
							required data-plugin-selectTwo data-width='100%'");
						?>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('date_format'); ?></label>
					<div class="col-md-10">
						<?php
						$getDateformat = $this->app_lib->get_date_format();
						echo form_dropdown("date_format", $getDateformat, set_value('date_format', isset($global_config['date_format']) ? $global_config['date_format'] : ''), "class='form-control' id='date_format' 
							data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
						?>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('footer_text'); ?></label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="footer_text" value="<?php echo set_value('footer_text', $global_config['footer_text']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label">reCAPTCHA Site Key</label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="recaptcha_site_key" value="<?php echo set_value('recaptcha_site_key', $global_config['recaptcha_site_key'] ?? ''); ?>" placeholder="Google reCAPTCHA v2 site key (leave blank to disable)" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label">reCAPTCHA Secret Key</label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="recaptcha_secret_key" value="<?php echo set_value('recaptcha_secret_key', $global_config['recaptcha_secret_key'] ?? ''); ?>" placeholder="Google reCAPTCHA v2 secret key" />
						<small class="text-muted">When both keys are set, the admin login shows a reCAPTCHA challenge.</small>
					</div>
				</div>
				<?php if (get_permission('global_setting', 'is_edit')): ?>
					<footer class="panel-footer mt-md">
						<div class="row">
							<div class="col-md-2 col-md-offset-10">
								<button type="submit" class="btn btn btn-primary btn-block" name="app_setting" value="1"><?php echo translate('save'); ?></button>
							</div>
						</div>
					</footer>
				<?php endif; ?>
				<?php echo form_close(); ?>
			</div>

			<!-- Social URL Tab -->
			<div class="tab-pane box <?php echo ($active_tab == 3 ? 'active' : ''); ?>" id="social">
				<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
				<div class="form-group">
					<label class="col-md-2 control-label">Facebook URL</label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="facebook_url" value="<?php echo set_value('facebook_url', $global_config['facebook_url']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label">Twitter URL</label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="twitter_url" value="<?php echo set_value('twitter_url', $global_config['twitter_url']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label">Linkedin URL</label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="linkedin_url" value="<?php echo set_value('linkedin_url', $global_config['linkedin_url']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label">Youtube URL</label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="youtube_url" value="<?php echo set_value('youtube_url', $global_config['youtube_url']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-2 control-label">Instagram URL</label>
					<div class="col-md-10">
						<input type="text" class="form-control" name="instagram_url" value="<?php echo set_value('instagram_url', $global_config['instagram_url']); ?>" />
					</div>
				</div>
				<?php if (get_permission('global_setting', 'is_edit')): ?>
					<footer class="panel-footer mt-md">
						<div class="row">
							<div class="col-md-2 col-md-offset-10">
								<button type="submit" class="btn btn btn-primary btn-block" name="social_setting" value="1"><?php echo translate('save'); ?></button>
							</div>
						</div>
					</footer>
				<?php endif; ?>
				<?php echo form_close(); ?>
			</div>

			<!-- Theme Setting Tab -->
			<div class="tab-pane box <?php echo ($active_tab == 6 ? 'active' : ''); ?>" id="theme">
				<?php if (get_permission('global_setting', 'is_edit')): ?>
					<?php $this->load->view('settings/global/_theme_form', [
						'theme_config' => $theme_config ?? [],
						'form_action'  => $this->uri->uri_string(),
						'show_reset'   => true,
						'submit_name'  => 'theme',
						'submit_value' => '1',
					]); ?>
				<?php else: ?>
					<p class="text-muted"><?php echo translate('access_denied'); ?></p>
				<?php endif; ?>
			</div>

			<!-- Operations Settings Tab -->
			<div class="tab-pane box <?php echo ($active_tab == 7 ? 'active' : ''); ?>" id="ops">
				<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
				<div class="headers-line"><i class="fas fa-sliders-h"></i> <?php echo translate('operations_settings'); ?></div>
				<?php
                $ops_toggles = [
                    'customer_self_registration' => translate('customer_self_registration'),
                    'booking_reminders_enabled' => translate('booking_reminders_enabled'),
                    'weekly_report_email_enabled' => translate('weekly_report_email_enabled'),
                    'delivery_eta_notifications_enabled' => translate('delivery_eta_notifications_enabled'),
                    'kitchen_stock_alerts_enabled' => translate('kitchen_stock_alerts_enabled'),
                    'wallet_auto_topup_enabled' => translate('wallet_auto_topup_enabled'),
                ];
				foreach ($ops_toggles as $field => $label):
					$checked = !isset($global_config[$field]) || (int) $global_config[$field] === 1;
				?>
				<div class="form-group">
					<label class="col-md-4 control-label"><?php echo $label; ?></label>
					<div class="col-md-8">
						<div class="checkbox-custom checkbox-primary mt-xs">
							<input type="checkbox" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?>>
							<label for="<?php echo $field; ?>"><?php echo translate('enabled'); ?></label>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
				<?php if (get_permission('global_setting', 'is_edit')): ?>
					<footer class="panel-footer mt-md">
						<div class="row">
							<div class="col-md-2 col-md-offset-10">
								<button type="submit" class="btn btn-primary btn-block" name="ops_setting" value="1"><?php echo translate('save'); ?></button>
							</div>
						</div>
					</footer>
				<?php endif; ?>
				<?php echo form_close(); ?>
			</div>

			<!-- System Diagnostics & Cache Optimizer Tab -->
			<div class="tab-pane box <?php echo ($active_tab == 5 ? 'active' : ''); ?>" id="diagnostics">
				<div class="headers-line">
					<i class="fas fa-heartbeat"></i> System Diagnostics & Cache Manager
					<p class="text-muted" style="font-weight: normal; font-size: 12px; margin-top: 5px;">Monitor configuration files integrity and optimize the JSON caching layer in real-time.</p>
				</div>

				<div class="row">
					<div class="col-md-8">
						<section class="panel panel-featured panel-featured-primary" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
							<div class="panel-body">
								<h5 class="mt-none" style="font-weight: 600; font-size: 15px;"><i class="fas fa-database text-primary"></i> JSON Cache Registry</h5>
								<hr class="short mt-xs">
								<?php
								if (!function_exists('get_cache_stats')) {
									function get_cache_stats($path) {
										$stats = [
											'size' => '<span class="text-muted">N/A</span>',
											'count' => '<span class="text-muted">0 Items</span>'
										];
										if (file_exists($path)) {
											$size_bytes = filesize($path);
											if ($size_bytes < 1024) {
												$stats['size'] = $size_bytes . ' B';
											} else {
												$stats['size'] = round($size_bytes / 1024, 2) . ' KB';
											}
											$content = file_get_contents($path);
											$json = json_decode($content, true);
											if (is_array($json)) {
												$stats['count'] = count($json) . ' Keys';
											}
										}
										return $stats;
									}
								}
								?>
								<table class="table table-bordered table-condensed mt-md" style="width: 100%;">
									<thead>
										<tr>
											<th style="width: 25%;">Cache Name</th>
											<th style="width: 40%;">Location</th>
											<th style="width: 12%;">Size</th>
											<th style="width: 13%;">Cached Keys</th>
											<th style="width: 10%;" class="text-center">Status</th>
										</tr>
									</thead>
									<tbody>
										<?php
										// Fetch all active languages dynamically to monitor their caches
										$active_languages = $this->db->get_where('language_list', array('status' => "Active"))->result_array();
										foreach ($active_languages as $lang):
											$lang_name = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $lang['name']));
											$path = APPPATH . 'language/' . $lang_name . '/' . $lang_name . '.json';
											$stats = get_cache_stats($path);
										?>
											<tr>
												<td><strong>Language (<?php echo html_escape($lang['name']); ?>)</strong></td>
												<td style="word-break: break-all;"><code>application/language/<?php echo $lang_name; ?>/<?php echo $lang_name; ?>.json</code></td>
												<td><?php echo $stats['size']; ?></td>
												<td><?php echo $stats['count']; ?></td>
												<td class="text-center">
													<?php if (file_exists($path)): ?>
														<span class="label label-primary"><i class="fas fa-check"></i> Healthy</span>
													<?php else: ?>
														<span class="label label-danger"><i class="fas fa-times"></i> Missing</span>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>

										<?php
										// Display stats for Global Settings cache
										$global_path = APPPATH . 'logs/global/global_settings.json';
										$global_stats = get_cache_stats($global_path);
										?>
										<tr>
											<td><strong>Global Settings</strong></td>
											<td style="word-break: break-all;"><code>application/logs/global/global_settings.json</code></td>
											<td><?php echo $global_stats['size']; ?></td>
											<td><?php echo $global_stats['count']; ?></td>
											<td class="text-center">
												<?php if (file_exists($global_path)): ?>
													<span class="label label-primary"><i class="fas fa-check"></i> Healthy</span>
												<?php else: ?>
													<span class="label label-danger"><i class="fas fa-times"></i> Missing</span>
												<?php endif; ?>
											</td>
										</tr>

										<?php
										// Display stats for Theme Settings cache
										$theme_path = APPPATH . 'logs/theme/theme_settings.json';
										$theme_stats = get_cache_stats($theme_path);
										?>
										<tr>
											<td><strong>Theme Config</strong></td>
											<td style="word-break: break-all;"><code>application/logs/theme/theme_settings.json</code></td>
											<td><?php echo $theme_stats['size']; ?></td>
											<td><?php echo $theme_stats['count']; ?></td>
											<td class="text-center">
												<?php if (file_exists($theme_path)): ?>
													<span class="label label-primary"><i class="fas fa-check"></i> Healthy</span>
												<?php else: ?>
													<span class="label label-danger"><i class="fas fa-times"></i> Missing</span>
												<?php endif; ?>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</section>
					</div>

					<div class="col-md-4">
						<section class="panel panel-featured panel-featured-primary" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
							<div class="panel-body">
								<h5 class="mt-none" style="font-weight: 600; font-size: 15px;"><i class="fas fa-bolt text-primary"></i> Cache Warm-Up & Maintenance</h5>
								<hr class="short mt-xs">
								<p class="text-muted" style="font-size: 13px; line-height: 1.6;">
									If database settings have been manually modified or if static JSON cache files are deleted or missing, click the button below to force rebuild them. This guarantees zero-DB overhead on site read operations.
								</p>
								<div style="display: flex; flex-direction: column; gap: 12px; margin-top: 25px;">
									<button type="button" class="btn btn-primary btn-block btn-lg" id="warmUpCacheBtn" style="padding: 12px;">
										<i class="fas fa-sync-alt"></i> Rebuild & Sync All JSON Caches
									</button>
									<button type="button" class="btn btn-default btn-block" id="checkIntegrityBtn" style="padding: 10px;">
										<i class="fas fa-shield-alt"></i> Run Cache Integrity Scan
									</button>
								</div>
							</div>
						</section>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script type="text/javascript">
	$(document).ready(function() {
		// Determine which tab to show: flashdata takes priority (after form submit), then URL hash
		var flashTabId = '';
		<?php if (!empty($active_tab)): ?>
		switch (<?php echo (int) $active_tab; ?>) {
			case 1: flashTabId = '#general'; break;
			case 2: flashTabId = '#upload'; break;
			case 3: flashTabId = '#social'; break;
			case 4: flashTabId = '#setting'; break;
			case 5: flashTabId = '#diagnostics'; break;
			case 6: flashTabId = '#theme'; break;
			case 7: flashTabId = '#ops'; break;
		}
		<?php endif; ?>

		var hashTabId = window.location.hash || '';
		var targetTab = flashTabId || hashTabId;

		if (targetTab && $('.nav-tabs a[href="' + targetTab + '"]').length) {
			$('.nav-tabs li').removeClass('active');
			$('.tab-content .tab-pane').removeClass('active');
			$('.nav-tabs a[href="' + targetTab + '"]').parent().addClass('active');
			$(targetTab).addClass('active');
			if (!flashTabId) {
				// Only update hash when driven by hash (not flashdata, to avoid polluting URL after save)
				history.replaceState(null, null, targetTab);
			}
		}

		// Update hash on tab click
		$('.nav-tabs a').on('shown.bs.tab', function(e) {
			history.replaceState(null, null, e.target.hash);
		});

		// System Diagnostics - Rebuild Caches
		$('#warmUpCacheBtn').on('click', function(e) {
			e.preventDefault();
			var btn = $(this);
			btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Rebuilding Cache Files...');

			$.ajax({
				url: '<?php echo base_url('settings/' . route_hash('rebuild_system_cache')); ?>',
				type: 'POST',
				dataType: 'json',
				primary: function(response) {
					swal({
						title: 'Caches Synced!',
						text: response.message,
						type: 'primary',
						confirmButtonClass: 'btn btn-default swal2-btn-default',
						buttonsStyling: false
					}).then(() => {
						window.location.reload();
					});
				},
				error: function() {
					swal({
						title: 'Error!',
						text: 'An error occurred while rebuilding static files cache.',
						type: 'error',
						confirmButtonClass: 'btn btn-default swal2-btn-default',
						buttonsStyling: false
					});
				},
				complete: function() {
					btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Rebuild & Sync All JSON Caches');
				}
			});
		});

		// System Diagnostics - Run Integrity Scan
		$('#checkIntegrityBtn').on('click', function(e) {
			e.preventDefault();
			var btn = $(this);
			btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Performing Integrity Scan...');

			$.ajax({
				url: '<?php echo base_url('settings/' . route_hash('run_integrity_scan')); ?>',
				type: 'POST',
				dataType: 'json',
				primary: function(response) {
					swal({
						title: response.status === 'primary' ? 'All Healthy!' : 'Scan Warning!',
						text: response.message,
						type: response.status,
						confirmButtonClass: 'btn btn-default swal2-btn-default',
						buttonsStyling: false
					});
				},
				error: function() {
					swal({
						title: 'Error!',
						text: 'Integrity scan service is currently offline.',
						type: 'error',
						confirmButtonClass: 'btn btn-default swal2-btn-default',
						buttonsStyling: false
					});
				},
				complete: function() {
					btn.prop('disabled', false).html('<i class="fas fa-shield-alt"></i> Run Cache Integrity Scan');
				}
			});
		});

	});
</script>
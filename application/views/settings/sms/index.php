<section class="panel">
	<?php
	$active_tab = $this->session->flashdata('sms_active_tab') ?: 'test_sms';
	$page_tabs = [
		['id' => 'test_sms',    'icon' => 'fas fa-paper-plane', 'label' => translate('test_sms'),    'active' => $active_tab === 'test_sms'],
		['id' => 'sms_config',  'icon' => 'fas fa-cog',         'label' => translate('sms_config'),  'active' => $active_tab === 'sms_config'],
		['id' => 'sms_template','icon' => 'fas fa-sitemap',     'label' => translate('sms_template'),'active' => $active_tab === 'sms_template'],
	];
	$this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs]);
	?>

	<!-- TAB 1: Test SMS -->
	<div id="test_sms" class="tab-pane <?= $active_tab === 'test_sms' ? 'active' : ''; ?>">
		<?php echo form_open($this->uri->uri_string(), ['class' => 'form-horizontal form-bordered']); ?>
		<input type="hidden" name="active_tab" value="test_sms">
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('recipient_mobile_no'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<input type="text" class="form-control" name="recipient_no" placeholder="e.g. +88017XXXXXXXX">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('message'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<textarea class="form-control" name="message" rows="3" placeholder="<?= translate('test_message_placeholder'); ?>"></textarea>
			</div>
		</div>
		<footer class="panel-footer" style="background:transparent;border-top:none;">
			<div class="row">
				<div class="col-md-offset-10 col-md-2">
					<button type="submit" name="save" value="test_sms" class="btn btn-primary btn-block">
						<i class="fas fa-paper-plane"></i> <?= translate('send_sms'); ?>
					</button>
				</div>
			</div>
		</footer>
		<?php echo form_close(); ?>
	</div>

	<!-- TAB 2: SMS Configuration -->
	<div id="sms_config" class="tab-pane <?= $active_tab === 'sms_config' ? 'active' : ''; ?>">

		<!-- Active Gateway Selection -->
		<?php echo form_open($this->uri->uri_string(), ['class' => 'form-horizontal form-bordered']); ?>
		<input type="hidden" name="active_tab" value="sms_config">
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('selected_sms_gateway'); ?></label>
			<div class="col-md-10">
				<?php
				$gateway_options = ['disabled' => translate('disabled'), 'custom_sms' => 'SMS'];
				echo form_dropdown('sms_gateway', $gateway_options, $sms_api->active_gateway,
					"class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
				?>
				<p class="help-block" style="color:#6b7280;font-size:12px;margin-top:6px;"><?= translate('gateway_used_for_all_sms'); ?></p>
			</div>
		</div>
		<?php if (get_permission('sms_setting', 'is_edit')): ?>
		<div class="form-group">
			<div class="col-md-offset-10 col-md-2">
				<button type="submit" name="save" value="gateway" class="btn btn-primary btn-block">
					<i class="fas fa-plus-circle"></i> <?= translate('save'); ?>
				</button>
			</div>
		</div>
		<?php endif; ?>
		<?php echo form_close(); ?>

		<hr style="margin:25px 0;border-top:1px solid #eee;">

		<!-- Custom SMS Gateway Credentials -->
		<?php echo form_open($this->uri->uri_string(), ['class' => 'form-horizontal form-bordered validate']); ?>
		<input type="hidden" name="active_tab" value="sms_config">
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('type'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<input type="text" required class="form-control" name="custom_type" value="<?= html_escape($sms_api->custom_type ?? 'SMS'); ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('endpoint_url'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<input type="url" required class="form-control" name="custom_endpoint" value="<?= html_escape($sms_api->custom_endpoint ?? ''); ?>" placeholder="https://api.example.com/send">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('method'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<select name="custom_method" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
					<option value="GET" <?= (($sms_api->custom_method ?? 'GET') === 'GET' ? 'selected' : ''); ?>>GET</option>
					<option value="POST" <?= (($sms_api->custom_method ?? 'GET') === 'POST' ? 'selected' : ''); ?>>POST</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('mobile_prefix'); ?></label>
			<div class="col-md-10">
				<input type="text" class="form-control" name="custom_mobile_prefix" value="<?= html_escape($sms_api->custom_mobile_prefix ?? ''); ?>" placeholder="e.g. +880">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('mobile_key'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<input type="text" required class="form-control" name="custom_mobile_key" value="<?= html_escape($sms_api->custom_mobile_key ?? 'MobileNumbers'); ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('message_key'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<input type="text" required class="form-control" name="custom_message_key" value="<?= html_escape($sms_api->custom_message_key ?? 'Message'); ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('headers'); ?></label>
			<div class="col-md-8">
				<div id="headers_container" style="margin-bottom:10px;">
					<?php $headers = $sms_api->custom_headers ?? [];
					if (!empty($headers) && is_array($headers)):
						foreach ($headers as $key => $val): ?>
					<div class="row header-row" style="margin-bottom:10px;display:flex;align-items:center;">
						<div class="col-xs-5"><input type="text" name="header_keys[]" class="form-control" placeholder="Key" value="<?= html_escape($key); ?>"></div>
						<div class="col-xs-5"><input type="text" name="header_values[]" class="form-control" placeholder="Value" value="<?= html_escape($val); ?>"></div>
						<div class="col-xs-2"><button type="button" class="btn remove-header-btn" style="width:100%;height:34px;background:#e9ecef;border:none;color:#d9534f;"><i class="far fa-trash-alt"></i></button></div>
					</div>
					<?php endforeach; endif; ?>
				</div>
				<button type="button" class="btn btn-default btn-xs" id="add_header_btn"><i class="fas fa-plus"></i> Add Header</button>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label"><?= translate('additional_parameters'); ?></label>
			<div class="col-md-8">
				<div id="params_container" style="margin-bottom:10px;">
					<?php $params = $sms_api->custom_params ?? [];
					if (!empty($params) && is_array($params)):
						foreach ($params as $key => $val): ?>
					<div class="row param-row" style="margin-bottom:10px;display:flex;align-items:center;">
						<div class="col-xs-5"><input type="text" name="param_keys[]" class="form-control" placeholder="Key" value="<?= html_escape($key); ?>"></div>
						<div class="col-xs-5"><input type="text" name="param_values[]" class="form-control" placeholder="Value" value="<?= html_escape($val); ?>"></div>
						<div class="col-xs-2"><button type="button" class="btn remove-param-btn" style="width:100%;height:34px;background:#e9ecef;border:none;color:#d9534f;"><i class="far fa-trash-alt"></i></button></div>
					</div>
					<?php endforeach; endif; ?>
				</div>
				<button type="button" class="btn btn-default btn-xs" id="add_param_btn"><i class="fas fa-plus"></i> Add Parameter</button>
			</div>
		</div>
		<?php if (get_permission('sms_setting', 'is_edit')): ?>
		<footer class="panel-footer" style="background:transparent;border-top:none;padding-top:20px;">
			<div class="row">
				<div class="col-md-offset-9 col-md-3">
					<button type="submit" name="save" value="custom_sms" class="btn btn-primary btn-block">
						<i class="fas fa-plus-circle"></i> <?= translate('save'); ?>
					</button>
				</div>
			</div>
		</footer>
		<?php endif; ?>
		<?php echo form_close(); ?>
	</div>

	<!-- TAB 3: SMS Template -->
	<div id="sms_template" class="tab-pane <?= $active_tab === 'sms_template' ? 'active' : ''; ?>">
		<div class="panel-group" id="accordion">
			<?php foreach ($template as $key => $row): ?>
			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#tpl_<?= html_escape($row['sms_type']); ?>">
							<i class="far fa-comment-dots"></i> <?= translate($row['sms_type']); ?>
						</a>
					</h4>
				</div>
				<div id="tpl_<?= html_escape($row['sms_type']); ?>" class="accordion-body collapse <?= ($this->session->flashdata('active_template') == $row['id'] ? 'in' : ''); ?>">
					<?php echo form_open($this->uri->uri_string()); ?>
					<input type="hidden" name="template_id" value="<?= $row['id']; ?>">
					<input type="hidden" name="active_tab" value="sms_template">
					<input type="hidden" name="save" value="template">
					<div class="panel-body">
						<div class="form-group">
							<div class="checkbox-replace">
								<label class="i-checks">
									<input type="checkbox" name="notify_enable" <?= ($row['notified'] == 1 ? 'checked' : ''); ?> value="1">
									<i></i> <?= translate('notify_enable'); ?>
								</label>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label"><?= translate('body'); ?></label>
							<textarea name="template_body" class="form-control" rows="4"><?= html_escape($row['template_body']); ?></textarea>
						</div>
						<div><strong>Codes:</strong> <?= $row['tags']; ?></div>
					</div>
					<?php if (get_permission('sms_setting', 'is_edit')): ?>
					<div class="panel-footer">
						<div class="row">
							<div class="col-md-offset-10 col-md-2">
								<button type="submit" class="btn btn-default btn-block">
									<i class="fas fa-plus-circle"></i> <?= translate('save'); ?>
								</button>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<?php echo form_close(); ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php $this->load->view('layout/_page_tabs_end'); ?>
</section>

<script>
$(document).ready(function() {
	$('#add_header_btn').click(function() {
		$('#headers_container').append('<div class="row header-row" style="margin-bottom:10px;display:flex;align-items:center;"><div class="col-xs-5"><input type="text" name="header_keys[]" class="form-control" placeholder="Key"></div><div class="col-xs-5"><input type="text" name="header_values[]" class="form-control" placeholder="Value"></div><div class="col-xs-2"><button type="button" class="btn remove-header-btn" style="width:100%;height:34px;background:#e9ecef;border:none;color:#d9534f;"><i class="far fa-trash-alt"></i></button></div></div>');
	});
	$(document).on('click', '.remove-header-btn', function() { $(this).closest('.header-row').remove(); });
	$('#add_param_btn').click(function() {
		$('#params_container').append('<div class="row param-row" style="margin-bottom:10px;display:flex;align-items:center;"><div class="col-xs-5"><input type="text" name="param_keys[]" class="form-control" placeholder="Key"></div><div class="col-xs-5"><input type="text" name="param_values[]" class="form-control" placeholder="Value"></div><div class="col-xs-2"><button type="button" class="btn remove-param-btn" style="width:100%;height:34px;background:#e9ecef;border:none;color:#d9534f;"><i class="far fa-trash-alt"></i></button></div></div>');
	});
	$(document).on('click', '.remove-param-btn', function() { $(this).closest('.param-row').remove(); });
});
</script>

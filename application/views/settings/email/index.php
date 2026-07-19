<?php $active_tab = $this->session->flashdata('active_tab') ?: 'email_test'; ?>
<section class="panel">
	<?php
	$page_tabs = [
		['id' => 'email_test', 'icon' => 'fas fa-paper-plane', 'label' => 'Test Email', 'active' => ($active_tab == 'email_test')],
		['id' => 'email_config', 'icon' => 'far fa-envelope', 'label' => translate('email_config'), 'active' => ($active_tab == 'email_config')],
		['url' => base_url('email/' . route_hash('template')), 'icon' => 'fas fa-sitemap', 'label' => translate('email_triggers'), 'active' => false],
	];
	$this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs]);
	?>
	<!-- Test Email Tab (first) -->
	<div id="email_test" class="tab-pane <?php echo ($active_tab == 'email_test' ? 'active' : ''); ?>">
		<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
		<div class="form-group">
			<label class="col-md-2 control-label">Test Recipient Email <span class="required">*</span></label>
			<div class="col-md-10">
				<input required class="form-control" name="test_recipient" type="email" placeholder="Recipient Email Address">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">Subject <span class="required">*</span></label>
			<div class="col-md-10">
				<input required class="form-control" name="test_subject" type="text" placeholder="e.g. Test SMTP Mail Config">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">Message <span class="required">*</span></label>
			<div class="col-md-10 mb-md">
				<textarea required class="form-control" name="test_message" rows="5" placeholder="Type your test email message here..."></textarea>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-2 col-sm-offset-10">
					<button type="submit" class="btn btn-primary btn-block" name="test_email" value="1"><i class="fas fa-paper-plane"></i> Send Email</button>
				</div>
			</div>
		</footer>
		<?php echo form_close(); ?>
	</div>

	<!-- Email Config Tab -->
	<div id="email_config" class="tab-pane <?php echo ($active_tab == 'email_config' ? 'active' : ''); ?>">
		<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
		<div class="form-group">
			<label class="col-md-2 control-label"><?php echo translate('system_email'); ?> <span class="required">*</span></label>
			<div class="col-md-10">
				<input required class="form-control" value="<?php echo html_escape($config['email']); ?>" name="email" type="email" placeholder="All Outgoing Email Will be sent from This Email Address.">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">Email Protocol</label>
			<div class="col-md-10">
				<select name="email_protocol" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity" id="email_protocol">
					<option value="mail" <?php if (set_value('email_protocol', $config['email_protocol']) == 'mail') echo 'selected'; ?>>PHP Mail Function</option>
					<option value="sendmail" <?php if (set_value('email_protocol', $config['email_protocol']) == 'sendmail') echo 'selected'; ?>>Send Mail</option>
					<option value="smtp" <?php if (set_value('email_protocol', $config['email_protocol']) == 'smtp') echo 'selected'; ?>>SMTP Mail</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">SMTP Host <span class="required">*</span></label>
			<div class="col-md-10">
				<input required class="form-control smtp" value="<?php echo html_escape($config['smtp_host']); ?>" name="smtp_host" type="text">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">SMTP Username <span class="required">*</span></label>
			<div class="col-md-10">
				<input required class="form-control smtp" value="<?php echo html_escape($config['smtp_user']); ?>" name="smtp_user" type="text">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">SMTP Password <span class="required">*</span></label>
			<div class="col-md-10">
				<input name="smtp_pass" required value="<?php echo html_escape($config['smtp_pass']); ?>" class="form-control smtp" type="password">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-2 control-label">SMTP Port <span class="required">*</span></label>
			<div class="col-md-10">
				<input required class="form-control smtp" value="<?php echo html_escape($config['smtp_port']); ?>" name="smtp_port" type="text">
			</div>
		</div>
		<div class="form-group m">
			<label class="col-md-2 control-label">Email Encryption</label>
			<div class="col-md-10 mb-md">
				<select name="smtp_encryption" class="form-control smtp" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
					<option value="">No</option>
					<option value="tls" <?php if (set_value('smtp_encryption', $config['smtp_encryption']) == 'tls') echo 'selected'; ?>>TLS</option>
					<option value="ssl" <?php if (set_value('smtp_encryption', $config['smtp_encryption']) == 'ssl') echo 'selected'; ?>>SSL</option>
				</select>
			</div>
		</div>
		<?php if (get_permission('email_setting', 'is_edit')): ?>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-2 col-sm-offset-10">
						<button type="submit" class="btn btn-primary btn-block" name="save" value="1"><i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?></button>
					</div>
				</div>
			</footer>
		<?php endif; ?>
	</div>
	<?php $this->load->view('layout/_page_tabs_end'); ?>
</section>
<script type="text/javascript">
	<?php if ($config['email_protocol'] != 'smtp'): ?>
		$(document).ready(function() {
			$(".smtp").prop('disabled', true);
		});
	<?php endif; ?>
</script>
<section class="panel">
	<?php
	$page_tabs = [
		['url' => base_url('sms'), 'icon' => 'far fa-envelope', 'label' => translate('sms_config'), 'active' => false],
		['id' => 'smsConfig', 'icon' => 'fas fa-sitemap', 'label' => translate('sms_template'), 'active' => true],
	];
	$this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs]);
	?>
	<div class="tab-pane active" id="smsConfig">
		<div class="panel-group" id="accordion">
			<?php foreach ($template as $key => $row): ?>
				<div class="panel panel-accordion">
					<div class="panel-heading">
						<h4 class="panel-title">
							<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#<?php echo $row['sms_type']; ?>">
								<i class="far fa-comment-dots"></i> <?php echo translate($row['sms_type']); ?>
							</a>
						</h4>
					</div>
					<div id="<?php echo $row['sms_type']; ?>" class="accordion-body collapse <?php echo ($this->session->flashdata('active_template') == $row['id'] ? 'in' : ''); ?>">
						<?php echo form_open($this->uri->uri_string()); ?>
						<input type="hidden" name="template_id" value="<?php echo $row['id']; ?>">
						<div class="panel-body">
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<div class="checkbox-replace">
											<label class="i-checks">
												<input type="checkbox" name="notify_enable" id="notify_enable" <?php echo ($row['notified'] == 1 ? 'checked' : ''); ?> value="1">
												<i></i> <?php echo translate('notify_enable'); ?>
											</label>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label"><?php echo translate('body'); ?></label>
										<textarea name="template_body" id="<?php echo $key ?>" class="form-control" rows="4"><?php echo html_escape($row['template_body']); ?></textarea>
									</div>
									<div class=""><strong>Codes : </strong><?php echo $row['tags']; ?></div>
								</div>
							</div>
						</div>
						<?php if (get_permission('sms_setting', 'is_edit')): ?>
							<div class="panel-footer">
								<div class="row">
									<div class="col-md-offset-10 col-md-2">
										<button type="submit" name="save" value="1" class="btn btn-default btn-block"><i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?></button>
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
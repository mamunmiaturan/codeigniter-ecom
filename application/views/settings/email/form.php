<section class="panel">
	<?php
	$page_tabs = [
		['url' => base_url('email'), 'icon' => 'fas fa-paper-plane', 'label' => 'Test Email', 'active' => false],
		['url' => base_url('email') . '?tab=config', 'icon' => 'far fa-envelope', 'label' => translate('email_config'), 'active' => false],
		['id' => 'email_triggers', 'icon' => 'fas fa-sitemap', 'label' => translate('email_triggers'), 'active' => true],
	];
	$this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs]);
	?>
	<div class="tab-pane active" id="email_triggers">
		<div class="panel-group" id="accordion">
			<?php if (empty($template)): ?>
				<p class="text-muted"><?= translate('no_email_templates_found'); ?></p>
			<?php endif; ?>
			<?php foreach ($template as $row):
				$key = $row['template_key'] ?? ($row['email_type'] ?? ('tpl_' . $row['id']));
				$tags = $row['available_tags'] ?? ($row['tags'] ?? '');
				$active = (int) ($row['is_active'] ?? ($row['notified'] ?? 1));
			?>
				<div class="panel panel-accordion">
					<div class="panel-heading">
						<h4 class="panel-title">
							<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#tpl_<?php echo (int) $row['id']; ?>">
								<i class="fas fa-at"></i> <?php echo html_escape($key); ?>
								<?php if (!$active): ?><span class="label label-default"><?= translate('inactive'); ?></span><?php endif; ?>
							</a>
						</h4>
					</div>
					<div id="tpl_<?php echo (int) $row['id']; ?>" class="accordion-body collapse <?php echo ($this->session->flashdata('emailt_active') == $row['id'] ? 'in' : ''); ?>">
						<?php echo form_open($this->uri->uri_string(), array('class' => 'validate')); ?>
						<input type="hidden" name="template_id" value="<?php echo html_escape($row['id']); ?>">
						<div class="panel-body">
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<div class="checkbox-replace">
											<label class="i-checks">
												<input type="checkbox" name="is_active" <?php echo $active ? 'checked' : ''; ?> value="1">
												<i></i> <?php echo translate('template_active'); ?>
											</label>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label"><?php echo translate('subject'); ?> <span class="required">*</span></label>
										<input type="text" class="form-control" value="<?php echo html_escape($row['subject']); ?>" name="subject" required />
									</div>
									<div class="form-group">
										<label class=" control-label"><?php echo translate('body'); ?></label>
										<textarea name="template_body" id="editor_<?php echo $row['id']; ?>" class="form-control editor"><?php echo html_escape($row['template_body']); ?></textarea>
									</div>
									<?php if ($tags): ?>
										<div class="md"><strong><?= translate('available_tags'); ?>:</strong> <?php echo html_escape($tags); ?></div>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<?php if (get_permission('email_setting', 'is_edit')): ?>
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
<script type="text/javascript">
	$(document).ready(function() {
		if (typeof CKEDITOR !== 'undefined') {
			<?php foreach ($template as $row): ?>
				CKEDITOR.replace('editor_<?php echo $row['id']; ?>', {
					toolbar: [{
							name: 'basicstyles',
							items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat']
						},
						{
							name: 'paragraph',
							items: ['NumberedList', 'BulletedList', '-', 'Blockquote']
						},
						{
							name: 'links',
							items: ['Link', 'Unlink']
						},
						{
							name: 'insert',
							items: ['Image', 'HorizontalRule']
						},
						{
							name: 'styles',
							items: ['Styles', 'Format']
						},
						{
							name: 'tools',
							items: ['Maximize', 'Source']
						}
					],
					height: 250
				});
			<?php endforeach; ?>

			$('.validate').on('submit', function() {
				for (var name in CKEDITOR.instances) {
					CKEDITOR.instances[name].updateElement();
				}
			});
		}
	});
</script>
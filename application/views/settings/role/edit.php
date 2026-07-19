<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li>
				<a href="<?php echo base_url('role'); ?>"><i class="fas fa-list-ul"></i> <?php echo translate('role') . " " . translate('list'); ?></a>
			</li>
			<li class="active">
				<a href="#create" data-toggle="tab"><i class="far fa-edit"></i> <?php echo translate('create') . " " . translate('role'); ?></a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="create">
				<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal')); ?>
				<input type="hidden" name="id" value="<?php echo html_escape(encrypt_id($roles['id'])); ?>">
				<div class="form-group <?php if (form_error('role')) echo 'has-error'; ?>">
					<label class="col-md-2 control-label"><?php echo translate('role') . " " . translate('name'); ?> <span class="required">*</span></label>
					<div class="col-md-10 mb-sm">
						<input type="text" class="form-control" name="role" value="<?php echo set_value('role', $roles['name']); ?>">
						<span class="error"><?php echo form_error('role'); ?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('level'); ?></label>
					<div class="col-md-10 mb-sm">
						<input type="number" min="1" step="1" class="form-control" name="level" value="<?php echo set_value('level', isset($roles['level']) ? (int) $roles['level'] : 50); ?>" style="max-width:160px;"
							<?php echo (int) $roles['id'] === ROLE_SUPERMAN_ID ? 'readonly' : ''; ?>>
						<span class="help-block text-muted"><?php echo translate('role_level_hint') ?: 'Lower number = higher authority. A role can manage roles with a higher number than its own.'; ?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo translate('parent') ?: 'Parent'; ?> <?php echo translate('role'); ?></label>
					<div class="col-md-10 mb-sm">
						<?php $__cur_parent = isset($roles['parent_id']) ? (int) $roles['parent_id'] : 0; ?>
						<select name="parent_id" class="form-control" style="max-width:320px;">
							<option value="0"><?php echo translate('none') ?: 'None (top level)'; ?></option>
							<?php foreach (($parent_options ?? []) as $__opt): ?>
								<?php if ((int) $__opt['id'] === (int) $roles['id']) continue; // a role cannot be its own parent ?>
								<option value="<?php echo (int) $__opt['id']; ?>" <?php echo set_select('parent_id', (string) $__opt['id'], $__cur_parent === (int) $__opt['id']); ?>>
									<?php echo html_escape($__opt['name']); ?> (<?php echo translate('level'); ?> <?php echo isset($__opt['level']) ? (int) $__opt['level'] : 0; ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<span class="help-block text-muted"><?php echo translate('role_parent_hint') ?: 'Which role this one sits under in the organogram.'; ?></span>
					</div>
				</div>

				<footer class="panel-footer mt-lg">
					<div class="row">
						<div class="col-md-2 col-md-offset-10">
							<button type="submit" name="save" value="1" class="btn btn-default btn-block"><i class="fas fa-edit"></i> <?php echo translate('update'); ?></button>
						</div>
					</div>
				</footer>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</section>
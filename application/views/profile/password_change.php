<section class="panel">
	<div class="tabs-custom">
		<div class="tab-content">
			<div class="tab-pane box active" id="list">
				<?php echo form_open(base_url('profile/' . route_hash('password')), array('class' => 'form-horizontal form-bordered validate')); ?>
				<div class="form-group mt-xs <?php if (form_error('current_password')) echo 'has-error'; ?>">
					<label class="col-md-2 control-label"><?php echo translate('current_password'); ?> <span class="required">*</span></label>
					<div class="col-md-10">
						<div class="input-group">
							<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
							<input type="password" class="form-control" name="current_password" id="current_password" value="<?php echo set_value('current_password'); ?>" required />
							<span class="input-group-addon toggle-password" data-target="#current_password" style="cursor: pointer;">
								<i class="fas fa-eye"></i>
							</span>
						</div>
						<span class="error"><?php echo form_error('current_password'); ?></span>
					</div>
				</div>
				<div class="form-group <?php if (form_error('new_password')) echo 'has-error'; ?>">
					<label class="col-md-2 control-label"><?php echo translate('new_password'); ?> <span class="required">*</span></label>
					<div class="col-md-10">
						<div class="input-group">
							<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
							<input type="password" class="form-control" name="new_password" id="new_password" value="<?php echo set_value('new_password'); ?>" required />
							<span class="input-group-addon toggle-password" data-target="#new_password" style="cursor: pointer;">
								<i class="fas fa-eye"></i>
							</span>
						</div>
						<span class="error"><?php echo form_error('new_password'); ?></span>
					</div>
				</div>
				<div class="form-group <?php if (form_error('confirm_password')) echo 'has-error'; ?>">
					<label class="col-md-2 control-label"><?php echo translate('confirm_password'); ?> <span class="required">*</span></label>
					<div class="col-md-10 mb-md">
						<div class="input-group">
							<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
							<input type="password" class="form-control" name="confirm_password" id="confirm_password" value="<?php echo set_value('confirm_password'); ?>" required />
							<span class="input-group-addon toggle-password" data-target="#confirm_password" style="cursor: pointer;">
								<i class="fas fa-eye"></i>
							</span>
						</div>
						<span class="error"><?php echo form_error('confirm_password'); ?></span>
					</div>
				</div>
				<footer class="panel-footer">
					<div class="row">
						<div class="col-md-2 col-md-offset-10">
							<button type="submit" class="btn btn-default btn-block" name="save" value="1"><i class="fas fa-key"></i> <?php echo translate('update'); ?></button>
						</div>
					</div>
				</footer>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</section>
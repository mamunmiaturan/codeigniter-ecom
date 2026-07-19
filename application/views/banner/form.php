<?php
$is_edit = !empty($banner);
$action  = $is_edit ? base_url('banner/update') : base_url('banner/store');

$type_options = [];
foreach ($types as $t) {
	$type_options[$t] = translate($t) ?: ucfirst($t);
}

// datetime-local expects "Y-m-d\TH:i"
$fmt_dt = function ($value) {
	$value = trim((string) $value);
	return $value !== '' ? date('Y-m-d\TH:i', strtotime($value)) : '';
};
?>
<section class="panel">
	<?php echo form_open_multipart($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($banner['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-images"></i>
			<?php echo $is_edit ? (translate('edit_banner') ?: 'Edit Banner') : (translate('add_banner') ?: 'Add Banner'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-8 mb-sm">
				<div class="form-group <?php if (form_error('title')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('title') ?: 'Title'; ?></label>
					<input class="form-control" name="title" type="text" value="<?php echo set_value('title', $is_edit ? $banner['title'] : ''); ?>">
					<span class="error"><?php echo form_error('title'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('type') ?: 'Type'; ?> <span class="required">*</span></label>
					<?php echo form_dropdown('type', $type_options, set_value('type', $is_edit ? $banner['type'] : 'slider'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group <?php if (form_error('subtitle')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('subtitle') ?: 'Subtitle'; ?></label>
					<input class="form-control" name="subtitle" type="text" value="<?php echo set_value('subtitle', $is_edit ? $banner['subtitle'] : ''); ?>">
					<span class="error"><?php echo form_error('subtitle'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group <?php if (form_error('link_url')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('link_url') ?: 'Link URL'; ?></label>
					<input class="form-control" name="link_url" type="text" placeholder="landing/shop" value="<?php echo set_value('link_url', $is_edit ? $banner['link_url'] : ''); ?>">
					<small class="text-muted"><?php echo translate('banner_link_hint') ?: 'Relative path (e.g. landing/shop) or full URL.'; ?></small>
					<span class="error"><?php echo form_error('link_url'); ?></span>
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group <?php if (form_error('button_text')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('button_text') ?: 'Button Text'; ?></label>
					<input class="form-control" name="button_text" type="text" placeholder="Shop now" value="<?php echo set_value('button_text', $is_edit ? $banner['button_text'] : ''); ?>">
					<span class="error"><?php echo form_error('button_text'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $banner['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('position')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('position') ?: 'Position'; ?></label>
					<input class="form-control" name="position" type="number" min="0" value="<?php echo set_value('position', $is_edit ? $banner['position'] : 0); ?>">
					<span class="error"><?php echo form_error('position'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label><?php echo translate('image') ?: 'Image'; ?></label>
					<?php $cur = ($is_edit && !empty($banner['image'])) ? base_url('uploads/banner/' . $banner['image']) : ''; ?>
					<input type="file" name="image" class="dropify" data-allowed-file-extensions="png jpg jpeg webp" data-height="120" <?php echo $cur ? 'data-default-file="' . $cur . '"' : ''; ?> />
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('starts_at') ?: 'Starts At'; ?></label>
					<input class="form-control" name="starts_at" type="datetime-local" value="<?php echo set_value('starts_at', $is_edit ? $fmt_dt($banner['starts_at']) : ''); ?>">
					<small class="text-muted"><?php echo translate('leave_blank_no_limit') ?: 'Leave blank for no start limit.'; ?></small>
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('ends_at') ?: 'Ends At'; ?></label>
					<input class="form-control" name="ends_at" type="datetime-local" value="<?php echo set_value('ends_at', $is_edit ? $fmt_dt($banner['ends_at']) : ''); ?>">
					<small class="text-muted"><?php echo translate('leave_blank_no_limit') ?: 'Leave blank for no end limit.'; ?></small>
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('banner'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

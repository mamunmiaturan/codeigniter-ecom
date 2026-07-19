<?php
$is_edit = !empty($brand);
$action  = $is_edit ? base_url('brand/update') : base_url('brand/store');
?>
<section class="panel">
	<?php echo form_open_multipart($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($brand['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="far fa-copyright"></i>
			<?php echo $is_edit ? (translate('edit_brand') ?: 'Edit Brand') : (translate('add_brand') ?: 'Add Brand'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $brand['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('website') ?: 'Website'; ?></label>
					<input class="form-control" name="website" type="text" placeholder="https://example.com" value="<?php echo set_value('website', $is_edit ? $brand['website'] : ''); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $brand['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('sort_order')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
					<input class="form-control" name="sort_order" type="number" min="0" value="<?php echo set_value('sort_order', $is_edit ? $brand['sort_order'] : 0); ?>">
					<span class="error"><?php echo form_error('sort_order'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('is_featured') ?: 'Featured'; ?></label>
					<label class="switch"><input type="checkbox" name="is_featured" value="1" <?php echo set_checkbox('is_featured', '1', $is_edit ? !empty($brand['is_featured']) : false); ?>> <?php echo translate('featured') ?: 'Show as featured'; ?></label>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label><?php echo translate('logo') ?: 'Logo'; ?></label>
					<?php $cur = ($is_edit && !empty($brand['logo'])) ? base_url('uploads/catalog/brand/' . $brand['logo']) : ''; ?>
					<input type="file" name="logo" class="dropify" data-allowed-file-extensions="jpg jpeg png gif webp svg" data-height="120" <?php echo $cur ? 'data-default-file="' . $cur . '"' : ''; ?> />
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('description'); ?></label>
					<textarea class="form-control" rows="3" name="description"><?php echo set_value('description', $is_edit ? $brand['description'] : ''); ?></textarea>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_title') ?: 'Meta Title'; ?></label>
					<input class="form-control" name="meta_title" type="text" value="<?php echo set_value('meta_title', $is_edit ? $brand['meta_title'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_description') ?: 'Meta Description'; ?></label>
					<input class="form-control" name="meta_description" type="text" value="<?php echo set_value('meta_description', $is_edit ? $brand['meta_description'] : ''); ?>">
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('brand'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

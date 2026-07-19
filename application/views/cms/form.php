<?php
$is_edit = !empty($page);
$action  = $is_edit ? base_url('cms/update') : base_url('cms/store');
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?><input type="hidden" name="id" value="<?php echo encrypt_id($page['id']); ?>"><?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-file-alt"></i> <?php echo $is_edit ? (translate('edit_page') ?: 'Edit Page') : (translate('add_page') ?: 'Add Page'); ?></h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-8 mb-sm">
				<div class="form-group <?php if (form_error('title')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('title') ?: 'Title'; ?> <span class="required">*</span></label>
					<input class="form-control" name="title" type="text" value="<?php echo set_value('title', $is_edit ? $page['title'] : ''); ?>">
					<span class="error"><?php echo form_error('title'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('slug') ?: 'Slug'; ?></label>
					<input class="form-control" name="slug" type="text" value="<?php echo set_value('slug', $is_edit ? $page['slug'] : ''); ?>" placeholder="auto from title">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('content') ?: 'Content'; ?></label>
					<textarea class="form-control" name="content" rows="12"><?php echo set_value('content', $is_edit ? $page['content'] : ''); ?></textarea>
					<small class="text-muted"><?php echo translate('html_allowed') ?: 'HTML is allowed.'; ?></small>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php $so = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $so, set_value('status', $is_edit ? $page['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
					<input class="form-control" name="sort_order" type="number" value="<?php echo set_value('sort_order', $is_edit ? $page['sort_order'] : 0); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('show_in_footer') ?: 'Show in footer'; ?></label>
					<label class="switch"><input type="checkbox" name="show_in_footer" value="1" <?php echo set_checkbox('show_in_footer', '1', $is_edit ? !empty($page['show_in_footer']) : true); ?>> <?php echo translate('footer') ?: 'Footer'; ?></label>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_title') ?: 'Meta Title'; ?></label>
					<input class="form-control" name="meta_title" type="text" value="<?php echo set_value('meta_title', $is_edit ? $page['meta_title'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_description') ?: 'Meta Description'; ?></label>
					<input class="form-control" name="meta_description" type="text" value="<?php echo set_value('meta_description', $is_edit ? $page['meta_description'] : ''); ?>">
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('cms'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

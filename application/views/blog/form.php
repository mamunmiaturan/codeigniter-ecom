<?php
$is_edit = !empty($post);
$action  = $is_edit ? base_url('blog/update') : base_url('blog/store');
$pub_val = ($is_edit && !empty($post['published_at'])) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '';
?>
<section class="panel">
	<?php echo form_open_multipart($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($post['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-blog"></i>
			<?php echo $is_edit ? (translate('edit_post') ?: 'Edit Post') : (translate('add_post') ?: 'Add Post'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-8 mb-sm">
				<div class="form-group <?php if (form_error('title')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('title') ?: 'Title'; ?> <span class="required">*</span></label>
					<input class="form-control" name="title" type="text" value="<?php echo set_value('title', $is_edit ? $post['title'] : ''); ?>">
					<span class="error"><?php echo form_error('title'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('slug') ?: 'Slug'; ?></label>
					<input class="form-control" name="slug" type="text" value="<?php echo set_value('slug', $is_edit ? $post['slug'] : ''); ?>" placeholder="auto from title">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('category') ?: 'Category'; ?></label>
					<input class="form-control" name="category" type="text" placeholder="e.g. News" value="<?php echo set_value('category', $is_edit ? $post['category'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('tags') ?: 'Tags'; ?></label>
					<input class="form-control" name="tags" type="text" placeholder="comma,separated,tags" value="<?php echo set_value('tags', $is_edit ? $post['tags'] : ''); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('excerpt') ?: 'Excerpt'; ?></label>
					<textarea class="form-control" name="excerpt" rows="2" maxlength="500"><?php echo set_value('excerpt', $is_edit ? $post['excerpt'] : ''); ?></textarea>
					<small class="text-muted"><?php echo translate('short_summary') ?: 'Short summary shown in listings.'; ?></small>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('content') ?: 'Content'; ?></label>
					<textarea class="form-control" name="content" rows="12"><?php echo set_value('content', $is_edit ? $post['content'] : ''); ?></textarea>
					<small class="text-muted"><?php echo translate('html_allowed') ?: 'HTML is allowed.'; ?></small>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = [
						'Draft'    => (translate('draft') ?: 'Draft'),
						'Active'   => (translate('active') ?: 'Active'),
						'Inactive' => (translate('inactive') ?: 'Inactive'),
					];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $post['status'] : 'Draft'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('published_at') ?: 'Published At'; ?></label>
					<input class="form-control" name="published_at" type="datetime-local" value="<?php echo set_value('published_at', $pub_val); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('is_featured') ?: 'Featured'; ?></label>
					<label class="switch"><input type="checkbox" name="is_featured" value="1" <?php echo set_checkbox('is_featured', '1', $is_edit ? !empty($post['is_featured']) : false); ?>> <?php echo translate('featured') ?: 'Show as featured'; ?></label>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label><?php echo translate('thumbnail') ?: 'Thumbnail'; ?></label>
					<?php $cur = ($is_edit && !empty($post['thumbnail'])) ? base_url('uploads/blog/' . $post['thumbnail']) : ''; ?>
					<input type="file" name="thumbnail" class="dropify" data-allowed-file-extensions="png jpg jpeg webp" data-height="150" <?php echo $cur ? 'data-default-file="' . $cur . '"' : ''; ?> />
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_title') ?: 'Meta Title'; ?></label>
					<input class="form-control" name="meta_title" type="text" value="<?php echo set_value('meta_title', $is_edit ? $post['meta_title'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_description') ?: 'Meta Description'; ?></label>
					<input class="form-control" name="meta_description" type="text" value="<?php echo set_value('meta_description', $is_edit ? $post['meta_description'] : ''); ?>">
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('blog'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

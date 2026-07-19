<?php
$is_edit = !empty($faq);
$action  = $is_edit ? base_url('faq/update') : base_url('faq/store');
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?><input type="hidden" name="id" value="<?php echo encrypt_id($faq['id']); ?>"><?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-question-circle"></i> <?php echo $is_edit ? (translate('edit_faq') ?: 'Edit FAQ') : (translate('add_faq') ?: 'Add FAQ'); ?></h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-8 mb-sm">
				<div class="form-group <?php if (form_error('question')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('question') ?: 'Question'; ?> <span class="required">*</span></label>
					<input class="form-control" name="question" type="text" value="<?php echo set_value('question', $is_edit ? $faq['question'] : ''); ?>">
					<span class="error"><?php echo form_error('question'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('category') ?: 'Category'; ?></label>
					<input class="form-control" name="category" type="text" value="<?php echo set_value('category', $is_edit ? $faq['category'] : ''); ?>" placeholder="e.g. Ordering">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('answer') ?: 'Answer'; ?></label>
					<textarea class="form-control" name="answer" rows="8"><?php echo set_value('answer', $is_edit ? $faq['answer'] : ''); ?></textarea>
					<small class="text-muted"><?php echo translate('html_allowed') ?: 'HTML is allowed.'; ?></small>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php $so = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $so, set_value('status', $is_edit ? $faq['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
					<input class="form-control" name="sort_order" type="number" value="<?php echo set_value('sort_order', $is_edit ? $faq['sort_order'] : 0); ?>">
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('faq'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

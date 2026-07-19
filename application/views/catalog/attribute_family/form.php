<?php
$is_edit = !empty($family);
$action  = $is_edit ? base_url('attribute_family/update') : base_url('attribute_family/store');

// Assignable attribute pool: id => "Admin Name (code)".
$attr_options = [];
foreach ((array) $all_attributes as $a) {
	$attr_options[(int) $a['id']] = $a['admin_name'] . ' (' . $a['code'] . ')';
}

$col_options = [
	'1' => (translate('main_column') ?: 'Main'),
	'2' => (translate('right_column') ?: 'Right'),
];

/**
 * Normalise the groups to render as builder cards. Priority:
 *   1. POST arrays  -> validation failed, repopulate what the user submitted
 *   2. $groups      -> editing an existing family (grouped_attributes structure)
 *   3. []           -> fresh create
 */
$builder_groups = [];
$post_names = $this->input->post('group_name');
if (is_array($post_names)) {
	$p_id   = (array) $this->input->post('group_id');
	$p_code = (array) $this->input->post('group_code');
	$p_col  = (array) $this->input->post('group_column');
	$p_pos  = (array) $this->input->post('group_position');
	$p_attr = (array) $this->input->post('group_attributes');
	foreach ($post_names as $i => $gname) {
		$ids = (isset($p_attr[$i]) && is_array($p_attr[$i])) ? array_map('intval', $p_attr[$i]) : [];
		$builder_groups[] = [
			'id'       => $p_id[$i] ?? '',
			'name'     => $gname,
			'code'     => $p_code[$i] ?? '',
			'column'   => $p_col[$i] ?? '1',
			'position' => $p_pos[$i] ?? ($i + 1),
			'attr_ids' => $ids,
		];
	}
} elseif (!empty($groups)) {
	foreach ($groups as $g) {
		$row = $g['group'];
		$builder_groups[] = [
			'id'       => $row['id'],
			'name'     => $row['name'],
			'code'     => $row['code'],
			'column'   => $row['column'],
			'position' => $row['position'],
			'is_user_defined' => $row['is_user_defined'] ?? 1,
			'attr_ids' => array_map(function ($a) { return (int) $a['id']; }, $g['attributes']),
		];
	}
}

/**
 * Render a single group card. Used for both the pre-filled cards and the JS
 * template (pass $idx = '__IDX__', $g = []). The attribute multiselect is named
 * group_attributes[$idx][] so the controller can align it with the flat group_*
 * arrays; the JS reindex() keeps $idx equal to the card's DOM position on submit.
 */
$render_card = function ($idx, $g) use ($attr_options, $col_options) {
	$gid  = html_escape((string) ($g['id'] ?? ''));
	$name = html_escape((string) ($g['name'] ?? ''));
	$code = html_escape((string) ($g['code'] ?? ''));
	$col  = (string) ($g['column'] ?? '1');
	$pos  = html_escape((string) ($g['position'] ?? ''));
	$sel  = (isset($g['attr_ids']) && is_array($g['attr_ids'])) ? $g['attr_ids'] : [];
	$is_system = array_key_exists('is_user_defined', $g) && (int) $g['is_user_defined'] === 0;
	ob_start();
	?>
	<div class="group-card panel panel-default mb-md" data-index="<?php echo $idx; ?>">
		<div class="panel-body">
			<input type="hidden" name="group_id[]" value="<?php echo $gid; ?>">
			<div class="row">
				<div class="col-md-4 mb-sm">
					<div class="form-group">
						<label class="control-label"><?php echo translate('group_name') ?: 'Group Name'; ?> <span class="required">*</span><?php if ($is_system): ?> <span class="badge badge-secondary" data-toggle="tooltip" data-original-title="<?php echo translate('system_group_hint') ?: 'System group — cannot be removed.'; ?>"><i class="fas fa-lock"></i> <?php echo translate('system') ?: 'System'; ?></span><?php endif; ?></label>
						<input class="form-control" name="group_name[]" type="text" value="<?php echo $name; ?>">
					</div>
				</div>
				<div class="col-md-3 mb-sm">
					<div class="form-group">
						<label class="control-label"><?php echo translate('code') ?: 'Code'; ?></label>
						<input class="form-control" name="group_code[]" type="text" value="<?php echo $code; ?>">
					</div>
				</div>
				<div class="col-md-2 mb-sm">
					<div class="form-group">
						<label class="control-label"><?php echo translate('column') ?: 'Column'; ?></label>
						<?php echo form_dropdown('group_column[]', $col_options, $col, "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
					</div>
				</div>
				<div class="col-md-2 mb-sm">
					<div class="form-group">
						<label class="control-label"><?php echo translate('position') ?: 'Position'; ?></label>
						<input class="form-control" name="group_position[]" type="number" min="0" value="<?php echo $pos; ?>">
					</div>
				</div>
				<div class="col-md-1 mb-sm">
					<div class="form-group">
						<label class="control-label" style="display:block;">&nbsp;</label>
						<?php if ($is_system): ?>
							<button type="button" class="btn btn-default btn-block" disabled data-toggle="tooltip" data-original-title="<?php echo translate('system_group_hint') ?: 'System group — cannot be removed.'; ?>"><i class="fas fa-lock"></i></button>
						<?php else: ?>
							<button type="button" class="btn btn-danger btn-block remove-group-btn" data-toggle="tooltip" data-original-title="<?php echo translate('remove_group') ?: 'Remove Group'; ?>"><i class="fas fa-trash"></i></button>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12 mb-sm">
					<div class="form-group">
						<label class="control-label"><?php echo translate('attributes') ?: 'Attributes'; ?></label>
						<?php echo form_dropdown('group_attributes[' . $idx . '][]', $attr_options, $sel, "class='form-control attr-select' multiple data-plugin-selectTwo data-width='100%'"); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($family['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-sitemap"></i>
			<?php echo $is_edit ? (translate('edit_attribute_family') ?: 'Edit Attribute Family') : (translate('add_attribute_family') ?: 'Add Attribute Family'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $family['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('code')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('code') ?: 'Code'; ?> <span class="required">*</span></label>
					<input class="form-control" name="code" type="text" value="<?php echo set_value('code', $is_edit ? $family['code'] : ''); ?>">
					<span class="error"><?php echo form_error('code'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = ['1' => (translate('active') ?: 'Active'), '0' => (translate('inactive') ?: 'Inactive')];
					$status_value   = $is_edit ? (string) (int) $family['status'] : '1';
					echo form_dropdown('status', $status_options, set_value('status', $status_value), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>

		<hr>

		<div class="row mb-sm">
			<div class="col-md-8">
				<h5 class="mb-none"><i class="fas fa-layer-group"></i> <?php echo translate('attribute_groups') ?: 'Attribute Groups'; ?></h5>
				<small class="text-muted"><?php echo translate('attribute_groups_hint') ?: 'Buckets that organise this family\'s attributes across the Main and Right columns of the product form.'; ?></small>
			</div>
			<div class="col-md-4 text-right">
				<button type="button" id="add-group-btn" class="btn btn-primary btn-sm">
					<i class="fas fa-plus-circle"></i> <?php echo translate('add_group') ?: 'Add Group'; ?>
				</button>
			</div>
		</div>

		<div id="group-list">
			<?php foreach ($builder_groups as $gi => $bg): ?>
				<?php echo $render_card($gi, $bg); ?>
			<?php endforeach; ?>
		</div>

		<template id="group-card-template">
			<?php echo $render_card('__IDX__', []); ?>
		</template>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('attribute_family'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

<script type="text/javascript">
	(function () {
		var list   = document.getElementById('group-list');
		var tpl    = document.getElementById('group-card-template');
		var addBtn = document.getElementById('add-group-btn');
		if (!list || !tpl) { return; }

		var seq = <?php echo count($builder_groups); ?>;

		function hasSelect2() {
			return !!(window.jQuery && jQuery.fn && jQuery.fn.select2);
		}

		// Keep group_attributes[<idx>][] aligned with each card's DOM position so
		// the controller can pair it with the flat group_* arrays.
		function reindex() {
			var cards = list.querySelectorAll('.group-card');
			for (var i = 0; i < cards.length; i++) {
				var sel = cards[i].querySelector('select.attr-select');
				if (sel) { sel.name = 'group_attributes[' + i + '][]'; }
				cards[i].setAttribute('data-index', i);
			}
		}

		function initSelect2(scope) {
			if (!hasSelect2()) { return; }
			jQuery(scope).find('[data-plugin-selectTwo]').each(function () {
				if (!jQuery(this).hasClass('select2-hidden-accessible')) {
					jQuery(this).select2({ width: '100%' });
				}
			});
		}

		function addCard() {
			var html = tpl.innerHTML.replace(/__IDX__/g, seq);
			var wrap = document.createElement('div');
			wrap.innerHTML = html;
			var card = wrap.querySelector('.group-card');
			if (!card) { return; }
			list.appendChild(card);
			seq++;
			reindex();
			initSelect2(card);
		}

		if (addBtn) {
			addBtn.addEventListener('click', function (e) { e.preventDefault(); addCard(); });
		}

		list.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('.remove-group-btn') : null;
			if (!btn) { return; }
			e.preventDefault();
			var card = btn.closest('.group-card');
			if (!card) { return; }
			if (hasSelect2()) {
				jQuery(card).find('.select2-hidden-accessible').select2('destroy');
			}
			card.parentNode.removeChild(card);
			reindex();
		});

		// Safety net: normalise indices right before the form submits.
		var form = list.closest ? list.closest('form') : null;
		if (form) {
			form.addEventListener('submit', function () { reindex(); });
		}

		reindex();
	})();
</script>

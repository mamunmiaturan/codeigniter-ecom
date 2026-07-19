<section class="panel">
	<?php
	$create_active = isset($validation_error);
	$page_tabs = [
		['id' => 'list', 'icon' => 'fas fa-list-ul', 'label' => translate('role') . " " . translate('list'), 'active' => !$create_active],
		['id' => 'organogram', 'icon' => 'fas fa-sitemap', 'label' => translate('organogram') ?: 'Organogram', 'active' => false],
	];
	if (get_permission('role_permission', 'is_add')) {
		$page_tabs[] = ['id' => 'create', 'icon' => 'far fa-edit', 'label' => translate('create') . " " . translate('role'), 'active' => $create_active];
	}
	$this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs]);

	// id -> name map (from the full role list) for showing parent names.
	$role_name_by_id = [];
	foreach (($parent_options ?? []) as $__r) {
		$role_name_by_id[(int) $__r['id']] = $__r['name'];
	}

	// Recursive organogram renderer.
	if (!function_exists('render_role_org_node')) {
		function render_role_org_node(array $node)
		{
			$lvl = isset($node['level']) ? (int) $node['level'] : 0;
			echo '<li><div class="org-node">'
				. '<span class="org-name">' . html_escape($node['name']) . '</span>'
				. '<span class="org-level">' . translate('level') . ' ' . $lvl . '</span>'
				. '</div>';
			if (!empty($node['children'])) {
				echo '<ul>';
				foreach ($node['children'] as $child) {
					render_role_org_node($child);
				}
				echo '</ul>';
			}
			echo '</li>';
		}
	}
	?>
	<div id="list" class="tab-pane <?= !$create_active ? 'active' : ''; ?>">
		<div class="mb-md">
			<table class="table table-bordered table-hover table-condensed table-default">
				<thead>
					<tr>
						<th><?php echo translate('sl'); ?></th>
						<th><?php echo translate('role') . " " . translate('name'); ?></th>
						<th><?php echo translate('level'); ?></th>
						<th><?php echo translate('parent') ?: 'Parent'; ?></th>
						<th><?php echo translate('system_role'); ?></th>
						<th><?php echo translate('action'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (count($roles)) {
						$count = 1;
						foreach ($roles as $row):
					?>
							<tr>
								<td><?php echo $count++; ?></td>
								<td><?php echo html_escape($row['name']); ?></td>
								<td><span class="label label-default"><?php echo isset($row['level']) ? (int) $row['level'] : '-'; ?></span></td>
								<td><?php
									$pid = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
									echo $pid && isset($role_name_by_id[$pid]) ? html_escape($role_name_by_id[$pid]) : '<span class="text-muted">&mdash;</span>';
								?></td>
								<td><?php echo html_escape($row['is_system'] ? translate('yes') :  translate('no')); ?></td>
								<td>
									<?php if (get_permission('role_permission', 'is_edit')) { ?>
										<a class="btn btn-default btn-circle icon" data-toggle="tooltip" data-original-title="<?php echo translate('edit'); ?>" href="<?php echo base_url('role/' . route_hash('edit') . '/' . encrypt_id($row['id'])); ?>"><i class="fas fa-pen-nib"></i></a>
										<?php if ($row['id'] != 1) { ?>
											<a class="btn btn-default btn-sm role-action-btn" href="<?php echo base_url('role/' . route_hash('permission') . '/' . encrypt_id($row['id'])); ?>"><i class="fab fa-buromobelexperte"></i> <?php echo translate('permission'); ?></a>
										<?php } ?>
										<a class="btn btn-default btn-sm role-action-btn"
										   href="javascript:void(0);"
										   onclick="confirm_regenerate_sidebar('<?php echo html_escape(base_url('role/regenerate_sidebar/' . encrypt_id($row['id']) . '?return=list')); ?>');">
											<i class="fas fa-sync-alt"></i> <?php echo translate('regenerate_sidebar'); ?>
										</a>
									<?php } ?>
									<?php if (!$row['is_system']) { ?>
										<?php if (get_permission('role_permission', 'is_delete')) { ?>
											<?php echo btn_delete('role/' . route_hash('delete') . '/' . $row['id']); ?>
										<?php } ?>
									<?php } ?>
								</td>
							</tr>
					<?php endforeach;
					} ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Organogram (role hierarchy tree by parent/child) -->
	<div id="organogram" class="tab-pane">
		<div class="role-org">
			<?php if (!empty($role_tree)): ?>
				<ul class="org-tree">
					<?php foreach ($role_tree as $__node) { render_role_org_node($__node); } ?>
				</ul>
			<?php else: ?>
				<p class="text-muted text-center"><?php echo translate('no_information_available'); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if (get_permission('role_permission', 'is_add')) { ?>
		<div class="tab-pane <?php echo (isset($validation_error) ? 'active' : ''); ?>" id="create">
			<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal')); ?>
			<div class="form-group <?php if (form_error('role')) echo 'has-error'; ?>">
				<label class="col-md-2 control-label"><?php echo translate('role') . " " . translate('name'); ?> <span class="required">*</span></label>
				<div class="col-md-10 mb-sm">
					<input type="text" class="form-control" name="role" value="<?php echo set_value('role'); ?>">
					<span class="error"><?php echo form_error('role'); ?></span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-2 control-label"><?php echo translate('level'); ?></label>
				<div class="col-md-10 mb-sm">
					<input type="number" min="1" step="1" class="form-control" name="level" value="<?php echo set_value('level', '50'); ?>" style="max-width:160px;">
					<span class="help-block text-muted"><?php echo translate('role_level_hint') ?: 'Lower number = higher authority. A role can manage roles with a higher number than its own. (Superman 0, Admin 1, …)'; ?></span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-2 control-label"><?php echo translate('parent') ?: 'Parent'; ?> <?php echo translate('role'); ?></label>
				<div class="col-md-10 mb-sm">
					<select name="parent_id" class="form-control" style="max-width:320px;">
						<option value="0"><?php echo translate('none') ?: 'None (top level)'; ?></option>
						<?php foreach (($parent_options ?? []) as $__opt): ?>
							<option value="<?php echo (int) $__opt['id']; ?>" <?php echo set_select('parent_id', (string) $__opt['id']); ?>>
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
						<button type="submit" name="save" value="1" class="btn btn-default btn-block"><i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?></button>
					</div>
				</div>
			</footer>
			<?php echo form_close(); ?>
		</div>
	<?php } ?>
	</div>
	<?php $this->load->view('layout/_page_tabs_end'); ?>
</section>

<style>
	/* ---- Role organogram (top-down org chart) ---- */
	.role-org { padding: 24px 12px; overflow-x: auto; }
	.org-tree, .org-tree ul { list-style: none; margin: 0; padding: 0; display: flex; justify-content: center; }
	.org-tree ul { padding-top: 22px; position: relative; }
	.org-tree li { position: relative; padding: 22px 12px 0; text-align: center; }
	/* connector lines */
	.org-tree li::before, .org-tree li::after {
		content: ''; position: absolute; top: 0; right: 50%; width: 50%; height: 22px;
		border-top: 2px solid #d0d5dd;
	}
	.org-tree li::after { right: auto; left: 50%; border-left: 2px solid #d0d5dd; }
	.org-tree li:only-child::before, .org-tree li:only-child::after { display: none; }
	.org-tree li:first-child::before, .org-tree li:last-child::after { border: 0; }
	.org-tree li:last-child::before { border-right: 2px solid #d0d5dd; border-radius: 0 6px 0 0; }
	.org-tree li:first-child::after { border-radius: 6px 0 0 0; }
	.org-tree ul::before {
		content: ''; position: absolute; top: 0; left: 50%; height: 22px; border-left: 2px solid #d0d5dd;
	}
	.org-node {
		display: inline-flex; flex-direction: column; align-items: center; gap: 3px;
		min-width: 120px; padding: 10px 16px; border: 1px solid #e4e7ec; border-radius: 12px;
		background: #fff; box-shadow: 0 2px 6px rgba(20,20,50,.06); transition: .15s;
	}
	.org-node:hover { border-color: #5956ea; box-shadow: 0 6px 16px rgba(89,86,234,.15); }
	.org-node .org-name { font-weight: 700; color: #1f2937; font-size: .92rem; }
	.org-node .org-level { font-size: .72rem; color: #6b7280; background: #f2f4f7; padding: 1px 8px; border-radius: 100px; }
	html.dark .org-node { background: #2b2b2b; border-color: #3a3a3a; }
	html.dark .org-node .org-name { color: #e5e7eb; }
	html.dark .org-node .org-level { background: #1f1f1f; color: #9aa0ac; }
	html.dark .org-tree li::before, html.dark .org-tree li::after,
	html.dark .org-tree li:last-child::before, html.dark .org-tree ul::before { border-color: #4a4a4a; }
</style>
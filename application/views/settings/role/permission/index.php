<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title">
            <i class="fab fa-buromobelexperte text-primary"></i>
            <span><?php echo translate('role_permission_for') . " : "; ?><strong><?php echo html_escape(get_type_name_by_id('roles', (int) ($role_id_dec ?? 0))); ?></strong></span>
            <div class="pull-right" style="margin-top: -6px; margin-left: auto; display: flex; align-items: center; gap: 8px;">
                <?php if (get_permission('role_permission', 'is_edit')) { ?>
                    <a href="javascript:void(0);"
                       class="btn btn-default btn-sm"
                       data-toggle="tooltip"
                       data-original-title="<?php echo translate('regenerate_sidebar'); ?>"
                       onclick="confirm_regenerate_sidebar('<?php echo html_escape(base_url('role/regenerate_sidebar/' . $role_id)); ?>');">
                        <i class="fas fa-sync-alt"></i> <?php echo translate('regenerate_sidebar'); ?>
                    </a>
                <?php } ?>
                <div class="permission-search-wrapper" style="margin-bottom: 0; max-width: 220px;">
                    <i class="fas fa-search" style="left: 10px; font-size: 12px; top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" id="permissionSearch" class="form-control" placeholder="<?php echo translate('search'); ?>..." style="height: 28px; padding-left: 28px; font-size: 12px; border-radius: 6px; width: 100%;">
                </div>
            </div>
        </h4>
    </header>
    <?php echo form_open_multipart($this->uri->uri_string()); ?>
    <input type="hidden" name="role_id" value="<?php echo html_escape($role_id); ?>">
    <div class="panel-body">

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-condensed mt-sm permission-table" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th><?php echo translate('feature'); ?></th>
                        <th width="12%">
                            <div class="checkbox-replace">
                                <label class="i-checks"><input type="checkbox" id="all_view" value="1"><i></i> <?php echo translate('view'); ?></label>
                            </div>
                        </th>
                        <th width="12%">
                            <div class="checkbox-replace">
                                <label class="i-checks"><input type="checkbox" id="all_add" value="1"><i></i> <?php echo translate('add'); ?></label>
                            </div>
                        </th>
                        <th width="12%">
                            <div class="checkbox-replace">
                                <label class="i-checks"><input type="checkbox" id="all_edit" value="1"><i></i> <?php echo translate('edit'); ?></label>
                            </div>
                        </th>
                        <th width="12%">
                            <div class="checkbox-replace">
                                <label class="i-checks"><input type="checkbox" id="all_delete" value="1"><i></i> <?php echo translate('delete'); ?></label>
                            </div>
                        </th>
                        <th width="12%" class="text-center">
                            <div class="checkbox-replace">
                                <label class="i-checks"><input type="checkbox" id="all_row_select" value="1"><i></i> <?php echo translate('all'); ?></label>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($modules)) {
                        foreach ($modules as $module):
                            $permissions = $this->role_model->check_permissions_for_granter(
                                (int) $module['id'],
                                (int) $role_id_dec,
                                (int) ($granter_role_id ?? loggedin_role_id())
                            );
                            $feature_count = count($permissions);
                            if ($feature_count == 0) continue;
                    ?>
                            <!-- Module Group Header with Accordion and Stats -->
                            <tr class="module-header-row" data-module-id="<?php echo $module['id']; ?>">
                                <th colspan="6">
                                    <div class="module-header-container">
                                        <div class="module-header-title" data-module-id="<?php echo $module['id']; ?>">
                                            <i class="fas fa-chevron-down chevron-icon text-primary"></i>
                                            <span><i class="fas fa-layer-group text-primary"></i> <?php echo html_escape($module['name']); ?></span>
                                            <span class="permission-stats-badge"><?php echo $feature_count; ?> <?php echo translate('features'); ?></span>
                                        </div>
                                        <button type="button" class="btn btn-toggle-module" data-module-id="<?php echo $module['id']; ?>">
                                            <i class="fas fa-check-double"></i> <?php echo translate('toggle_group'); ?>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                            <?php
                            foreach ($permissions as $permission):
                            ?>
                                <input type="hidden" name="privileges[<?php echo html_escape($permission['id']); ?>][privileges_id]" value="<?php echo html_escape($permission['id']); ?>">
                                <tr class="permission-row module-group-<?php echo $module['id']; ?>" data-search-term="<?php echo strtolower($module['name'] . ' ' . $permission['name']); ?>">
                                    <td class="pl-xl feature-name-cell">
                                        <i class="far fa-arrow-alt-circle-right"></i> <?php echo html_escape($permission['name']); ?>
                                    </td>
                                    <td class="checkbox-cell">
                                        <?php if ($permission['show_view']) { ?>
                                            <div class="checkbox-replace">
                                                <label class="i-checks"><input type="checkbox" class="cb_view cb_action" name="privileges[<?php echo html_escape($permission['id']); ?>][view]" <?php echo ($permission['is_view'] == 1 ? 'checked' : ''); ?> value="1">
                                                    <i></i>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="checkbox-cell">
                                        <?php if ($permission['show_add']) { ?>
                                            <div class="checkbox-replace">
                                                <label class="i-checks"><input type="checkbox" class="cb_add cb_action" name="privileges[<?php echo html_escape($permission['id']); ?>][add]" <?php echo ($permission['is_add'] == 1 ? 'checked' : ''); ?> value="1">
                                                    <i></i>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="checkbox-cell">
                                        <?php if ($permission['show_edit']) { ?>
                                            <div class="checkbox-replace">
                                                <label class="i-checks"><input type="checkbox" class="cb_edit cb_action" name="privileges[<?php echo html_escape($permission['id']); ?>][edit]" <?php echo ($permission['is_edit'] == 1 ? 'checked' : ''); ?> value="1">
                                                    <i></i>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="checkbox-cell">
                                        <?php if ($permission['show_delete']) { ?>
                                            <div class="checkbox-replace">
                                                <label class="i-checks"><input type="checkbox" class="cb_delete cb_action" name="privileges[<?php echo html_escape($permission['id']); ?>][delete]" <?php echo ($permission['is_delete'] == 1 ? 'checked' : ''); ?> value="1">
                                                    <i></i>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="checkbox-replace">
                                            <label class="i-checks">
                                                <input type="checkbox" class="cb_row_toggle"><i></i>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    <?php endforeach;
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="panel-footer">
        <div class="row">
            <div class="col-md-offset-9 col-md-3">
                <button type="submit" name="save" value="1" class="btn btn-default btn-block"><i class="fas fa-save"></i> <?php echo translate('save_permissions'); ?></button>
            </div>
        </div>
    </footer>
    <?php echo form_close(); ?>
</section>

<script src="<?php echo base_url('assets/backend/js/app/permissions.js'); ?>"></script>
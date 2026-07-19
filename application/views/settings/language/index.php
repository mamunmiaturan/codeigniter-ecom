<div class="row">
	<div class="col-md-12">
		<section class="panel">
            <?php
            $is_view = get_permission('language', 'is_view');
            $is_add = get_permission('language', 'is_add');
            $page_tabs = [];
            if ($is_view) {
                $page_tabs[] = ['id' => 'list', 'icon' => 'fas fa-list-ul', 'label' => translate('language') . " " . translate('list'), 'active' => true];
            }
            if ($is_add) {
                $page_tabs[] = ['id' => 'create', 'icon' => 'far fa-edit', 'label' => translate('add') . " " . translate('language'), 'active' => !$is_view];
            }
            $this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs]);
            ?>
            <?php if ($is_view) { ?>
                <div id="list" class="tab-pane active">
							<div class="table-responsive">
								<table class="table table-bordered table-hover table-condensed table-default" width="100%">
									<thead>
										<tr>
											<th><?php echo translate('sl'); ?></th>
											<th><?php echo translate('language'); ?></th>
											<th><?php echo translate('flag'); ?></th>
											<th><?php echo translate('status'); ?></th>
											<th><?php echo translate('created_at'); ?></th>
											<th><?php echo translate('updated_at'); ?></th>
											<th><?php echo translate('action'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php
										$count = 1;
										foreach ($languages as $row) {
										?>
											<tr>
												<td><?php echo $count++; ?></td>
												<td><?php echo ucwords($row['name']); ?></td>
												<td><img class="lang-img" src="<?php echo $this->app_lib->get_lang_image_url($row['id'], false); ?>" loading="lazy" /></td>
												<td>
													<div class="material-switch ml-xs">
														<input class="switch_lang" id="switch_<?php echo $row['id']; ?>" data-lang="<?php echo html_escape(md5($row['id'])); ?>" name="sw_lang<?php echo $row['id']; ?>"
															type="checkbox" <?php echo ($row['status'] == "Active" ? 'checked' : ''); ?> <?php echo (!get_permission('language', 'is_edit') ? 'disabled' : ''); ?> />
														<label for="switch_<?php echo $row['id']; ?>" class="label-primary"></label>
													</div>
												</td>
												<td><?php echo time_ago($row['created_at']); ?></td>
												<td><?php echo time_ago($row['updated_at']); ?></td>
												<td class="min-w-md">
													<?php if (get_permission('language', 'is_edit')) {
														$lang_field = (!empty($row['code']) ? $row['code'] : strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $row['name'])));
													?>
														<a href="<?php echo base_url('language/' . route_hash('word_update') . '/' . md5($lang_field)); ?>" class="btn btn-default btn-circle"><i class="fas fa-external-link-alt"></i> <?php echo translate('edit_word'); ?></a>
														<a class="btn btn-default btn-circle icon" href="<?php echo base_url('language/' . route_hash('edit') . '/' . md5($row['id'])); ?>" data-toggle="tooltip" data-original-title="<?php echo translate('edit'); ?>"><i class="fas fa-pen-nib"></i></a>
													<?php }
													if (get_permission('language', 'is_delete')) { ?>
														<?php echo btn_delete('language/' . route_hash('delete') . '/' . md5($row['id'])); ?>
													<?php } ?>

												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					<?php }
					if (get_permission('language', 'is_add')) { ?>
						<div class="tab-pane <?php echo !get_permission('language', 'is_view') ? 'active' : ''; ?>" id="create">
							<?php echo form_open_multipart($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
							<div class="form-group mb-md">
								<label class="col-md-2 control-label"><?php echo translate('language'); ?> <span class="required">*</span></label>
								<div class="col-md-10">
									<input type="text" class="form-control" name="name" required value="" />
								</div>
							</div>
							<div class="form-group mb-md">
								<label class="col-md-2 control-label"><?php echo translate('flag_icon'); ?></label>
								<div class="col-md-10">
									<input type="file" name="flag" data-height="90" class="dropify" data-allowed-file-extensions="jpg png bmp" />
								</div>
							</div>
							<footer class="panel-footer mt-lg">
								<div class="row">
									<div class="col-md-2 col-md-offset-10">
										<button type="submit" class="btn btn-default btn-block" name="save" value="1">
											<i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?>
										</button>
									</div>
								</div>
							</footer>
							<?php echo form_close(); ?>
						</div>
					<?php } ?>
    </div>
    <?php $this->load->view('layout/_page_tabs_end'); ?>
</section>
	</div>
</div>
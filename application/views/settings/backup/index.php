<section class="panel">
    <?php
    $page_tabs = [
        ['id' => 'database_backup', 'icon' => 'fas fa-database', 'label' => translate('database') . " " . translate('list'), 'active' => true],
    ];
    if (get_permission('database_restore', 'is_add')) {
        $page_tabs[] = ['id' => 'restore_database', 'icon' => 'fas fa-upload', 'label' => translate('restore') . " " . translate('database'), 'active' => false];
    }
    
    $page_tab_actions = '';
    if (get_permission('database_backup', 'is_add')) {
        ob_start();
        echo form_open($this->uri->uri_string(), array('class' => 'tabs-header-action-form'));
        echo '<button class="btn btn-primary btn-sm" type="submit" name="backup" value="1"><i class="fas fa-paste"></i> ' . translate('create_backup') . '</button>';
        echo form_close();
        $page_tab_actions = ob_get_clean();
    }
    $this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs, 'page_tab_actions' => $page_tab_actions]);
    ?>
			<div class="tab-pane box active" id="database_backup">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-condensed table-default">
						<thead>
							<tr>
								<th width="60"><?php echo translate('sl'); ?></th>
								<th><?php echo translate('file') . " " . translate('name'); ?></th>
								<th><?php echo translate('backup_size'); ?></th>
								<th><?php echo translate('date'); ?></th>
								<th><?php echo translate('action'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$count = 1;
							$files = get_filenames(FCPATH . '/uploads/db_backup/');
							if (count($files)) {
								foreach ($files as $file) {
									$sqlpath = './uploads/db_backup/' . $file;
							?>
									<tr>
										<td><?php echo $count++; ?></td>
										<td><?php echo ($file); ?></td>
										<td><?php echo bytesToSize($sqlpath); ?></td>
										<td><?php echo date('Y-m-d h:i a', filectime($sqlpath)); ?></td>
										<td>
											<?php if (get_permission('database_backup', 'is_add')) { ?>
												<a href="<?php echo base_url('backup/download?file=' . $file) ?>" class="btn btn-circle icon btn-default"><i class="fas fa-download"></i></a>
											<?php }
											if (get_permission('database_backup', 'is_delete')) { ?>
												<?php echo btn_delete('backup/' . route_hash('delete') . '/' . $file); ?>
											<?php } ?>
										</td>
									</tr>
							<?php }
							} else {
								echo '<tr><td colspan="5"><h5 class="text-danger text-center">' . translate('no_information_available') . '</td></tr>';
							} ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php if (get_permission('database_restore', 'is_add')) { ?>
				<div class="tab-pane box" id="restore_database">
					<?php echo form_open_multipart(base_url('backup/' . route_hash('restore_file')), array('class' => 'form-horizontal validate')); ?>
					<div class="form-group mb-lg">
						<label class="col-md-2 control-label"><?php echo translate('file_upload'); ?> <span class="required" aria-required="true">*</span></label>
						<div class="col-md-10">
							<input type="file" name="uploaded_file" class="dropify" data-height="140" data-allowed-file-extensions="zip" required />
						</div>
					</div>
					<footer class="panel-footer">
						<div class="row">
							<div class="col-md-2 col-sm-offset-10">
								<button type="submit" class="btn btn-default btn-block"><i class="fas fa-cloud-upload-alt"></i> <?php echo translate('restore'); ?></button>
							</div>
						</div>
					</footer>
					<?php echo form_close(); ?>
				</div>
			<?php } ?>
    </div>
    <?php $this->load->view('layout/_page_tabs_end'); ?>
</section>
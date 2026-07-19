<div class="row">
	<div class="col-md-12">
		<section class="panel">
			<div class="tabs-custom">
				<ul class="nav nav-tabs">
					<li class="">
						<a href="<?php echo base_url('language'); ?>"><i class="fas fa-list-ul"></i> <?php echo translate('language') . " " . translate('list'); ?></a>
					</li>
					<li class="active">
						<a href="#edit" data-toggle="tab"><i class="far fa-edit"></i> <?php echo translate('rename') . " " . translate('language'); ?></a>
					</li>
				</ul>
				<div class="tab-content">
					<div class="tab-pane active" id="edit">
						<?php echo form_open_multipart($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
						<div class="form-group mb-md">
							<label class="col-md-2 control-label"><?php echo translate('language'); ?> <span class="required">*</span></label>
							<div class="col-md-10">
								<input type="text" class="form-control" name="name" required value="<?php echo html_escape($languages['name']); ?>" />
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
									<button type="submit" class="btn btn-default btn-block" name="update" value="1">
										<i class="fas fa-edit"></i> <?php echo translate('update'); ?>
									</button>
								</div>
							</div>
						</footer>
						<?php echo form_close(); ?>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>
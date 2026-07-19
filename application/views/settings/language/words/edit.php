<div class="row">
	<div class="col-md-12">
		<?php echo form_open($this->uri->uri_string(), array('class' => 'validate')); ?>
		<section class="panel">
			<header class="panel-heading">
				<div class="row">
					<div class="col-md-3">
						<h4 class="panel-title"><i class="fas fa-pen-nib"></i> <?php echo translate('edit_word'); ?></h4>
					</div>
					<div class="col-md-9 text-right">
						<button type="submit" class="btn btn-danger btn-sm btn_bulk_delete" name="bulk_delete" value="1" style="display: none; margin-right: 5px;" onclick="return confirm('<?php echo translate('are_you_sure'); ?>')">
							<i class="fas fa-trash-alt"></i> <?php echo translate('bulk_delete'); ?>
						</button>
						
						<a href="javascript:void(0);" class="btn btn-info btn-sm" onclick="mfp_modal('#import_word_modal');" style="margin-right: 5px;">
							<i class="fas fa-file-csv"></i> <?php echo translate('import'); ?> CSV
						</a>
						<a href="javascript:void(0);" class="btn btn-primary btn-sm" onclick="mfp_modal('#add_word_modal');">
							<i class="fas fa-plus-circle"></i> <?php echo translate('add_word'); ?>
						</a>
						<button type="button" class="btn btn-success btn-sm btn-bulk-translate" style="margin-right: 5px;">
							<i class="fas fa-magic"></i> <?php echo translate('bulk_auto_translate'); ?>
						</button>
						<button type="submit" class="btn btn-default btn-sm" name="update" value="1" style="margin-right: 5px;">
							<i class="fas fa-edit"></i> <?php echo translate('update'); ?>
						</button>
					</div>
				</div>
			</header>
			<input type="hidden" name="select_lang" value="<?php echo html_escape($select_language); ?>">
			<div class="panel-body">
				<!-- Premium Live Search/Filter Bar -->
				<div class="row" style="margin-bottom: 20px;">
					<div class="col-md-5">
						<div class="input-group">
							<span class="input-group-addon"><i class="fas fa-search"></i></span>
							<input type="text" id="phraseSearchInput" class="form-control input-sm" placeholder="Type to search word key or translation in real-time..." autocomplete="off">
						</div>
					</div>
					<div class="col-md-7 text-right" style="line-height: 30px;">
						<span id="phraseSearchSummary" class="text-muted" style="font-weight: 600; font-size: 12px; letter-spacing: 0.5px;">
							Showing all phrases
						</span>
					</div>
				</div>

				<table class="table table-bordered table-hover table-condensed" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th width="3%">
								<div class="checkbox-replace">
									<label class="i-checks"><input type="checkbox" id="selectAllchkbox"><i></i></label>
								</div>
							</th>
							<th width="10%"><?php echo translate('sl'); ?></th>
							<th width="40%"><?php echo translate('word'); ?></th>
							<th width="40%"><?php echo translate('translations'); ?></th>
							<th width="10%" class="text-center"><?php echo translate('action'); ?></th>
						</tr>
					</thead>
					<tbody class="cb-chk-area">
						<?php
						$count = 1;
						foreach ($langresult as $row) {
						?>
							<tr>
								<td class="checked-area">
									<div class="checkbox-replace">
										<label class="i-checks">
											<input type="checkbox" class="cb_bulk_delete" name="phrase_ids[]" value="<?php echo $row['id']; ?>"> <i></i>
										</label>
									</div>
								</td>
								<td><?php echo $count++; ?></td>
								<td><?php echo ucwords(str_replace('_', ' ',  $row['word_key'])); ?></td>
								<td>
									<div class="input-group">
										<span class="input-group-addon"><span class="icon"><i class="far fa-comment-alt"></i></span></span>
										<input type="text" class="form-control translation-input" placeholder="Set Word Translation" id="trans_<?php echo $row['id']; ?>" name="word[<?php echo html_escape($row['id']); ?>][field]" value="<?php echo isset($row[$select_language]) ? $row[$select_language] : ''; ?>" />
										<span class="input-group-btn">
											<button class="btn btn-primary btn-auto-translate" type="button" data-id="<?php echo $row['id']; ?>" data-key="<?php echo html_escape($row['word_key']); ?>" title="Auto-Translate from English">
												<i class="fas fa-magic"></i>
											</button>
										</span>
									</div>
								</td>
								<td class="text-center">
									<?php echo btn_delete('language/' . route_hash('delete_phrase') . '/' . md5($row['id'])); ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-8 col-md-4 text-right">
						<button type="submit" class="btn btn-danger btn_bulk_delete" name="bulk_delete" value="1" style="display: none;" onclick="return confirm('<?php echo translate('are_you_sure'); ?>')">
							<i class="fas fa-trash-alt"></i> <?php echo translate('bulk_delete'); ?>
						</button>
						<button type="submit" class="btn btn-default" name="update" value="1">
							<i class="fas fa-edit"></i> <?php echo translate('update'); ?>
						</button>
					</div>
				</div>
			</footer>
			<?php echo form_close(); ?>
		</section>
	</div>
</div>

<!-- Add Word Modal -->
<div id="add_word_modal" class="zoom-anim-dialog modal-block modal-block-primary mfp-hide">
	<section class="panel">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-plus-circle"></i> <?php echo translate('add_word'); ?></h4>
		</header>
		<?php echo form_open(base_url('language/' . route_hash('add_phrase')), array('class' => 'validate')); ?>
		<div class="panel-body">
			<div class="form-group mb-md">
				<label class="control-label"><?php echo translate('add_bulk_words'); ?> <span class="required">*</span></label>
				<textarea class="form-control" name="word" rows="6" required placeholder="Format - word_key:Translation Text (One per line)&#10;Example:&#10;login_title:Login to Account&#10;email_label:Email Address"></textarea>
				<p class="text-muted mt-sm">
					<small><i class="fas fa-info-circle"></i> Enter each word in a new line. Format is <code>word_key:Translation</code>. If you don't provide a translation, the key will be used as a default.</small>
				</p>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-12 text-right">
					<button type="submit" class="btn btn-default"><?php echo translate('save'); ?></button>
					<button class="btn btn-default modal-dismiss"><?php echo translate('cancel'); ?></button>
				</div>
			</div>
		</footer>
		<?php echo form_close(); ?>
	</section>
</div>

<!-- Import Word Modal -->
<div id="import_word_modal" class="zoom-anim-dialog modal-block modal-block-primary mfp-hide">
	<section class="panel">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-file-csv"></i> <?php echo translate('import'); ?> CSV / TXT</h4>
		</header>
		<?php echo form_open_multipart(base_url('language/import_phrase'), array('class' => 'validate')); ?>
		<input type="hidden" name="select_lang" value="<?php echo html_escape($select_language); ?>">
		<div class="panel-body">
			<div class="form-group mb-md">
				<label class="control-label">Select File (CSV or TXT) <span class="required">*</span></label>
				<input type="file" name="file" class="form-control" accept=".csv,.txt" required />
				<p class="text-muted mt-sm">
					<small><i class="fas fa-info-circle"></i> File should have only one column of human-readable phrases (one per line). The system will automatically slugify the phrase to generate its unique <code>word_key</code>.<br/>Example:<br/><code>Dashboard Overview</code><br/><code>Sign In</code></small>
				</p>
				<div class="mt-xs">
					<a href="<?php echo base_url('uploads/import/language_sample.csv'); ?>" class="btn btn-xs btn-default" download>
						<i class="fas fa-download"></i> Download Sample CSV
					</a>
				</div>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-12 text-right">
					<button type="submit" class="btn btn-default"><?php echo translate('upload'); ?></button>
					<button class="btn btn-default modal-dismiss"><?php echo translate('cancel'); ?></button>
				</div>
			</div>
		</footer>
		<?php echo form_close(); ?>
	</section>
</div>

<script type="text/javascript">
	function syncCsrf(newCsrf) {
		if (!newCsrf) return;
		csrfData = newCsrf;
		$.ajaxSetup({ data: csrfData });
		var name = Object.keys(newCsrf)[0];
		var val  = newCsrf[name];
		$('input[name="' + name + '"]').val(val);
	}

	$(document).ready(function () {
		// handle bulk delete button visibility
		$('.cb_bulk_delete, #selectAllchkbox').on('change', function() {
			if ($('.cb_bulk_delete:checked').length > 0) {
				$('.btn_bulk_delete').fadeIn();
			} else {
				$('.btn_bulk_delete').fadeOut();
			}
		});

		// Auto translate single word via AJAX
		$('.btn-auto-translate').on('click', function(e) {
			e.preventDefault();
			var btn = $(this);
			var id = btn.data('id');
			var wordKey = btn.data('key');
			var targetLang = $('input[name="select_lang"]').val();
			var inputField = $('#trans_' + id);

			// Disable button and show spinner loader
			btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

			$.ajax({
				url: '<?php echo base_url('language/' . route_hash('auto_translate_phrase')); ?>',
				type: 'POST',
				dataType: 'json',
				data: $.extend({ word_key: wordKey, target_lang: targetLang }, csrfData),
				success: function(response) {
					if (response.csrf) { syncCsrf(response.csrf); }
					if (response.status === 'success') {
						inputField.val(response.translation);
						inputField.css('background-color', '#d4edda').animate({backgroundColor: '#ffffff'}, 1500);
					} else {
						swal({ title: 'Error', text: response.message || 'Translation failed', type: 'error', confirmButtonClass: 'btn btn-default swal2-btn-default', buttonsStyling: false });
					}
				},
				error: function(xhr) {
					var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Google Translate could not be reached.';
					swal({ title: 'Translation Error', text: msg, type: 'error', confirmButtonClass: 'btn btn-default swal2-btn-default', buttonsStyling: false });
				},
				complete: function() {
					btn.prop('disabled', false).html('<i class="fas fa-magic"></i>');
				}
			});
		});

		// Bulk Auto-Translate Empty Words on the current page
		$('.btn-bulk-translate').on('click', function(e) {
			e.preventDefault();
			var bulkBtn = $(this);
			var targetLang = $('input[name="select_lang"]').val();
			
			// Find all empty translation fields in the table
			var emptyInputs = [];
			$('.translation-input').each(function() {
				var input = $(this);
				if ($.trim(input.val()) === '') {
					var rowBtn = input.closest('tr').find('.btn-auto-translate');
					if (rowBtn.length > 0) {
						emptyInputs.push({
							input: input,
							btn: rowBtn,
							id: rowBtn.data('id'),
							key: rowBtn.data('key')
						});
					}
				}
			});

			if (emptyInputs.length === 0) {
				swal({
					title: 'Already Translated!',
					text: 'All words on this page are already translated.',
					type: 'info',
					confirmButtonClass: 'btn btn-default swal2-btn-default',
					buttonsStyling: false
				});
				return;
			}

			swal({
				title: "<?php echo translate('are_you_sure'); ?>",
				text: "Do you want to auto-translate " + emptyInputs.length + " empty word(s) on this page?",
				type: "warning",
				showCancelButton: true,
				confirmButtonClass: "btn btn-default swal2-btn-default",
				cancelButtonClass: "btn btn-default swal2-btn-default",
				confirmButtonText: "<?php echo translate('yes_continue'); ?>",
				cancelButtonText: "<?php echo translate('cancel'); ?>",
				buttonsStyling: false
			}).then((result) => {
				if (result.value) {
					// Disable actions to avoid race conditions
					bulkBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Translating (0/' + emptyInputs.length + ')');
					$('.btn-auto-translate').prop('disabled', true);

					var completed = 0;
					
					// Process sequentially with a light human-like delay to behave friendly with APIs
					function translateNext(index) {
						if (index >= emptyInputs.length) {
							// Trigger Premium SweetAlert Success Toast & Auto-Save Form Submission
							swal({
								title: 'Translation Complete!',
								text: 'Auto-saving ' + completed + ' translation(s) and writing to JSON cache...',
								type: 'success',
								showConfirmButton: false,
								timer: 2000
							});

							// Append hidden update field to simulate update button click
							var form = bulkBtn.closest('form');
							form.append('<input type="hidden" name="update" value="1">');

							// Submit after 2 seconds to let the user see the success alert
							setTimeout(function() {
								form.submit();
							}, 2000);
							return;
						}

						var current = emptyInputs[index];
						current.btn.html('<i class="fas fa-spinner fa-spin"></i>');

						$.ajax({
							url: '<?php echo base_url('language/' . route_hash('auto_translate_phrase')); ?>',
							type: 'POST',
							dataType: 'json',
							data: $.extend({ word_key: current.key, target_lang: targetLang }, csrfData),
							success: function(response) {
								if (response.csrf) { syncCsrf(response.csrf); }
								if (response.status === 'success') {
									current.input.val(response.translation);
									current.input.css('background-color', '#d4edda').animate({backgroundColor: '#ffffff'}, 1200);
									completed++;
								}
							},
							complete: function() {
								current.btn.html('<i class="fas fa-magic"></i>');
								bulkBtn.html('<i class="fas fa-spinner fa-spin"></i> Translating (' + (index + 1) + '/' + emptyInputs.length + ')');
								
								// Human delay of 250ms before triggering next
								setTimeout(function() {
									translateNext(index + 1);
								}, 250);
							}
						});
					}

					translateNext(0);
				}
			});
		});

		// Premium Live Client-Side Phrase Search/Filter
		$('#phraseSearchInput').on('keyup', function() {
			var value = $(this).val().toLowerCase();
			var visibleCount = 0;
			var totalCount = 0;
			
			$('.cb-chk-area tr').each(function() {
				var wordText = $(this).find('td:nth-child(3)').text().toLowerCase();
				var transText = $(this).find('.translation-input').val().toLowerCase();
				var isMatch = wordText.indexOf(value) > -1 || transText.indexOf(value) > -1;
				
				$(this).toggle(isMatch);
				totalCount++;
				if (isMatch) visibleCount++;
			});
			
			if (value === '') {
				$('#phraseSearchSummary').html('<i class="fas fa-list-ul text-primary"></i> Showing all ' + totalCount + ' phrases');
			} else {
				$('#phraseSearchSummary').html('<i class="fas fa-search text-success"></i> Found ' + visibleCount + ' matching phrases out of ' + totalCount);
			}
		});

		// Initialize default search summary count
		var initialCount = $('.cb-chk-area tr').length;
		$('#phraseSearchSummary').html('<i class="fas fa-list-ul text-primary"></i> Showing all ' + initialCount + ' phrases');
	});
</script>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
$slug = $product['slug'];
$logged_in = function_exists('is_customer_loggedin') && is_customer_loggedin();
?>
<?php if ($logged_in): ?>
	<form action="<?php echo base_url('product/' . rawurlencode($slug) . '/review'); ?>" method="post" class="border rounded p-3">
		<h6 class="mb-2">Write a review</h6>
		<div class="mb-2">
			<label class="form-label small mb-1">Your rating</label>
			<select name="rating" class="form-select form-select-sm" required>
				<option value="5">★★★★★ (5)</option>
				<option value="4">★★★★☆ (4)</option>
				<option value="3">★★★☆☆ (3)</option>
				<option value="2">★★☆☆☆ (2)</option>
				<option value="1">★☆☆☆☆ (1)</option>
			</select>
		</div>
		<div class="mb-2"><input type="text" name="title" class="form-control form-control-sm" maxlength="200" placeholder="Title (optional)"></div>
		<div class="mb-2"><textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Share your experience with this product..."></textarea></div>
		<button class="btn btn-dark btn-sm" type="submit"><i class="bi bi-pencil-square me-1"></i> Submit review</button>
		<p class="text-muted small mb-0 mt-2">Reviews are published after moderation.</p>
	</form>
<?php else: ?>
	<a href="<?php echo base_url('account/login?redirect=' . urlencode(base_url('product/' . rawurlencode($slug)))); ?>" class="btn btn-outline-dark btn-sm">
		<i class="bi bi-pencil-square me-1"></i> Log in to write a review
	</a>
<?php endif; ?>

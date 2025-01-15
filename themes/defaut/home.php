<?php include 'inc/header.php'; ?>
	<main class="main">
		<div class="container">
			<div class="grid">
				<div class="<?= $contentClass ?>">
<?php include 'inc/posts.php'; ?>
				</div>
<?php
if (!defined('FULL_WIDTH')) {
	include 'inc/sidebar.php';
}
?>
			</div>
		</div>
	</main>
<?php
include 'inc/footer.php';

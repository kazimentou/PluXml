<?php include 'inc/header.php'; ?>
	<main class="main">
		<div class="container">
			<div class="grid">
				<div class="<?= $contentClass ?>">
					<ul class="repertory menu breadcrumb">
						<?php $plxShow->pageBlog(BLOG_PATTERN) ?>
						<li><?php $plxShow->pageTitle('#title '); ?></li>
					</ul>
<?php include 'inc/posts.php'; ?>
					<?php $plxShow->artFeed(); ?>
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

<?php
if (!defined('PLX_ROOT')) {
	exit;
}

include 'inc/header.php';
?>
	<main class="main">
		<div class="container">
			<div class="grid">
				<div class="<?= $contentClass ?>">
					<article class="article static" id="static-page-<?= $plxShow->staticId() ?>">
						<header>
							<h2><?php $plxShow->staticTitle(); ?></h2>
						</header>
<?php $plxShow->staticContent(); ?>
					</article>
				</div>
<?php
if(!defined('FULL_WIDTH')) {
	include 'inc/sidebar.php';
}
?>
			</div>
		</div>
	</main>
<?php
include 'inc/footer.php';

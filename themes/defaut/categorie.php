<?php include 'header.php'; ?>

	<main class="main">

		<div class="container">

			<div class="grid">

				<div class="content col <?= defined('FULL_WIDTH') ? '' : 'med-9' ?>">

					<ul class="repertory menu breadcrumb">
						<li><a href="<?php $plxShow->racine() ?>"><?php $plxShow->lang('HOME'); ?></a></li>
						<li><?php $plxShow->catName(); ?></li>
					</ul>

					<p><?php $plxShow->catDescription('#cat_description'); ?></p>
					<p><?php $plxShow->catThumbnail(); ?></p>

<?php include 'posts.php'; ?>

					<?php $plxShow->artFeed('rss', $plxShow->catId(), '<span><a class="rss" href="#feedUrl" title="#feedTitle">#feedName</a></span>'); ?>

				</div>

<?php
if (!defined('FULL_WIDTH')) {
    include 'sidebar.php';
}
?>

			</div>

		</div>

	</main>

<?php
include 'footer.php';

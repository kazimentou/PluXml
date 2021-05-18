<?php include 'header.php'; ?>

	<main class="main">

		<div class="container">

			<div class="grid">

				<div class="content col <?= defined('FULL_WIDTH') ? '' : 'med-9' ?>">

					<article class="article" id="post-<?php echo $plxShow->artId(); ?>">

						<header>
							<span class="art-date">
								<time datetime="<?php $plxShow->artDate('#num_year(4)-#num_month-#num_day'); ?>">
									<?php $plxShow->artDate('#num_day #month #num_year(4)'); ?>
								</time>
							</span>
							<h2>
								<?php $plxShow->artTitle(); ?>
							</h2>
							<div>
								<small>
<?php
if ($plxShow->authorCount > 1) {
    ?>
									<span class="written-by"><?php $plxShow->lang('WRITTEN_BY'); ?> <?php $plxShow->artAuthor() ?></span>
<?php
}
?>
									<span class="art-nb-com"><a href="#comments" title="<?php $plxShow->artNbCom(); ?>"><?php $plxShow->artNbCom(); ?></a></span>
								</small>
							</div>
							<div>
								<small>
									<span class="classified-in">
										<?php $plxShow->lang('CLASSIFIED_IN') ?> : <?php $plxShow->artCat() ?>
									</span>
									<span class="tags">
										<?php $plxShow->lang('TAGS') ?> : <?php $plxShow->artTags() ?>
									</span>
								</small>
							</div>
						</header>
						<main class="main-post">
<?php $plxShow->artThumbnail(); ?>
<?php $plxShow->artContent(); ?>
						</main>
					</article>

					<?php $plxShow->artAuthorInfos('<div class="author-infos">#art_authorinfos</div>'); ?>

					<?php include 'commentaires.php'; ?>

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

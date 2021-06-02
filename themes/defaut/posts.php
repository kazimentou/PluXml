<?php
if (!defined('PLX_ROOT')) {
    exit;
}

const ART_THUMBNAIL_TEMPLATE = '<a href="#art_url" class="art_thumbnail"><img src="#img_thumb_url" alt="#img_alt" title="#img_title" /></a>';

?>
<!-- begin of posts.php -->
		<div class="posts"> 
<?php
while ($plxShow->plxMotor->plxRecord_arts->loop()) {
    ?>
			<article class="article" id="post-<?= $plxShow->artId(); ?>">
				<header class="art-header">
					<span class="art-date"><time datetime="<?php $plxShow->artDate('#num_year(4)-#num_month-#num_day'); ?>"><?php $plxShow->artDate('#num_day #month #num_year(4)'); ?></time></span>
					<h2><?php $plxShow->artTitle('link'); ?></h2>
					<div>
						<small>
<?php
if ($plxShow->authorCount > 1) {
        ?>
						<span class="written-by"><?php $plxShow->lang('WRITTEN_BY'); ?> <?php $plxShow->artAuthor() ?></span>
<?php
    }

    if (!empty($plxShow->plxMotor->aConf['allow_com'])) {
        ?>
						<span class="art-nb-com"><?php $plxShow->artNbCom(); ?></span>
<?php
    } ?>
						</small>
					</div>
					<div>
						<small>
							<span class="classified-in"><?php $plxShow->lang('CLASSIFIED_IN') ?> : <?php $plxShow->artCat() ?></span>
							<span class="tags"><?php $plxShow->lang('TAGS') ?> : <?php $plxShow->artTags() ?></span>
						</small>
					</div>
				</header>
				<main class="main-post">
<?php $plxShow->artThumbnail(ART_THUMBNAIL_TEMPLATE); ?>
<?php $plxShow->artChapo($plxShow->getLang('READ_MORE')); ?>
				</main>

			</article>
<?php
}
?>
		</div>
		<nav class="pagination text-center">
<?php $plxShow->pagination(); ?>
		</nav>
<!-- end of posts.php -->

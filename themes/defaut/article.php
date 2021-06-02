<?php
if (!defined('PLX_ROOT')) {
    exit;
}

include 'header.php';
?>
<!-- begin of article.php -->
					<article class="article" id="post-<?php echo $plxShow->artId(); ?>">
						<header>
							<span class="art-date">
								<time datetime="<?php $plxShow->artDate('#num_year(4)-#num_month-#num_day'); ?>"><?php $plxShow->artDate('#num_day #month #num_year(4)'); ?></time>
							</span>
							<h2><span><?php $plxShow->artTitle(); ?></span></h2>
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
									<span class="classified-in"><?php $plxShow->lang('CLASSIFIED_IN') ?> : <?php $plxShow->artCat() ?></span>
									<span class="tags"><?php $plxShow->lang('TAGS') ?> : <?php $plxShow->artTags() ?></span>
								</small>
							</div>
						</header>
						<main class="main-post">
<?php $plxShow->artThumbnail(); ?>
<?php $plxShow->artContent(); ?>
						</main>
					</article>
<?php

if (method_exists($plxShow, 'artNavigation')) {
    ?>
		            <div id="art-navigation">
<?php $plxShow->artNavigation('<li><a href="#url" rel="#dir" title="#title">#emoji</a></li>'); ?>
		            </div>
<?php
}

?>
<?php $plxShow->artAuthorInfos('<div class="author-infos">#art_authorinfos</div>'); ?>

<?php
$allowedComs = ($plxMotor->plxRecord_arts->f('allow_com') and $plxMotor->aConf['allow_com']);
if ($allowedComs or $plxMotor->plxRecord_coms) {
    include 'commentaires.php';
}
?>
<!-- end of article.php -->

<?php
include 'footer.php';

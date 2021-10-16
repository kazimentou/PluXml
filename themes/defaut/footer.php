<?php
if (!defined('PLX_ROOT')) {
    exit;
}
?>
<!-- begin of footer.php -->
				</div>

<?php
if (!defined('FULL_WIDTH')) {
    include 'sidebar.php';
}
?>

			</div>

		</div>

	</main>

	<footer class="footer">
		<div class="container">
			<p>
				<?php $plxShow->mainTitle('link'); ?> - <span><?php $plxShow->subTitle(); ?></span> <span> - © <?= date('Y', time() - 1296000) ?></span>
			</p>
			<p>
				<?php $plxShow->lang('POWERED_BY') ?>&nbsp;<a href="<?= PLX_URL_REPO?>" title="<?php $plxShow->lang('PLUXML_DESCRIPTION') ?>">PluXml</a>
				<?php $plxShow->lang('IN') ?>&nbsp;<?php $plxShow->chrono(); ?>
				<?php $plxShow->httpEncoding() ?>
				-
				<a rel="nofollow" href="<?php $plxShow->urlRewrite('core/admin/'); ?>" title="<?php $plxShow->lang('ADMINISTRATION') ?>"><?php $plxShow->lang('ADMINISTRATION') ?></a>
			</p>
			<ul class="menu">
<?php
if (!empty($plxMotor->aConf['enable_rss'])) {
    ?>
				<li><a class="rss" href="<?php $plxShow->urlRewrite('feed.php?rss') ?>" title="<?php $plxShow->lang('ARTICLES_RSS_FEEDS'); ?>"><?php $plxShow->lang('ARTICLES'); ?></a></li>
<?php
}

if (!empty($plxMotor->aConf['allow_com']) and !empty($plxMotor->aConf['enable_rss_comment'])) {
    ?>
				<li><a class="rss" href="<?php $plxShow->urlRewrite('feed.php?rss/commentaires'); ?>" title="<?php $plxShow->lang('COMMENTS_RSS_FEEDS') ?>"><?php $plxShow->lang('COMMENTS'); ?></a></li>
<?php
}
?>
				<li><a href="#top" title="<?php $plxShow->lang('GOTO_TOP') ?>" id="go-top"><?php $plxShow->lang('TOP') ?></a></li>
			</ul>
		</div>
	</footer>

<?php
if (defined('THEME_SLIDESHOW') or $plxShow->mode() == 'article') {
    ?>
	<div id="slideshow" data-interval="5000">
		<div class="overlay"></div>
		<figure>
			<img id="slideshow-img" />
			<figcaption>
				<span id="slideshow-counter"></span>
				<span id="slideshow-caption"></span>
				<span id="slideshow-close">❌</span>
			</figcaption>
		</figure>
		<div class="gallery">
			<div>
				<button id="slideshow-prev" class="button">◀</button>
			</div>
			<div id="slideshow-gallery"></div>
			<div>
				<button id="slideshow-next" class="button">▶</button>
			</div>
		</div>
	</div>
<?php
}
?>
	<script src="<?php $plxShow->template(); ?>/js/script.js"></script>

</body>

</html>
<!-- end of footer.php -->

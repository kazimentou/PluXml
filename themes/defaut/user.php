<?php
if (!defined('PLX_ROOT')) {
    exit;
}

include 'header.php';
?>
<!-- begin of user.php -->
					<ul class="repertory menu breadcrumb">
						<li><a href="<?php $plxShow->racine() ?>"><?php $plxShow->lang('HOME'); ?></a></li>
						<li><?php $plxShow->artAuthor(); ?></li>
					</ul>

<?php
include 'posts.php';
?>

					<nav class="pagination text-center">
						<?php $plxShow->pagination(); ?>
					</nav>

					<div>
						<?php $plxShow->artFeed('rss', $plxShow->plxMotor->cible, '<span><a href="#feedUrl" title="#feedTitle">#feedName</a></span>'); ?>
					</div>
<!-- end of user.php -->

<?php
include 'footer.php';

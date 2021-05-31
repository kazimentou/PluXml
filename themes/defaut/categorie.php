<?php include 'header.php'; ?>
<!-- begin of categorie.php -->
					<ul class="repertory menu breadcrumb">
						<li><a href="<?php $plxShow->racine() ?>"><?php $plxShow->lang('HOME'); ?></a></li>
						<li><?php $plxShow->catName(); ?></li>
					</ul>

					<p><?php $plxShow->catDescription('#cat_description'); ?></p>
					<p><?php $plxShow->catThumbnail(); ?></p>

<?php include 'posts.php'; ?>

					<?php $plxShow->artFeed('rss', $plxShow->catId(), '<span><a class="rss" href="#feedUrl" title="#feedTitle">#feedName</a></span>'); ?>

<!-- end of categorie.php -->

<?php
include 'footer.php';

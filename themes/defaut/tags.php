<?php
if (!defined('PLX_ROOT')) {
    exit;
}

include 'header.php';
?>
<!-- begin of tags.php -->
					<ul class="repertory menu breadcrumb">
						<li><a href="<?php $plxShow->racine() ?>"><?php $plxShow->lang('HOME'); ?></a></li>
						<li><?php $plxShow->tagName(); ?></li>
					</ul>
<?php
include 'posts.php';

?>
					<div>
						<?php $plxShow->tagFeed('', '', '<span><a class="rss" href="#feedUrl" title="#feedTitle">#feedName</a></span>'); ?>
					</div>
<!-- end of tags.php -->
<?php

include 'footer.php';

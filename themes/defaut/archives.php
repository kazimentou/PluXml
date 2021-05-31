<?php
if (!defined('PLX_ROOT')) {
    exit;
}

include 'header.php';
?>
<!-- begin of archives.php -->
					<ul class="repertory menu breadcrumb">
						<li><a href="<?php $plxShow->racine() ?>"><?php $plxShow->lang('HOME'); ?></a></li>
						<li><?= plxDate::formatDate($plxShow->plxMotor->cible, $plxShow->getLang('ARCHIVES') . ' #month #num_year(4)') ?></li>
					</ul>
<!-- end of archives.php -->

<?php
include 'posts.php';
include 'footer.php';

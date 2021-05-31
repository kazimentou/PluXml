<?php
if (!defined('PLX_ROOT')) {
    exit;
}

include 'header.php';
?>
<!-- begin of static.php -->
					<article class="article static" id="static-page-<?php echo $plxShow->staticId(); ?>">
						<header>
							<h2><?php $plxShow->staticTitle(); ?></h2>
						</header>
<?php $plxShow->staticContent(); ?>
					</article>
<!-- end of static.php -->

<?php
include 'footer.php';

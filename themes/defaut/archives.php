<?php include 'header.php'; ?>

	<main class="main">

		<div class="container">

			<div class="grid">

				<div class="content col med-9">

					<ul class="repertory menu breadcrumb">
						<li><a href="<?php $plxShow->racine() ?>"><?php $plxShow->lang('HOME'); ?></a></li>
						<li><?php echo plxDate::formatDate($plxShow->plxMotor->cible, $plxShow->lang('ARCHIVES').' #month #num_year(4)') ?></li>
					</ul>

<?php include 'posts.php' ?>

					<?php $plxShow->artFeed('rss', $plxShow->catId(), '<span><a class="rss" href="#feedUrl" title="#feedTitle">#feedName</a></span>'); ?>

				</div>

				<?php include 'sidebar.php'; ?>

			</div>

		</div>

	</main>

<?php
include 'footer.php';

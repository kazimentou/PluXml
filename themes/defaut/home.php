<?php include 'header.php'; ?>

	<main class="main">

		<div class="container">

			<div class="grid">

				<div class="content col med-9">

<?php include 'posts.php'; ?>

					<?php $plxShow->artFeed('rss', $plxShow->catId(), '<span><a class="rss" href="#feedUrl" title="#feedTitle">#feedName</a></span>'); ?>

				</div>

<?php include 'sidebar.php'; ?>

			</div>

		</div>

	</main>

<?php
include 'footer.php';

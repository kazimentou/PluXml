<?php
if (!defined('PLX_ROOT')) {
	exit;
}

if(method_exists($plxShow, 'authorList')) {
	$users = array_filter(
		$plxShow->plxMotor->aUsers,
		function($user) { return !empty($user['active']) and empty($user['delete']); }
	);
	if(count($users) > 1) {
		define('MULTI_USERS', true);
	}
}

$contentClass = 'content col';
if(!defined('FULL_WIDTH')) {
	$contentClass .= ' med-9';
}
?>
<!DOCTYPE html>
<html lang="<?php $plxShow->defaultLang() ?>">
<head>
	<meta charset="<?php $plxShow->charset(); ?>">
	<meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
	<title><?php $plxShow->pageTitle(); ?></title>
<?php
	$plxShow->meta_all();
?>
	<link rel="canonical" href="<?= $plxShow->pageUrl() ?>" />
	<link rel="icon" href="<?php $plxShow->template(); ?>/img/favicon.png" />
	<link rel="stylesheet" href="<?php $plxShow->template(); ?>/css/plucss.min.css?v=<?= PLX_VERSION ?>" media="screen,print"/>
	<link rel="stylesheet" href="<?php $plxShow->template(); ?>/css/theme.min.css?v=<?= PLX_VERSION ?>" media="screen"/>
<?php
	$plxShow->templateCss();
	$plxShow->pluginsCss();
	if($plxShow->allowRSS()) {
		$plxShow->urlPostsRssFeed('', 'link');
	}
?>
</head>
<body id="top" class="page mode-<?php $plxShow->mode(true) ?>">
	<header class="header">
		<div class="container">
			<div class="grid">
				<div class="col sml-6 med-5 lrg-4">
					<div class="logo">
						<h1 class="no-margin heading-small"><?php $plxShow->mainTitle('link'); ?></h1>
						<h2 class="h5 no-margin"><?php $plxShow->subTitle(); ?></h2>
					</div>
				</div>
				<div class="col sml-6 med-7 lrg-8">
					<nav class="nav">
						<div class="responsive-menu">
							<label for="menu"></label>
							<input type="checkbox" id="menu">
							<ul class="menu">
								<?php $plxShow->staticList($plxShow->getLang('HOME')); ?>
								<?php $plxShow->pageBlog('<li class="#page_class #page_status" id="#page_id"><a href="#page_url" title="#page_name">#page_name</a></li>'); ?>
							</ul>
						</div>
					</nav>
				</div>
			</div>
		</div>
	</header>
	<div class="bg"></div>

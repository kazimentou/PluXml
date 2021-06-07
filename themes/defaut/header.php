<?php
if (!defined('PLX_ROOT')) {
    exit;
}

if (version_compare(PLX_VERSION, '6.0.0', '<')) {
    // Pas d'auto-loader pour les classes PluXml
    include_once PLX_CORE . 'lib/class.plx.token.php';
    if ($plxShow->plxMotor->aConf['capcha']) {
        include_once PLX_CORE . 'lib/class.plx.capcha.php';
    }
}

function printCapcha()
{
    global $plxShow;

    if ($plxShow->plxMotor->aConf['capcha']) {
        // Hack against PluXml: $plxShow->plxMotor->plxCapcha est instancié uniquement en mode article.
        if (!class_exists('plxCapcha')) {
            include_once PLX_CORE . 'lib/class.plx.capcha.php';
        }

        if (empty($plxShow->plxMotor->plxCapcha)) {
            $plxShow->plxMotor->plxCapcha = new plxCapcha();
        } ?>
		<div>
			<label for="id_rep"><strong><?php echo $plxShow->lang('ANTISPAM_WARNING') ?></strong> :</label>
			<div class="capcha-challenge">
				<p>
					<?php $plxShow->capchaQ(); ?>
				</p>
				<input type="text" name="rep" id="id_rep" maxlength="1" class="antispam" autocomplete="off" required />
			</div>
		</div>
<?php
    }
}

?>
<!DOCTYPE html>
<!-- begin of header.php -->
<html lang="<?php $plxShow->defaultLang() ?>">
<head>
	<meta charset="<?php $plxShow->charset('min'); ?>">
	<meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
<?php
    $plxShow->meta();
?>
	<title><?php $plxShow->pageTitle(); ?></title>
	<link rel="icon" href="<?php $plxShow->template(); ?>/img/favicon.png" />
	<link rel="stylesheet" href="<?php $plxShow->template(); ?>/css/plucss.css?v=1.3.1" media="screen,print"/>
	<link rel="stylesheet" href="<?php $plxShow->template(); ?>/css/theme.css?v=<?php echo PLX_VERSION ?>" />
<?php
    $plxShow->templateCss();
    $plxShow->pluginsCss();
?>
	<link rel="alternate" type="application/rss+xml" title="<?php $plxShow->lang('ARTICLES_RSS_FEEDS') ?>" href="<?php $plxShow->urlPostsRssFeed($plxShow->plxMotor->mode) ?>" />
	<link rel="alternate" type="application/rss+xml" title="<?php $plxShow->lang('COMMENTS_RSS_FEEDS') ?>" href="<?php $plxShow->urlRewrite('feed.php?rss/commentaires') ?>" />
<?php
if (method_exists($plxShow, 'canonical')) {
    $plxShow->canonical();
}
?>
</head>

<body id="top" class="page mode-<?php $plxShow->mode(true) ?>">

	<header class="header">

		<div class="container">

			<div>

				<div class="brand">

					<div>
						<h1 class="no-margin heading-small"><?php $plxShow->mainTitle('link'); ?></h1>
						<h2 class="h5 no-margin"><?php $plxShow->subTitle(); ?></h2>
					</div>

				</div>

				<nav class="nav">

					<div class="responsive-menu">
						<label for="menu"></label>
						<input type="checkbox" id="menu">
						<ul class="menu">
							<?php $plxShow->staticList($plxShow->getLang('HOME'), '<li class="#static_class #static_status" id="#static_id"><a href="#static_url" title="#static_name">#static_name</a></li>'); ?>
							<?php $plxShow->pageBlog('<li class="#page_class #page_status" id="#page_id"><a href="#page_url" title="#page_name">#page_name</a></li>'); ?>
						</ul>
					</div>

				</nav>

			</div>

		</div>

	</header>

	<div class="bg"></div>

	<main class="main">

		<div class="container">

			<div class="grid">

				<div class="content col <?= defined('FULL_WIDTH') ? '' : 'med-9' ?>">
<!-- end of header.php -->

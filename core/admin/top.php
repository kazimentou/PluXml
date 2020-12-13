<?php

if (!defined('PLX_ROOT')) {
    exit;
}

if (isset($_GET["del"]) and $_GET["del"] == "install") {
    if (@unlink(PLX_SCRIPT_INSTALL))
        plxMsg::Info(L_DELETE_SUCCESSFUL);
    else
        plxMsg::Error(L_DELETE_FILE_ERR . ' ' . basename(PLX_SCRIPT_INSTALL));
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $plxAdmin->aConf['default_lang'] ?>">
<head>
	<meta charset="<?= strtolower(PLX_CHARSET) ?>">
    <meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
    <title><?= plxUtils::strCheck($plxAdmin->aConf['title']) ?> <?= L_ADMIN ?></title>
    <link rel="stylesheet" href="theme/css/theme.css?v=<?= PLX_VERSION ?>" media="screen"/>
<?php
plxUtils::printLinkCss($plxAdmin->aConf['custom_admincss_file'], true);
plxUtils::printLinkCss($plxAdmin->aConf['racine_plugins'] . 'admin.css', true);

# Plugin hook
eval($plxAdmin->plxPlugins->callHook('AdminTopEndHead'));

# pour tablettes
$currentScript = basename($_SERVER['SCRIPT_NAME'], ".php");
$fullwide = in_array($currentScript, array('article', 'statique'/* , 'medias' */, 'parametres_users', 'parametres_themes', 'parametres_edittpl')) ? ' fullwide' : '';
$hideMenu = in_array($currentScript, array(
	'article', 'categorie', 'comment', 'profil', 'user', 'parametres_base',
	'parametres_affichage', 'parametres_themes', 'parametres_avances', 'statique')
) ? ' hide-menu' : '';

if($currentScript == 'medias') {
	# on active le medias manager
	$extras = array(
		'data-root="' . plxUtils::getRacine() . '"',
		'data-errormsg="' . 'Popup interdit' . '"',
	);
}
if($_SESSION ['profil'] > PROFIL_WRITER) {
	$_SESSION ['profil'] = PROFIL_WRITER;
}
?>
    <link rel="icon" href="theme/images/favicon.png" />
    <meta name="robots" content="noindex, nofollow" />
</head>
<body id="<?= $currentScript ?>" class="profil-<?= $_SESSION['profil'] ?><?= $fullwide ?>" <?= !empty($extras) ? implode(' ', $extras) : '' ?>>
	<header id="main-header">
		<div class="banner">
			<ul class="unstyled">
				<!-- li class="badge"><a href="user.php"><img src="theme/images/pluxml.png"/></a></li -->
				<li class="user">
					<a href="user.php"><?= PlxUtils::strCheck($plxAdmin->aUsers[$_SESSION['user']]['name']) ?></a>
					<small><em><?= PROFIL_NAMES[$_SESSION ['profil']] ?></em></small>
				</li>
				<li><a href="auth.php?d=1" title="<?= L_ADMIN_LOGOUT_TITLE ?>"><i class="icon-logout"></i></a></li>
			</ul>
		</div>
		<nav>
<?php
$menus = array();
$userId = ($_SESSION['profil'] < PROFIL_WRITER) ? '\d{3}' : $_SESSION['user'];
$nbartsmod = $plxAdmin->nbArticles('all', $userId, '_');
$arts_mod = $nbartsmod > 0 ? '<span class="badge" onclick="window.location=\'' . 'index.php?sel=mod&amp;page=1\';return false;">' . $nbartsmod . '</span>' : '';
$menus[] = plxUtils::formatMenu('<i class="icon-doc-inv"></i>' . L_MENU_ARTICLES, 'index.php?page=1', L_MENU_ARTICLES_TITLE, false, false, $arts_mod);

if (isset($_GET['a'])) # edition article
	$menus[] = plxUtils::formatMenu('<i class="icon-plus"></i>' . L_NEW_ARTICLE, 'article.php', L_NEW_ARTICLE, false, false, '', false);
else # nouvel article
	$menus[] = plxUtils::formatMenu('<i class="icon-plus"></i>' . L_NEW_ARTICLE, 'article.php', L_NEW_ARTICLE);

$menus[] = plxUtils::formatMenu('<i class="icon-picture"></i>' . L_MENU_MEDIAS, 'medias.php', L_MENU_MEDIAS_TITLE);

if ($_SESSION['profil'] <= PROFIL_MANAGER)
	# Peut gérer les pages statiques ( script PHP )
	$menus[] = plxUtils::formatMenu('<i class="icon-doc-text-inv"></i>' . L_MENU_STATICS, 'statiques.php', L_MENU_STATICS_TITLE);

if (!empty($plxAdmin->aConf['allow_com']) and $_SESSION['profil'] <= PROFIL_MODERATOR) {
	# Peut gérer les commentaires
	$nbcoms = $plxAdmin->nbComments('offline');
	$coms_offline = $nbcoms > 0 ? '<span class="badge" onclick="window.location=\'' . 'comments.php?sel=offline&amp;page=1\';return false;">' . $plxAdmin->nbComments('offline') . '</span>' : '';
	$menus[] = plxUtils::formatMenu('<i class="icon-comment-inv-alt2"></i>' . L_COMMENTS, 'comments.php?page=1', L_MENU_COMMENTS_TITLE, false, false, $coms_offline);
}

if ($_SESSION['profil'] <= PROFIL_EDITOR)
	# Peut gérer les catégories et son profil
	$menus[] = plxUtils::formatMenu('<i class="icon-list"></i>' . L_CATEGORIES, 'categories.php', L_MENU_CATEGORIES_TITLE);

$menus[] = plxUtils::formatMenu('<i class="icon-user"></i>' . L_PROFIL, 'user.php', L_MENU_PROFIL_TITLE);

if ($_SESSION['profil'] == PROFIL_ADMIN) {
	# Cet utilisateur a les super-pouvoirs
	$menus[] = plxUtils::formatMenu('<i class="icon-cog-1"></i>' . L_MENU_CONFIG, 'parametres_base.php', L_MENU_CONFIG_TITLE, false, false, '', false);
	$isSetup = preg_match('@^parametres_@', basename($_SERVER['SCRIPT_NAME']));
	if ($isSetup) {
		$menus[] = plxUtils::formatMenu(L_CONFIG_BASE, 'parametres_base.php', L_MENU_CONFIG_BASE_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_MENU_CONFIG_VIEW, 'parametres_affichage.php', L_MENU_CONFIG_VIEW_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_MENU_CONFIG_USERS, 'parametres_users.php', L_MENU_CONFIG_USERS_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_CONFIG_ADVANCED, 'parametres_avances.php', L_MENU_CONFIG_ADVANCED_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_THEMES, 'parametres_themes.php', L_THEMES_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_MENU_CONFIG_PLUGINS, 'parametres_plugins.php', L_MENU_CONFIG_PLUGINS_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_INFOS, 'parametres_infos.php', L_MENU_CONFIG_INFOS_TITLE, 'menu-config');
	}
}

// Get administration menu links from Plugins
foreach ($plxAdmin->plxPlugins->aPlugins as $plugName => $plugInstance) {
	if ($plugInstance and is_file(PLX_PLUGINS . $plugName . '/admin.php')) {
		if ($plxAdmin->checkProfil($plugInstance->getAdminProfil(), false)) {
			if ($plugInstance->adminMenu) {
				$menu = plxUtils::formatMenu(plxUtils::strCheck($plugInstance->adminMenu['title']), 'plugin.php?p=' . $plugName, plxUtils::strCheck($plugInstance->adminMenu['caption']));
				if ($plugInstance->adminMenu['position'] != '')
					array_splice($menus, ($plugInstance->adminMenu['position'] - 1), 0, $menu);
				else
					$menus[] = $menu;
			} else {
				$menus[] = plxUtils::formatMenu(plxUtils::strCheck($plugInstance->getInfo('title')), 'plugin.php?p=' . $plugName, plxUtils::strCheck($plugInstance->getInfo('title')));
			}
		}
	}
}

# Plugin hook
eval($plxAdmin->plxPlugins->callHook('AdminTopMenus'));
?>
        <nav class="responsive-menu">
			<h1 class="h4-like txtcenter"><?= PlxUtils::strCheck($plxAdmin->aConf['title']) ?></h1>
			<button class="nav-button" type="button" role="button" aria-label="open/close navigation"><i></i></button>
            <ul>
<?php if (isset($plxAdmin->aConf['homestatic']) and !empty($plxAdmin->aConf['homestatic'])) : ?>
				<li><a class="back-blog" href="<?= $plxAdmin->urlRewrite('?blog'); ?>" title="<?= L_BACK_TO_BLOG_TITLE ?>"><i class="icon-left-open"></i><?= L_BACK_TO_BLOG; ?></a></li>
<?php endif; ?>
                <li><?= plxUtils::formatMenu('<i class="icon-home-1"></i>' . L_HOMEPAGE, PLX_ROOT, L_BACK_HOMEPAGE_TITLE) ?></li>
<?php
/*
					<a class="back-site" href="<?= PLX_ROOT ?>" title="<?= L_BACK_HOMEPAGE_TITLE ?>"><i class="icon-left-open"></i><?= L_HOMEPAGE; ?></a></li>
 * */
?>
<li><?= implode('</li>' . PHP_EOL . '<li>', $menus) ?></li>
<li class="plxversion"><a title="PluXml" href="<?= PLX_URL_REPO ?>" target="_blank">PluXml <?= $plxAdmin->aConf['version'] ?></a></li>
            </ul>
        </nav>
	</header>
	<section id="main-section" class="main section<?= !empty($isSetup) ? ' setup' : '' ?><?= $hideMenu ?>">
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminTopBottom'));
?>
<!------ End of top.php ----->

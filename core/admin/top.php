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
$fullwide = in_array($currentScript, array('article', 'statique'/* , 'medias' */, 'parametres_themes', 'parametres_edittpl', 'parametres_plugin', 'parametres_plugincss')) ? ' fullwide' : '';
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

$logo = (!empty($plxAdmin->aConf['thumbnail']) and file_exists(PLX_ROOT . $plxAdmin->aConf['thumbnail'])) ? PLX_ROOT . $plxAdmin->aConf['thumbnail'] : 'theme/images/pluxml.png';
$logoSizes = getimagesize($logo);
?>
    <link rel="icon" href="theme/images/favicon.png" />
    <meta name="robots" content="noindex, nofollow" />
</head>
<body id="<?= $currentScript ?>" class="profil-<?= $_SESSION['profil'] ?><?= $fullwide ?>" <?= !empty($extras) ? implode(' ', $extras) : '' ?>>
	<header id="main-header">
		<div class="banner">
			<div class="brand">
				<div>
					<a href="<?= PLX_ROOT ?>" title="<?= L_BACK_HOMEPAGE_TITLE ?>" class="logo"><img src="<?= $logo ?>" <?= !empty($logoSizes) ? $logoSizes[3] : '' ?> /></a>
				</div>
				<div>
					<h1 class="h4-like"><?= PlxUtils::strCheck($plxAdmin->aConf['title']) ?></h1>
					<div><?= PlxUtils::strCheck($plxAdmin->aConf['description']) ?></div>
				</div>
			</div>
			<div class="user">
				<a href="user.php" title="<?= L_MENU_PROFIL_TITLE ?>"><?= PlxUtils::strCheck($plxAdmin->aUsers[$_SESSION['user']]['name']) ?></a>
				<small><em><?= PROFIL_NAMES[$_SESSION ['profil']] ?></em></small>
				<small title="<?= L_LASTLOGIN_TIMESTAMP ?>"><em><?= date('Y-m-d H:i', $_SESSION['auth_time']) ?></em></small>
			</div>
			<div>
				<a href="auth.php?d=1" title="<?= L_ADMIN_LOGOUT_TITLE ?>" class="icon-logout"></i></a>
			</div>
		</div>
<?php
$userId = ($_SESSION['profil'] < PROFIL_WRITER) ? '\d{3}' : $_SESSION['user'];
$nbartsmod = $plxAdmin->nbArticles('all', $userId, '_');
$arts_mod = ($nbartsmod > 0) ? '<a class="badge" href="index.php?sel=mod&page=1">' . $nbartsmod . '</a>' : '';

# Entrées de base dans le menu
$menus = array(
    plxUtils::formatMenu(L_HOMEPAGE, PLX_ROOT, L_BACK_HOMEPAGE_TITLE, 'icon-home-1'),
	plxUtils::formatMenu(L_MENU_ARTICLES, 'index.php?sel=all&page=1', L_MENU_ARTICLES_TITLE, 'icon-doc-inv') . $arts_mod,
	plxUtils::formatMenu(L_NEW_ARTICLE, 'article.php', L_NEW_ARTICLE, 'icon-plus', false, '', !isset($_GET['a'])), # Pas surligné si édition article
	plxUtils::formatMenu(L_MENU_MEDIAS, 'medias.php', L_MENU_MEDIAS_TITLE, 'icon-picture'),
	plxUtils::formatMenu(L_PROFIL, 'user.php', L_MENU_PROFIL_TITLE, 'icon-user'),
);

if ($_SESSION['profil'] <= PROFIL_MANAGER) {  # PROFIL_MANAGER == 1
	# Peut gérer les pages statiques ( script PHP )
	$menus[] = plxUtils::formatMenu(L_MENU_STATICS, 'statiques.php', L_MENU_STATICS_TITLE, 'icon-doc-text-inv');
}

if ($_SESSION['profil'] <= PROFIL_MODERATOR and !empty($plxAdmin->aConf['allow_com'])) { # PROFIL_MODERATOR == 2
	# Peut gérer les commentaires
	$nbcoms = $plxAdmin->nbComments('offline');
	$entry = plxUtils::formatMenu(L_COMMENTS, 'comments.php?sel=all&page=1', L_MENU_COMMENTS_TITLE, 'icon-comment-inv-alt2');
	if($nbcoms > 0) {
		$entry .= '<a class="badge" href="comments.php?sel=offline&page=1">' . $nbcoms . '</a>';
	}
	array_splice($menus, 3, 0, $entry);
}

if ($_SESSION['profil'] <= PROFIL_EDITOR) { # PROFIL_EDITOR == 3
	# Peut gérer les catégories et son profil
	$menus[] = plxUtils::formatMenu(L_CATEGORIES, 'categories.php', L_MENU_CATEGORIES_TITLE, 'icon-list');
}

if ($_SESSION['profil'] == PROFIL_ADMIN) { # PROFIL_ADMIN == 0
	# Cet utilisateur a les super-pouvoirs
	$isSetup = (
		preg_match('@^parametres_@', basename($_SERVER['SCRIPT_NAME'])) or
		(basename($_SERVER['SCRIPT_NAME'], '.php') == 'user' and !empty($_GET['p']) and $_GET['p'] != $_SESSION['user'])
	);
	if ($isSetup) {
		$menus[] = plxUtils::formatMenu(L_CONFIG_BASE, 'parametres_base.php', L_MENU_CONFIG_BASE_TITLE, 'icon-cog-1');
		$menus[] = plxUtils::formatMenu(L_MENU_CONFIG_VIEW, 'parametres_affichage.php', L_MENU_CONFIG_VIEW_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_THEMES, 'parametres_themes.php', L_THEMES_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_CONFIG_ADVANCED, 'parametres_avances.php', L_MENU_CONFIG_ADVANCED_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_MENU_CONFIG_USERS, 'parametres_users.php', L_MENU_CONFIG_USERS_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_INFOS, 'parametres_infos.php', L_MENU_CONFIG_INFOS_TITLE, 'menu-config');
		$menus[] = plxUtils::formatMenu(L_MENU_CONFIG_PLUGINS, 'parametres_plugins.php', L_MENU_CONFIG_PLUGINS_TITLE, 'menu-config');
	} else {
		$menus[] = plxUtils::formatMenu(L_MENU_CONFIG, 'parametres_base.php', L_MENU_CONFIG_TITLE, 'icon-cog-1', false, '', false);
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
			<button class="nav-button" type="button" role="button" aria-label="open/close navigation"><i></i></button>
			<div>
	            <ul>
<?php
if (isset($plxAdmin->aConf['homestatic']) and !empty($plxAdmin->aConf['homestatic'])) {
	$artsHomepage = $plxAdmin->plxGlob_arts->query('#^\d{4}\.(\d{3},)*home(,\d{3})*\.\d{3}\.\d{12}\.[\w-]+\.xml$#');
	if(!empty($artsHomepage)) {
?>
					<li><?= plxUtils::formatMenu(L_BLOG, $plxAdmin->urlRewrite('index.php?blog'), L_BACK_TO_BLOG_TITLE, 'icon-left-open') ?></li>
<?php
	}
}

?>
<li><?= implode('</li>' . PHP_EOL . '<li>', $menus) ?></li>
	            </ul>
				<div class="plxversion">
					<a title="PluXml" href="<?= PLX_URL_REPO ?>" target="_blank">PluXml <?= $plxAdmin->aConf['version'] ?></a>
				</div>
			</div>
        </nav>
	</header>
	<section id="main-section" class="main section<?= !empty($isSetup) ? ' setup' : '' ?><?= $hideMenu ?>">
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminTopBottom'));
?>
<!------ End of top.php ----->

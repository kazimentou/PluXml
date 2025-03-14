<?php
const PLX_ROOT = '../';
const PLX_CORE = PLX_ROOT . 'core/';

# These old versions of PluXml do not version in 'data/configuration/parametres.xml'
const PLX_OLD_VERSIONS = array(
	'4.2',
	'4.3',
	'4.3.1',
	'4.3.2',
);

include PLX_CORE.'lib/config.php';

# On vérifie la version minimale de PHP
if(version_compare(PHP_VERSION, PHP_VERSION_MIN, '<')){
	header('Content-Type: text/plain; charset=UTF-8');
	echo L_WRONG_PHP_VERSION;
	exit;
}

# On verifie que PluXml est installé
if(!file_exists(path('XMLFILE_PARAMETERS'))) {
	header('Location: '.PLX_ROOT.'install.php');
	exit;
}

const PLX_UPDATER = true;

# On inclut les librairies nécessaires pour la MAJ
include 'class.plx.updater.php';

# Création de l'objet principal et lancement du traitement
$plxUpdater = new plxUpdater();

plxUtils::cleanHeaders();
plx_session_start();

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Echappement des caractères
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$_POST = plxUtils::unSlash($_POST);
}

$all_langs = plxUtils::getLangs();

# Chargement des langues
$lang = (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : PLX_SITE_LANG;
if(!empty($_POST['default_lang']) and preg_match('#^([a-z]{2})$#', $_POST['default_lang'], $matches)) {
	$lang = $matches[1];
}
if(!array_key_exists($lang, $all_langs)) {
	$lang = PLX_SITE_LANG;
}
define('USER_LANG', $lang);

$root_langs = PLX_CORE . 'lang/'. USER_LANG . '/';
foreach(array('core.php', 'admin.php', 'update.php') as $k) {
	loadLang($root_langs . $k);
}
?>
<!DOCTYPE html>
<head>
	<meta name="robots" content="noindex, nofollow" />
	<meta charset="<?= strtolower(PLX_CHARSET) ?>" />
	<meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
	<title><?= L_UPDATE_TITLE . ' ' . PLX_VERSION ?></title>
<?php plxUtils::printLinkCss(); ?>
	<style>
		p.alert { width: fit-content; }
	</style>
</head>
<body>
	<main class="main grid">
		<aside class="aside col sml-12 med-3 lrg-2">
		</aside>
		<section class="section col sml-12 med-9 med-offset-3 lrg-10 lrg-offset-2" style="margin-top: 0">
			<header>
				<h1><?= L_UPDATE_TITLE . ' ' . PLX_VERSION ?> <i>( data: <?= PLX_VERSION_DATA ?> )</i></h1>
			</header>
<?php
if(empty($_POST['submit'])) {
	if(!empty($plxUpdater->oldVersion) and empty($plxUpdater->updatedVersions)) {
?>
				<p><strong><?= L_UPDATE_UPTODATE ?></strong></p>
				<p><?= L_UPDATE_NOT_AVAILABLE ?></p>
				<p><a class="button" href="<?= PLX_ROOT; ?>" title="<?= L_UPDATE_BACK ?>"><?= L_UPDATE_BACK ?></a></p>
<?php
	} else {
?>
				<form method="post">
					<?= plxToken::getTokenPostMethod() ?>
					<fieldset>
						<div class="grid">
							<div class="col sml-9 med-7 label-centered">
								<label for="id_default_lang"><?= L_SELECT_LANG ?></label>
							</div>
							<div class="col sml-3 med-2">
								<?php plxUtils::printSelect('default_lang', $all_langs, $lang) ?>&nbsp;
							</div>
							<div class="col med-3">
								<input type="submit" name="select_lang" value="<?= L_INPUT_CHANGE ?>" />
							</div>
						</div>
					</fieldset>
					<fieldset>
						<p><strong><?= L_UPDATE_WARNING1.' '.$plxUpdater->oldVersion ?></strong></p>
<?php
		if(empty($plxUpdater->oldVersion)) {
?>
						<p>
							<span><?= L_UPDATE_SELECT_VERSION ?></span>
							<select name="version" required>
								<option></option>
<?php
			foreach(PLX_OLD_VERSIONS as $version) {
?>
								<option value="<?= $version ?>"><?= $version ?></option>
<?php
			}
?>
							</select>
						</p>
						<p><? L_UPDATE_WARNING2 ?></p>
<?php
		} else {
?>
						<input type="hidden" name="version" value="<?= $plxUpdater->oldVersion ?>">
<?php
		}
?>
						<p><?php printf(L_UPDATE_WARNING3, preg_replace('@^([^/]+).*@', '$1', $plxUpdater->plxMotor->aConf['racine_articles'])); ?></p>
						<p><input type="submit" name="submit" value="<?= L_UPDATE_START ?>" /></p>
					</fieldset>
				</form>
<?php
	}
} elseif(!empty($_POST['version']) and $plxUpdater->startUpdate($_POST['version'])) {
?>
			<p><a class="button" href="<?= PLX_ROOT; ?>" title="<?= L_UPDATE_BACK ?>"><?= L_UPDATE_BACK ?></a></p>
<?php
}
?>
		</section>
	</main>
</body>
</html>

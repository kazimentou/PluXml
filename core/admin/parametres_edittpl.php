<?php
/**
 * Edition des fichiers templates du thème en vigueur
 * @package PLX
 * @author	Stephane F
 **/

include 'prepend.php';

# Controle du token du formulaire
plxToken::validateFormToken($_POST);

# Controle de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

# On teste l'existence du thème
$style = $plxAdmin->aConf['style'];
$stylePath = PLX_ROOT.$plxAdmin->aConf['racine_themes'] . $style . DIRECTORY_SEPARATOR;
if(empty($style) OR !is_dir($stylePath)) {
	plxMsg::Error(L_CONFIG_EDITTPL_ERROR_NOTHEME);
	header('Location: parametres_affichage.php');
	exit;
}

# Initialisation
if(!empty($_POST['load'])) {
	$tpl = $_POST['template'];
} else {
	if(empty($_POST['tpl'])) {
		$tpl = isset($_POST['tpl']);
		$filename = realpath($stylePath . $tpl);
		if(isset($_POST['content']) and is_writable($filename)) {
			# Traitement du formulaire: sauvegarde du template
			if(file_put_contents($filename, trim($_POST['content']))) {
				plxMsg::Info(L_SAVE_FILE_SUCCESSFULLY);
			} else {
				plxMsg::Error(L_SAVE_FILE_ERROR);
			}
		}
	}
}

if(empty($tpl) or !is_writable($stylePath . $tpl)) {
	$tpl = 'home.php';
}

# On récupère les fichiers templates du thème
$offset = strlen($stylePath);
$aTemplates = array_map(
	function($filename) use($offset) {
		return substr($filename, $offset);
	},
	array_filter(
		glob($stylePath . '*.php',  GLOB_MARK),
		function($filename) {
			return (preg_match('@\.(?:php|css|js|xml|html?|txt|)@', $filename) or substr($filename, -1) == DIRECTORY_SEPARATOR);
		}
	)
);

$filename = realpath($stylePath . $tpl);

# On inclut le header
include 'top.php';
?>
<form method="post" id="form_edittpl">
	<?= plxToken::getTokenPostMethod() ?>
	<input type="hidden" name="tpl" value="<?= $tpl ?>" />
	<div class="adminheader">
		<div>
			<h2><?= L_CONFIG_EDITTPL_TITLE ?> &laquo;<?= plxUtils::strCheck($style) ?>&raquo;</h2>
			<p><?php printf(L_CONFIG_VIEW_PLUXML_RESSOURCES, PLX_RESSOURCES_LINK); ?></p>
			<div>
<?php plxUtils::printSelectDir('template', $tpl, $stylePath, 'no-margin', false) ?>
				<input name="load" type="submit" value="<?= L_CONFIG_EDITTPL_LOAD ?>" />
			</div>
		</div>
		<div>
			<div>
				<a href="index.php" class="icon-logout"></a>
			</div>
			<div>
				<input name="submit" type="submit" value="<?= L_SAVE ?>" />
			</div>
		</div>
	</div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsEdittplTop'));
?>
	<div>
		<label for="id_content"><?= L_CONTENT_FIELD ?></label>
		<textarea name="content" rows="20"><?= plxUtils::strCheck(file_get_contents($filename)) ?></textarea>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsEdittpl'))
?>
	</div>
</form>
<?php

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsEdittplFoot'));

# On inclut le footer
include 'foot.php';

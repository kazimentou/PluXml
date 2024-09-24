<?php
/**
 * Edition des fichiers templates du thème en vigueur
 * @package PLX
 * @author	Stephane F
 **/

include 'prepend.php';

const TEMPLATE_EXTS = array('.php', '.css', '.htm', '.html', '.txt', '.js', '.xml',);

# Controle du token du formulaire
plxToken::validateFormToken($_POST);

# Controle de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

# On récupère les fichiers templates du thèmes
function listFolderFiles($dir, $root='') {
	$content = array();
	$ffs = scandir($dir);
	foreach($ffs as $ff){
		# On ignore les fichiers/dossiers cachés et les "dossiers" . et ..
		if($ff[0] == '.') {
			continue;
		}

		$filename = $dir . '/' . $ff;
		if(!is_dir($filename)) {
			$ext = strtolower(strrchr($ff, '.'));
			if(in_array($ext, TEMPLATE_EXTS)) {
				$f = str_replace($root, '', PLX_ROOT.ltrim($filename, './'));
				$content[$f] = $f;
			}
		} else {
			# appel récursif de la fonction
			$content = array_merge($content, listFolderFiles($filename, $root));
		}
	}
	return $content;
}

$style = $plxAdmin->aConf['style'];
$root = PLX_ROOT . $plxAdmin->aConf['racine_themes'] . $style;
$aTemplates = listFolderFiles($root, $root);

# Initialisation
if(isset($_POST['load'])) {
	$tpl = $_POST['template'];
} elseif(isset($_POST['save'])) {
	$tpl = $_POST['tpl'];
} else {
	$tpl = 'home.php';
}

if(!array_key_exists('/' . $tpl, $aTemplates)) {
	plxMsg::Error(L_CONFIG_EDITTPL_ERROR_NOTHEME);
	header('Location: parametres_affichage.php');
	exit;
}

$filename = realpath($root . '/' . $tpl);

# Traitement du formulaire: sauvegarde du template
if(isset($_POST['save']) AND !empty(trim($_POST['content']))) {
	if(plxUtils::write($_POST['content'], $filename))
		plxMsg::Info(L_SAVE_FILE_SUCCESSFULLY);
	else
		plxMsg::Error(L_SAVE_FILE_ERROR);
}

# On récupère le contenu du fichier template
$content = file_get_contents($filename);

# On inclut le header
include 'top.php';
?>
<form method="post" id="form_edittpl">

	<div class="inline-form action-bar">
		<h2><?= L_CONFIG_EDITTPL_TITLE ?> &laquo;<?= plxUtils::strCheck($style) ?>&raquo;</h2>
		<p><?php printf(L_CONFIG_VIEW_PLUXML_RESSOURCES, PLX_RESSOURCES_THEMES_LINK); ?></p>
		<?= plxToken::getTokenPostMethod() ?>
		<?php plxUtils::printSelectDir('template', $tpl, $root, 'no-margin', false) ?>
		<input name="load" type="submit" value="<?= L_CONFIG_EDITTPL_LOAD ?>" />
		<span class="sml-hide med-show">&nbsp;&nbsp;&nbsp;</span>
		<input name="save" type="submit" value="<?= L_SAVE_FILE ?>" />
	</div>

	<?php eval($plxAdmin->plxPlugins->callHook('AdminSettingsEdittplTop')); # Hook Plugins ?>

	<div class="grid">
		<div class="col sml-12">
			<?php plxUtils::printInput('tpl', plxUtils::strCheck($tpl), 'hidden'); ?>
			<label for="id_content"><?= L_CONTENT_FIELD ?>&nbsp;:</label>
			<?php plxUtils::printArea('content', plxUtils::strCheck($content), 0, 20, false, 'full-width', 'placeholder=" "'); ?>

			<?php eval($plxAdmin->plxPlugins->callHook('AdminSettingsEdittpl')); # Hook Plugins ?>

		</div>
	</div>

</form>
<?php

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsEdittplFoot'));

# On inclut le footer
include 'foot.php';

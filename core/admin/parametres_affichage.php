<?php

/**
 * Edition des paramètres d'affichage
 *
 * @package PLX
 * @author    Florent MONTHEL, Stephane F
 **/

include 'prepend.php';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

# On édite la configuration
if (isset($_POST['display'])) {
    $_POST['feed_footer'] = $_POST['content'];
    $_POST['images_l'] = plxUtils::getValue($_POST['images_l'], 800);
    $_POST['images_h'] = plxUtils::getValue($_POST['images_h'], 600);
    $_POST['miniatures_l'] = plxUtils::getValue($_POST['miniatures_l'], 200);
    $_POST['miniatures_h'] = plxUtils::getValue($_POST['miniatures_h'], 100);
    unset($_POST['content']);

    $plxAdmin->editConfiguration($_POST);
    header('Location: parametres_affichage.php');
    exit;
}

# On récupère les templates de la page d'accueil
$aTemplates = $plxAdmin->getTemplatesCurrentTheme('home', L_NONE1);

# On va tester les variables pour les images et miniatures
foreach(array(
	'images_l'		=> 800,
	'images_h'		=> 600,
	'miniatures_l'	=> 200,
	'miniatures_h'	=> 100,
) as $k=>$default) {
	if (!is_numeric($plxAdmin->aConf[$k])) {
		$plxAdmin->aConf[$k] = $default;
	}
}

# On inclut le header
include 'top.php';
?>

<form method="post" id="form_display" class="first-level">
    <?= plxToken::getTokenPostMethod() ?>

    <div class="adminheader">
        <div>
            <h2 class="h3-like"><?= L_CONFIG_VIEW ?></h2>
        </div>
        <div>
            <input class="btn--primary" type="submit" name="config-display" role="button" value="<?= L_SAVE ?>"/>
        </div>
    </div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsDisplayTop'))
?>
    <fieldset class="caption-inside">
<?php
foreach(array(
	'hometemplate'		=> array(L_CONFIG_HOMETEMPLATE, $aTemplates),
	'tri'				=> array(L_ARTICLES_SORT, TRI_ARTS),
	'bypage'			=> array(L_CONFIG_VIEW_BYPAGE),
	'bypage_tags'		=> array(L_CONFIG_VIEW_BYPAGE_TAGS),
	'bypage_archives'	=> array(L_CONFIG_VIEW_BYPAGE_ARCHIVES),
	'tri_coms'			=> array(L_CONFIG_VIEW_SORT_COMS, TRI_COMS),
	'bypage_admin_coms'	=> array(L_CONFIG_VIEW_BYPAGE_ADMIN_COMS),
	'display_empty_cat'	=> L_CONFIG_VIEW_DISPLAY_EMPTY_CAT,
	'images_l'			=> array(L_CONFIG_VIEW_IMAGES, 'images_h'),
	'miniatures_l'		=> array(L_CONFIG_VIEW_THUMBS, 'miniatures_h'),
	'thumbs'			=> L_MEDIAS_THUMBS,
	# call plxUtils::printThumbnail($plxAdmin->aConf()
	'bypage_feed'		=> array(L_CONFIG_VIEW_BYPAGE_FEEDS),
	'feed_chapo'		=> L_CONFIG_VIEW_FEEDS_HEADLINE,
	'feed_footer'		=> array(L_CONFIG_VIEW_FEEDS_FOOTER, true), # textarea
) as $k=>$infos) {
	if(is_string($infos)) {
		# input[type="checkbox"]
?>
		<label>
			<span><?= $infos ?></span>
			<input type="checkbox" name="<?= $k ?>" value="1" <?= !empty($plxAdmin->aConf[$k]) ? 'checked' : '' ?> />
		</label>
<?php
	} else {
		# $infos is an array
		if(isset($infos[1])) {
			if(is_array($infos[1])) {
				if(count($infos[1]) <= 1) {
					# No choice in <select> tag
					continue;
				} else {
					# <select> tag
?>
		<label>
			<span><?= $infos[0] ?></span>
<?php plxUtils::printSelect($k, $infos[1], $plxAdmin->aConf[$k]); ?>
		</label>
<?php
				}
			} elseif($infos[1] === true) {
				# <textarea>
?>
		<div>
			<label for="id_<?= $k ?>"><?= $infos[0] ?></label>
			<textarea name="<?= $k ?>" rows="5" id="id_<?= $k ?>"><?= $plxAdmin->aConf[$k] ?></textarea>
		</div>
<?php
			} elseif(is_string($infos[1])) {
				# input[type="number"] by 2
?>
		<label>
			<span><?= $infos[0] ?></span>
			<input type="number" name="<?= $k ?>" value="<?= $plxAdmin->aConf[$k] ?>" min="1" max="3072" />
			<span class="surface">X</span>
			<input type="number" name="<?= $infos[1] ?>" value="<?= $plxAdmin->aConf[$infos[1]] ?>"  min="1" max="1920" />
		</label>
<?php

			}
		} else {
			# $infos[1] is missing. one input[type="number"] only
?>
		<label>
			<span><?= $infos[0] ?></span>
			<input type="number" name="<?= $k ?>" value="<?= $plxAdmin->aConf[$k] ?>"  min="1" max="50" />
		</label>
<?php
		}
	}

	if($k == 'thumbs') {
		plxUtils::printThumbnail($plxAdmin->aConf);
	}
}
?>
	</fieldset>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsDisplay'))
?>
</form>
<?php

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsDisplayFoot'));

# On inclut le footer
include 'foot.php';

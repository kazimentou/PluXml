<?php

/**
 * Themes administration
 *
 * @package PLX
 * @author  Stephane F
 **/

include 'prepend.php';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

# On édite la configuration
if (!empty($_POST)) {
    $plxAdmin->editConfiguration($_POST);
    header('Location: parametres_themes.php');
    exit;
}

# On inclut le header
include 'top.php';

$plxThemes = new plxThemes(PLX_ROOT . $plxAdmin->aConf['racine_themes'], $plxAdmin->aConf['style']);

?>
<form method="post" id="form_themes">
	<?= plxToken::getTokenPostMethod() ?>
    <div class="adminheader">
        <div>
            <h2 class="h3-like"><?= L_CONFIG_VIEW_SKIN_SELECT ?></h2>
			<p>
				<a href="<?= PLX_URL_RESSOURCES ?>" target="_blank"><?= L_CONFIG_VIEW_PLUXML_RESSOURCES ?></a>
			</p>
        </div>
        <div>
			<a class="button btn--primary" href="parametres_edittpl.php"><?= L_TEMPLATES_EDIT ?></a>
        </div>
    </div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminThemesDisplayTop'))
?>
    <div class="admin">
        <ul class="themes unstyled">
<?php
if ($plxThemes->themesList):
	foreach ($plxThemes->themesList as $theme):
        $currentTheme = ($theme == $plxAdmin->aConf['style']) ? 'activeTheme' : '';
?>
			<li>
				<button type="radio" name="style" value="<?= $theme ?>" class="theme <?= ($theme == $plxThemes->activeTheme) ? 'active' : ''; ?>">
					<div>
						<?= $plxThemes->getImgPreview($theme) ?>
					</div>
<?php
		if ($aInfos = $plxThemes->getInfos($theme)):?>
					<div class="themeOverlay">
						<div class="themeDetails">
							Version : <strong><?= $aInfos['version'] ?></strong> (<?= $aInfos['date'] ?>)<br/>
							<?= L_AUTHOR ?>&nbsp;:&nbsp;<?= $aInfos['author'] ?><br>
							<a href="<?= $aInfos['site'] ?>" title="" target="_blank"><?= $aInfos['site'] ?></a><br>
							<?= $aInfos['description'] ?>
						</div>
					</div>
					<p>
<?php
			if (is_file(PLX_ROOT . $plxAdmin->aConf['racine_themes'] . $theme . '/lang/' . $plxAdmin->aConf['default_lang'] . '-help.php')):
?>
						<a title="<?= L_HELP_TITLE ?>"
							  href="parametres_help.php?help=theme&amp;page=<?= urlencode($theme) ?>"><?= L_HELP ?></a>
<?php
			endif;
?>
						<strong><?= $aInfos['title'] ?></strong>
<?php
		else:
?>
					<strong><?= $theme ?></strong>
<?php
		endif;
?>
					</p>
				</button>
			</li>
<?php
	endforeach;
else:
?>
                <?= L_NONE1 ?>
<?php
endif;
?>
        </ul>
    </div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminThemesDisplay'))
?>
</form>
<?php

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminThemesDisplayFoot'));

# On inclut le footer
include 'foot.php';

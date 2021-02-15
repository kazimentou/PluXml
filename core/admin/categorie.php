<?php

/**
 * Edition des options d'une catégorie
 *
 * @package PLX
 * @author    Stephane F.
 **/

include 'prepend.php';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCategoryPrepend'));

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_EDITOR);

# On édite la catégorie
if (!empty($_POST) and array_key_exists($_POST['id'], $plxAdmin->aCats)) {
    $plxAdmin->editCategorie($_POST);
    header('Location: categorie.php?p=' . $_POST['id']);
    exit;
} elseif (!empty($_GET['p'])) {
	# On affiche une catégorie
	# On vérifie l'existence de la catégorie
    $id = plxUtils::strCheck($_GET['p']);
    if (!isset($plxAdmin->aCats[$id])) {
        plxMsg::Error(L_CAT_UNKNOWN);
        header('Location: categorie.php');
        exit;
    }
} else {
	# Sinon, on redirige
    header('Location: categories.php');
    exit;
}

# On récupère les templates des catégories
$aTemplates = $plxAdmin->getTemplatesCurrentTheme('categorie', L_NONE1);

# On inclut le header
include 'top.php';
?>

<form method="post" id="form_category" class="first-level">
	<?= plxToken::getTokenPostMethod() ?>
	<input type="hidden" name="id" value="<?= $id ?>" />
    <div class="adminheader">
        <div>
            <h2 class="h3-like"><?= L_EDITCAT_PAGE_TITLE; ?> "<?= plxUtils::strCheck(trim($plxAdmin->aCats[$id]['name'])); ?>"</h2>
            <p><a class="icon-left-big" href="categories.php"><?= L_BACK_TO_CATEGORIES ?></a></p>
        </div>
        <div>
            <input class="btn--primary" type="submit" value="<?= L_SAVE ?>"/>
        </div>
    </div>

<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCategoryTop'))
?>
    <fieldset>
		<label class="caption-inside">
			<span><?= L_EDITCAT_DISPLAY_HOMEPAGE ?></span>
			<input  type="checkbox" name="homepage" value="1" class="switch" <?= !empty($plxAdmin->aCats[$id]['homepage']) ? ' checked' : '' ?> />
		</label>
		<label class="caption-inside">
			<span><?= L_TEMPLATE ?></span>
<?php plxUtils::printSelect('template', $aTemplates, $plxAdmin->aCats[$id]['template']) ?>
		</label>
		<div>
			<label for="id_content"><?= L_EDITCAT_DESCRIPTION ?></label>
			<textarea name="content" rows="5" id="id_content"><?= plxUtils::strCheck($plxAdmin->aCats[$id]['description']) ?></textarea>
		</div>
		<div>
<?php plxUtils::printThumbnail($plxAdmin->aCats[$id]); ?>
		</div>
		<div class="meta-tags">
<?php plxUtils::printInputs_Metas_Title($plxAdmin->aCats[$id]); ?>
		</div>
    </fieldset>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCategory'))
?>
</form>
<?php

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCategoryFoot'));

# On inclut le footer
include 'foot.php';

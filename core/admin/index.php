<?php

/**
 * Listing des articles
 *
 * @package PLX
 * @author    Stephane F et Florent MONTHEL, Pedro "P3ter" CADETE
 **/

include 'prepend.php';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminIndexPrepend'));

# Suppression des articles selectionnes
if (isset($_POST['selection']) and !empty($_POST['sel']) and ($_POST['selection'] == 'delete') and isset($_POST['idArt'])) {
    foreach ($_POST['idArt'] as $k => $v) $plxAdmin->delArticle($v);
    header('Location: index.php');
    exit;
}

# Récuperation de l'id de l'utilisateur
$userId = ($_SESSION['profil'] < PROFIL_WRITER ? '[0-9]{3}' : $_SESSION['user']);

# Récuperation des paramètres
if (!empty($_GET['sel']) and in_array($_GET['sel'], array('all', 'published', 'draft', 'mod'))) {
    $_SESSION['sel_get'] = plxUtils::nullbyteRemove($_GET['sel']);
    $_SESSION['sel_cat'] = '';
} else
    $_SESSION['sel_get'] = (isset($_SESSION['sel_get']) and !empty($_SESSION['sel_get'])) ? $_SESSION['sel_get'] : 'all';

if (!empty($_POST['sel_cat']))
    if (isset($_SESSION['sel_cat']) and $_SESSION['sel_cat'] == $_POST['sel_cat']) # annulation du filtre
        $_SESSION['sel_cat'] = 'all';
    else # prise en compte du filtre
        $_SESSION['sel_cat'] = $_POST['sel_cat'];
else
    $_SESSION['sel_cat'] = (isset($_SESSION['sel_cat']) and !empty($_SESSION['sel_cat'])) ? $_SESSION['sel_cat'] : 'all';

# Recherche du motif de sélection des articles en fonction des paramètres
$catIdSel = '';
$mod = '';
switch ($_SESSION['sel_get']) {
    case 'published':
        $catIdSel = '[home|0-9,]*FILTER[home|0-9,]*';
        $mod = '';
        break;
    case 'draft':
        $catIdSel = '[home|0-9,]*draft,FILTER[home|0-9,]*';
        $mod = '_?';
        break;
    case 'all':
        $catIdSel = '[home|draft|0-9,]*FILTER[draft|home|0-9,]*';
        $mod = '_?';
        break;
    case 'mod':
        $catIdSel = '[home|draft|0-9,]*FILTER[draft|home|0-9,]*';
        $mod = '_';
        break;
}

switch ($_SESSION['sel_cat']) {
    case 'all' :
        $catIdSel = str_replace('FILTER', '', $catIdSel);
        break;
    case '000' :
        $catIdSel = str_replace('FILTER', '000', $catIdSel);
        break;
    case 'home':
        $catIdSel = str_replace('FILTER', 'home', $catIdSel);
        break;
    case preg_match('/^[0-9]{3}$/', $_SESSION['sel_cat']) == 1:
        $catIdSel = str_replace('FILTER', $_SESSION['sel_cat'], $catIdSel);
}

# Nombre d'article sélectionnés
$nbArtPagination = $plxAdmin->nbArticles($catIdSel, $userId);

# Récupération du texte à rechercher
$artTitle = (!empty($_GET['artTitle'])) ? plxUtils::unSlash(trim(urldecode($_GET['artTitle']))) : '';
if (empty($artTitle)) {
    $artTitle = (!empty($_POST['artTitle'])) ? plxUtils::unSlash(trim(urldecode($_POST['artTitle']))) : '';
}
$_GET['artTitle'] = $artTitle;

# On génère notre motif de recherche
if (is_numeric($_GET['artTitle'])) {
    $artId = str_pad($_GET['artTitle'], 4, '0', STR_PAD_LEFT);
    $motif = '/^' . $mod . $artId . '.' . $catIdSel . '.' . $userId . '.[0-9]{12}.(.*).xml$/';
} else {
    $motif = '/^' . $mod . '[0-9]{4}.' . $catIdSel . '.' . $userId . '.[0-9]{12}.(.*)' . plxUtils::urlify($_GET['artTitle']) . '(.*).xml$/';
}
# Calcul du nombre de page si on fait une recherche
if ($_GET['artTitle'] != '') {
    if ($arts = $plxAdmin->plxGlob_arts->query($motif))
        $nbArtPagination = sizeof($arts);
}

# Traitement
$plxAdmin->prechauffage($motif);
$plxAdmin->getPage();
$arts = $plxAdmin->getArticles('all'); # Recuperation des articles

# Génération de notre tableau des catégories
$aFilterCat['all'] = L_ARTICLES_ALL_CATEGORIES;
$aFilterCat['home'] = L_HOMEPAGE;
$aFilterCat['000'] = L_UNCLASSIFIED;
if ($plxAdmin->aCats) {
    foreach ($plxAdmin->aCats as $k => $v) {
        $aCat[$k] = plxUtils::strCheck($v['name']);
        $aFilterCat[$k] = plxUtils::strCheck($v['name']);
    }
    $aAllCat[L_CATEGORIES] = $aCat;
}
$aAllCat[L_SPECIFIC_CATEGORIES_TABLE]['home'] = L_HOMEPAGE;
$aAllCat[L_SPECIFIC_CATEGORIES_TABLE]['draft'] = L_DRAFT;
$aAllCat[L_SPECIFIC_CATEGORIES_TABLE][''] = L_ALL_ARTICLES_CATEGORIES_TABLE;

# On inclut le header
include __DIR__ . '/top.php';
?>

<?php eval($plxAdmin->plxPlugins->callHook('AdminIndexTop')) # Hook Plugins ?>

<?php
$nbArticlesDraft = $plxAdmin->nbArticles('draft', $userId);
$nbArticlesWaiting = $plxAdmin->nbArticles('all', $userId, '_');
?>

<div class="adminheader">
    <h2 class="h3-like"><?= L_ARTICLES_LIST ?></h2>
    <ul>
        <li <?= ($_SESSION['sel_get'] == 'all') ? 'class="selected" ' : '' ?>>
			<a href="index.php?sel=all&amp;page=1"><?= L_ALL ?></a>
			<span class="tag"><?= $plxAdmin->nbArticles('all', $userId) ?></span>
		</li>
        <li <?= ($_SESSION['sel_get'] == 'published') ? 'class="selected" ' : '' ?>>
			<a href="index.php?sel=published&amp;page=1"><?= L_ALL_PUBLISHED ?></a>
			<span class="tag"><?= $plxAdmin->nbArticles('published', $userId, '') ?></span>
		</li>
        <li <?= ($_SESSION['sel_get'] == 'draft') ? 'class="selected" ' : '' ?>>
			<a href="index.php?sel=draft&amp;page=1"><?= L_ALL_DRAFTS ?></a><?php if ($nbArticlesDraft > 0) : ?>
			<span class="tag"><?= $nbArticlesDraft ?></span><?php endif; ?>
		</li>
        <li <?= ($_SESSION['sel_get'] == 'mod') ? 'class="selected" ' : '' ?>>
			<a href="index.php?sel=mod&amp;page=1"><?= L_AWAITING ?></a>
<?php if ($nbArticlesWaiting > 0) : ?>
			<span class="tag--warning"><?= $nbArticlesWaiting ?></span>
<?php endif; ?>
		</li>
    </ul>
</div>

<div class="admin">

    <?php eval($plxAdmin->plxPlugins->callHook('AdminTopBottom')); # Hook Plugins ?>

    <form action="index.php" method="post" id="form_articles">

        <div class="mtm pas grid-2-small-1 tableheader">
            <div>
                <a href="<?= PLX_CORE ?>admin/article.php"><span class="btn btn--primary"><i class="icon-plus"></i><?= L_NEW_ARTICLE ?></span></a>
                <?= PlxToken::getTokenPostMethod(); ?>
                <?php PlxUtils::printSelect('sel_cat', $aFilterCat, $_SESSION['sel_cat']) ?>
                <button class="<?= $_SESSION['sel_cat'] != 'all' ? ' select' : '' ?> btn--primary" type="submit"><i class="icon-filter"></i><?= L_ARTICLES_FILTER_BUTTON ?></button>
            </div>
            <div class="txtright">
                <input id="index-search" placeholder="<?= L_SEARCH_PLACEHOLDER ?>" type="text" name="artTitle" value="<?= PlxUtils::strCheck($_GET['artTitle']) ?>"/>
                <button class="<?= (!empty($_GET['artTitle']) ? ' select' : '') ?> btn--primary" type="submit"><i class="icon-search"></i><?= L_SEARCH ?></button>
            </div>
        </div>
		<table class="table scrollable mb0">
			<thead>
				<tr>
					<th class="checkbox"><input type="checkbox" onclick="checkAll(this.form, 'idArt[]')" /></th>
					<th>#</th>
					<th><?= L_DATE ?></th>
					<th class="w100"><?= L_TITLE ?></th>
					<th><?= L_ARTICLE_LIST_CATEGORIES ?></th>
					<th><?= L_ARTICLE_LIST_NBCOMS ?></th>
					<th><?= L_AUTHOR ?></th>
					<th><?= L_ACTION ?></th>
				</tr>
			</thead>
			<tbody>
<?php
# On va lister les articles
if ($arts) { # On a des articles
	# Initialisation de l'ordre
	$num = 0;
	$datetime = date('YmdHi');
	while ($plxAdmin->plxRecord_arts->loop()) {
		# Pour chaque article
		$author = PlxUtils::getValue($plxAdmin->aUsers[$plxAdmin->plxRecord_arts->f('author')]['name']);
		$publi = (boolean)!($plxAdmin->plxRecord_arts->f('date') > $datetime);
		# Catégories : liste des libellés de toutes les categories
		$draft = '';
		$libCats = '';
		$aCats = array();
		$catIds = explode(',', $plxAdmin->plxRecord_arts->f('categorie'));
		if (sizeof($catIds) > 0) {
			foreach ($catIds as $catId) {
				$selected = ($catId == $_SESSION['sel_cat'] ? ' selected="selected"' : '');
				if ($catId == 'draft') $draft = '&nbsp;<span class="tag--info">' . L_DRAFT . '</span>';
				elseif ($catId == 'home') $aCats['home'] = '<option value="home"' . $selected . '>' . L_HOMEPAGE . '</option>';
				elseif ($catId == '000') $aCats['000'] = '<option value="000"' . $selected . '>' . L_UNCLASSIFIED . '</option>';
				elseif (isset($plxAdmin->aCats[$catId])) $aCats[$catId] = '<option value="' . $catId . '"' . $selected . '>' . PlxUtils::strCheck($plxAdmin->aCats[$catId]['name']) . '</option>';
			}

		}
		# en attente de validation ?
		$idArt = $plxAdmin->plxRecord_arts->f('numero');
		$awaiting = $idArt[0] == '_' ? '&nbsp;<span class="tag--warning">' . L_AWAITING . '</span>' : '';
		# Commentaires
		$nbComsToValidate = $plxAdmin->getNbCommentaires('/^_' . $idArt . '.(.*).xml$/', 'all');
		$nbComsValidated = $plxAdmin->getNbCommentaires('/^' . $idArt . '.(.*).xml$/', 'all');
		# On affiche la ligne
?>
				<tr>
					<td><input type="checkbox" name="idArt[]" value="<?= $idArt ?>" /></td>
					<td><?= $idArt ?></td>
					<td><?= PlxDate::formatDate($plxAdmin->plxRecord_arts->f('date')) ?></td>
					<td>
						<a href="article.php?a=<?= $idArt ?>" title="<?= L_ARTICLE_EDIT_TITLE ?>">
							<?= PlxUtils::strCheck($plxAdmin->plxRecord_arts->f('title')) ?>
						</a>
						<?= $draft . $awaiting ?>
					</td>
					<td>
<?php
		if (sizeof($aCats) > 1) {
?>
						<select name="sel_cat2" class="ddcat" onchange="this.form.sel_cat.value=this.value;this.form.submit()">
						<?= implode(PHP_EOL, $aCats) ?>
						</select>
<?php
		} else {
?>
						<?= strip_tags(implode(PHP_EOL, $aCats)) ?>
<?php
		}
?>
					</td>
					<td><a title="<?= L_NEW_COMMENTS_TITLE ?>" href="comments.php?sel=offline&a=<?= $plxAdmin->plxRecord_arts->f('numero') ?>&page=1"><?= $nbComsToValidate ?></a> / <a title="<?= L_VALIDATED_COMMENTS_TITLE ?>" href="comments.php?sel=online&a=<?= $plxAdmin->plxRecord_arts->f('numero') ?>&page=1"><?= $nbComsValidated ?></a></td>
					<td><?= PlxUtils::strCheck($author) ?></td>
					<td>
						<button><a href="article.php?a=<?= $idArt ?>" title="<?= L_ARTICLE_EDIT_TITLE ?>"><i class="icon-pencil"></i></a></button>
<?php
		if ($publi and $draft == '') {
			# Si l'article est publié
?>
						<button><a href="<?= $plxAdmin->urlRewrite('?article' . intval($idArt) . '/' . $plxAdmin->plxRecord_arts->f('url')) ?>" title="<?= L_ARTICLE_VIEW_TITLE ?>"><i class="icon-eye"></i></a></button>
<?php
		}
?>
					</td>
				</tr>
<?php
	} # end of while
} else {
	# Pas d'article
?>
				<tr>
					<td colspan="8" class="center"><?= L_NO_ARTICLE ?></td>
				</tr>
<?php
}
?>
			</tbody>
		</table>
<?php if ($_SESSION['profil'] <= PROFIL_MODERATOR) : ?>
        <div class="pas grid-2-small-1 tablefooter">
				<div>
					<!-- <input class="btn--warning" name="delete" type="submit" value="<?= L_DELETE ?>" onclick="return confirmAction(this.form, 'delete', 'idArt[]', '<?= L_CONFIRM_DELETE ?>')" /> -->
					<button class="submit btn--warning" name="delete" type="submit"><i class="icon-trash"></i><?= L_DELETE ?></button>
					<?php PlxUtils::printInput('page', 1, 'hidden'); ?>
			</div>
<?php endif; ?>
			<div class="pagination right">
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminIndexPagination'));

//TODO PlxAdmin pagination function
if ($arts) { # if there is articles
	//Pagination preparation
	$last_page = ceil($nbArtPagination / $plxAdmin->bypage);
	$stop = $plxAdmin->page + 2;
	if ($stop < 5) $stop = 5;
	if ($stop > $last_page) $stop = $last_page;
	$start = $stop - 4;
	if ($start < 1) $start = 1;
	// URL generation
	$artTitle = (!empty($_GET['artTitle']) ? '&amp;artTitle=' . urlencode($_GET['artTitle']) : '');
	$p_url = 'index.php?page=' . ($plxAdmin->page - 1) . $artTitle;
	$n_url = 'index.php?page=' . ($plxAdmin->page + 1) . $artTitle;
	$l_url = 'index.php?page=' . $last_page . $artTitle;
	$f_url = 'index.php?page=1' . $artTitle;
	// Display pagination links
	$s = $plxAdmin->page > 2 ? '<a href="' . $f_url . '" title="' . L_PAGINATION_FIRST_TITLE . '"><span class="btn"><i class="icon-angle-double-left"></i></span></a>' : '<span class="btn"><i class="icon-angle-double-left"></i></span>';
	echo $s;
	$s = $plxAdmin->page > 1 ? '<a href="' . $p_url . '" title="' . L_PAGINATION_PREVIOUS_TITLE . '"><span class="btn"><i class="icon-angle-left"></i></span></a>' : '<span class="btn"><i class="icon-angle-left"></i></span>';
	echo $s;
	for ($i = $start; $i <= $stop; $i++) {
		$s = $i == $plxAdmin->page ? '<span class="current btn">' . $i . '</span>' : '<a href="' . ('index.php?page=' . $i . $artTitle) . '" title="' . $i . '"><span class="btn">' . $i . '</span></a>';
		echo $s;
	}
	$s = $plxAdmin->page < $last_page ? '<a href="' . $n_url . '" title="' . L_PAGINATION_NEXT_TITLE . '"><span class="btn"><i class="icon-angle-right"></i></span></a>' : '<span class="btn"><i class="icon-angle-right"></i></span>';
	echo $s;
	$s = $plxAdmin->page < ($last_page - 1) ? '<a href="' . $l_url . '" title="' . L_PAGINATION_LAST_TITLE . '"><span class="btn"><i class="icon-angle-double-right"></i></span></a>' : '<span class="btn"><i class="icon-angle-double-right"></i></span>';
	echo $s;
}
?>
			</div>
        </div>
    </form>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminIndexFoot'));

# On inclut le footer
include __DIR__ . '/foot.php';

<?php

/**
 * Edition d'un article
 *
 * @package PLX
 * @author    Stephane F, Florent MONTHEL, Jean-Pierre Pourrez
 **/

include 'prepend.php';

# Control du token du formulaire
if (!isset($_POST['preview'])) {
    plxToken::validateFormToken($_POST);
}

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminArticlePrepend'));

# validation de l'id de l'article si passé en parametre
if (isset($_GET['a']) and !preg_match('/^_?\d{4}$/', $_GET['a'])) {
    plxMsg::Error(L_ERR_UNKNOWN_ARTICLE); # mauvais format d'identifiant d'article
    header('Location: index.php');
    exit;
}

const ARTICLE_0 = array(
	'numero'			=> '0000',
	'title'				=> L_NEW_ARTICLE,
	'chapo'				=> '',
	'content'			=> '',
	'tags'				=> '',
	'author'			=> '',
	'url'				=> '',
	'tags'				=> '',
	'allow_com'			=> '',
	'template'			=> 'article.php',
	'date_update'		=> '',
	'meta_description'	=> '',
	'meta_keywords'		=> '',
	'title_htmltag'		=> '',
);

# Soumission des données du formulaire
if (!empty($_POST)) { # Création, mise à jour, suppression ou aperçu

    # droits réduits pour cet utilisateur
    if ($_SESSION['profil'] == PROFIL_WRITER) {
		# on  force l'identifiant de l'auteur avec l'utilisateur connecté
		if(empty($_POST['author']) or $_SESSION['user'] != $_POST['author']) {
			$_POST['author'] = $_SESSION['user'];
		}
		# On contrôle si l'utilisateur est l'auteur de l'article
		if(
			isset($_POST['artId']) and
			$_POST['artId'] != '0000' and
			# format général d'un nom de fichier-article : '@^_?\d{4}\.(?:draft,|\d{3},)*(?:home|\d{3})(,\d{3})*\.\d{3}\.\d{12}\..*\.xml$@'
			empty($plxAdmin->plxGlob_arts->query(
				'@^_?' . $_POST['artId'] .'\.(?:draft,|\d{3},)*(?:home|\d{3})(,\d{3})*\.' . $_SESSION['user'] . '\.\d{12}\..*\.xml$@')
			)
		) {
			# On rejete la soumission du formulaire
            plxMsg::Error(L_ERR_UNKNOWN_ARTICLE);
            header('Location: index.php');
            exit;
		}
	}

    if (!isset($_POST['catId'])) {
		# article non classé
		$_POST['catId'] = array('000');
	}

    # Si demande d'enregistrement en brouillon on ajoute la categorie draft à la liste et on retire la demande de validation
    if (isset($_POST['draft']) and !in_array('draft', $_POST['catId'])) {
		# draft toujours n°1
		array_unshift($_POST['catId'], 'draft');
	}

    # Si demande de publication ou demande de validation, on supprime la catégorie draft si elle existe
    if (isset($_POST['update']) or isset($_POST['publish']) or isset($_POST['moderate'])) {
		$_POST['catId'] = array_filter($_POST['catId'], function ($a) {
	        return $a != 'draft';
	    });
	}

    # Titre par défaut si titre vide
    if (trim($_POST['title']) == '') {
		$_POST['title'] = L_NEW_ARTICLE;
	}

    # --------- Previsualisation d'un article ---------
    if (isset($_POST['preview'])) {
        $tmpStr = (!empty(trim($_POST['url']))) ? $_POST['url'] : $_POST['title'];
        $tmpUrl = plxUtils::urlify($tmpStr);
        $art = array(
	        'title'				=> trim($_POST['title']),
	        'url'				=> !empty($tmpUrl) ? $tmpUrl : L_DEFAULT_NEW_ARTICLE_URL,
	        'allow_com'			=> $_POST['allow_com'],
	        'template'			=> basename($_POST['template']),
	        'chapo'				=> trim($_POST['chapo']),
	        'content'			=> trim($_POST['content']),
	        'categorie'			=> implode(',', array_filter($_POST['catId'], function($value) { $value != 'draft'; })),
	        'tags'				=> trim($_POST['tags']),
	        'meta_description'	=> $_POST['meta_description'],
	        'meta_keywords'		=> $_POST['meta_keywords'],
	        'title_htmltag'		=> $_POST['title_htmltag'],
	        'filename'			=> '',
	        'numero'			=> $_POST['artId'],
	        'author'			=> $_POST['author'],
	        'thumbnail'			=> $_POST['thumbnail'],
	        'thumbnail_title'	=> $_POST['thumbnail_title'],
	        'thumbnail_alt'		=> $_POST['thumbnail_alt'],
			'nb_com'			=> 0,
        );
        foreach(plxDate::ENTRIES as $k) {
			$art[$k] = substr(preg_replace('@\D@', '', $_POST[$k][0] . $_POST[$k][1]), 0, 12);
		}

		# compatibilité avec les anciennes versions de PluXml
		# $art['date'] = $art['date_publication'];

        # Hook Plugins
        eval($plxAdmin->plxPlugins->callHook('AdminArticlePreview'));

        $article[0] = $art;
        $_SESSION['preview'] = $article;
        header('Location: ' . PLX_ROOT . 'index.php?preview');
        exit;
    }

    # --------- Suppression d'un article --------------
    if (isset($_POST['delete'])) {
        $plxAdmin->delArticle($_POST['artId']);
        header('Location: index.php');
        exit;
    }

    # --------- Mode création ou maj -------------
    if (isset($_POST['update']) or isset($_POST['publish']) or isset($_POST['moderate']) or isset($_POST['draft'])) {

        $valid = true;

        # Vérification de l'unicité de l'url
        # Problème si plusieurs articles ont le même titre !
        $url = plxUtils::urlify(!empty($_POST['url']) ? $_POST['url'] : $_POST['title']);
        $artId = $_POST['artId'];
        $filenames = array_filter($plxAdmin->plxGlob_arts->aFiles, function($value) use($url, $artId) {
			return (
				preg_match('@^_?\d{4}\.(?:draft,|\d{3},)*(?:home|\d{3})(,\d{3})*\.\d{3}\.\d{12}\.' . $url . '\.xml$@', $value) and
				!preg_match('@^_?' . $artId . '\.@', $value)
			);
		});
        if(!empty($filenames)) {
			$valid = false;
			plxMsg::Error(L_ERR_URL_ALREADY_EXISTS . " : " . plxUtils::strCheck($url));
		}

		if($valid) {
			# Contrôle de la validité des dates
			foreach(plxDate::ENTRIES as $k) {
				if(!plxDate::checkDate5($_POST[$k][0], $_POST[$k][1])) {
					$valid = false;
					break;
				}
			}

			if($valid) {
	            $plxAdmin->editArticle($_POST, $_POST['artId']);
	            header('Location: article.php?a=' . $_POST['artId']);
	            exit;
			} else {
				plxMsg::Error(L_BAD_DATE_FORMAT);
			}
		}

		# Le formulaire n'a pas été validé. Retour en mode brouillon (draft) sans sauvegarde
		array_unshift($_POST['catId'], 'draft');
    }

    # ------------ Ajout d'une catégorie -----------
    if (isset($_POST['new_category'])) {
        # Ajout de la nouvelle catégorie
        $plxAdmin->editCategories($_POST);

        # On recharge la nouvelle liste
        $plxAdmin->getCategories(path('XMLFILE_CATEGORIES'));
        $_GET['a'] = $_POST['artId'];
    }

    # Alimentation des variables
    $catIds = isset($_POST['catId']) ? $_POST['catId'] : array();

    $dates5 = array();
    foreach(plxDate::ENTRIES as $k) {
		$dates5[$k][0] = $_POST[$k][0]; # date au format yyyy-mm-dd
		$dates5[$k][1] = $_POST[$k][1]; # heure au format hh:ii
	}

	$result = array();
	foreach(array_keys(ARTICLE_0) as $k) {
		switch($k) {
			case 'numero': $result['numero'] = filter_input(INPUT_POST, 'artId', FILTER_SANITIZE_STRING); break;
			case 'date_update': $result['date_update'] = filter_input(INPUT_POST, 'date_update_old', FILTER_SANITIZE_STRING); break;
			default: $result[$k] = filter_input(INPUT_POST, $k, FILTER_SANITIZE_STRING);
		}
	}

    # Hook Plugins
    eval($plxAdmin->plxPlugins->callHook('AdminArticlePostData'));

    # Fin de traitement du formulaire par methode="post"
} elseif (!empty($_GET['a'])) { # On n'a rien validé, c'est pour l'édition d'un article
    # On va rechercher notre article
    if (($aFile = $plxAdmin->plxGlob_arts->query('/^' . $_GET['a'] . '\..+\.xml$/')) == false) { # Article inexistant
        plxMsg::Error(L_ERR_UNKNOWN_ARTICLE);
        header('Location: index.php');
        exit;
    }

    # On parse et alimente nos variables
    $result = $plxAdmin->parseArticle(PLX_ROOT . $plxAdmin->aConf['racine_articles'] . $aFile['0']);
	$dates5 = plxDate::date2html5($result); # récupère les dates - version PluXml >= 6.0.0
    $catIds = explode(',', $result['categorie']);

    if ($result['author'] != $_SESSION['user'] and $_SESSION['profil'] == PROFIL_WRITER) {
        plxMsg::Error(L_ERR_FORBIDDEN_ARTICLE);
        header('Location: index.php');
        exit;
    }

    # Hook Plugins
    eval($plxAdmin->plxPlugins->callHook('AdminArticleParseData'));

} else {
	# Création d'un article
    $aDatetime = explode(' ', date('Y-m-d H:i'));
    $dates5 = array(); # version PluXml >= 6.0.0
    foreach(plxDate::ENTRIES as $k) {
		$dates5[$k] = $aDatetime; # tableau 2 élements pour <input type="date"> et <input type="time">
	}
    $catIds = array('draft', '000');

    $result = ARTICLE_0;
    # quelques ajustements selon le contexte
    $result['author'] = $_SESSION['user'];
    $result['allow_com'] = $plxAdmin->aConf['allow_com'];

    # Hook Plugins
    eval($plxAdmin->plxPlugins->callHook('AdminArticleInitData'));
}

# On inclut le header
include 'top.php';

# On construit la liste des utilisateurs
foreach ($plxAdmin->aUsers as $_userid => $_user) {
    if ($_user['active'] and !$_user['delete']) {
        if ($_user['profil'] == PROFIL_ADMIN)
            $_users[L_PROFIL_ADMIN][$_userid] = plxUtils::strCheck($_user['name']);
        elseif ($_user['profil'] == PROFIL_MANAGER)
            $_users[L_PROFIL_MANAGER][$_userid] = plxUtils::strCheck($_user['name']);
        elseif ($_user['profil'] == PROFIL_MODERATOR)
            $_users[L_PROFIL_MODERATOR][$_userid] = plxUtils::strCheck($_user['name']);
        elseif ($_user['profil'] == PROFIL_EDITOR)
            $_users[L_PROFIL_EDITOR][$_userid] = plxUtils::strCheck($_user['name']);
        else
            $_users[L_PROFIL_WRITER][$_userid] = plxUtils::strCheck($_user['name']);
    }
}

# On récupère les templates des articles
$aTemplates = $plxAdmin->getTemplatesCurrentTheme('article', L_NONE1);

$cat_id = '000';
$artId = $result['numero'];
?>
<form method="post" id="form_article">
    <?= PlxToken::getTokenPostMethod() ?>
    <input type="hidden" name="artId" value="<?= $artId ?>" />
    <input type="hidden" name="date_update_old" value="<?= $result['date_update'] ?>" />
    <div class="adminheader">
        <div>
            <h2 class="h3-like"><?= (empty($_GET['a'])) ? L_NEW_ARTICLE : L_ARTICLE_EDITING; ?></h2>
            <p><a class="back icon-left-big" href="index.php"><?= L_BACK_TO_ARTICLES ?></a></p>
        </div>
        <div>
            <p class="inbl"><span class="label-like"><?= L_ARTICLE_STATUS ?></span>
                <strong><?php
//TODO create a PlxAdmin function to get article status (P3ter)
if (isset($_GET['a']) and preg_match('/^_\d{4}$/', $_GET['a']))
	echo L_AWAITING;
elseif (in_array('draft', $catIds)) {
	echo L_DRAFT;
?><input type="hidden" name="catId[]" value="draft" /><?php
} else
	echo L_PUBLISHED;
?></strong>
            </p>
            <div>
	            <input class="btn--primary" type="submit" name="preview" value="<?= L_ARTICLE_PREVIEW_BUTTON ?>" onclick="this.form.target = '_blank';" />
<?php
if ($_SESSION['profil'] > PROFIL_MODERATOR and $plxAdmin->aConf['mod_art']) {
	# L'utilisateur a des droits réduits (pas de modération).
	if (in_array('draft', $catIds)) { # brouillon
		if ($artId != '0000') {
			# article à modérer
?>
				<input class="btn--primary" type="submit" name="draft" value="<?= L_ARTICLE_DRAFT_BUTTON ?>" />
				<input class="btn--primary" type="submit" name="moderate" value="<?= L_ARTICLE_MODERATE_BUTTON ?>" />
<?php
		}
	} else {
		if (isset($_GET['a']) and preg_match('/^_\d{4}$/', $_GET['a'])) {
			# en attente
?>
				<input class="btn--primary" type="submit" name="update" value="<?= L_SAVE ?>" />
				<input class="btn--primary" type="submit" name="draft" value="<?= L_ARTICLE_DRAFT_BUTTON ?>" />
<?php
		} else {
?>
				<input class="btn--inverse" type="submit" name="draft" value="<?= L_ARTICLE_DRAFT_BUTTON ?>"/>
				<input class="btn--inverse" type="submit" name="moderate" value="<?= L_ARTICLE_MODERATE_BUTTON ?>"/>
<?php
		}
	}
} else {
	# L'utilisateur peut modérer l'article.
	if (in_array('draft', $catIds)) {
		# brouillon
?>
				<input class="btn--primary" type="submit" name="draft" value="<?= L_ARTICLE_DRAFT_BUTTON ?>" />
				<input class="btn--primary" type="submit" name="publish" value="<?= L_ARTICLE_PUBLISHING_BUTTON ?>" />
<?php
	} else {
		if (!isset($_GET['a']) or preg_match('/^_\d{4}$/', $_GET['a'])) {
?>
				<input class="btn--primary" type="submit" name="publish" value="<?= L_ARTICLE_PUBLISHING_BUTTON ?> "/>
<?php
		}
		else {
?>
				<input class="btn--primary" type="submit" name="update" value="<?= L_SAVE ?>" />
<?php
			if(!empty($_GET['a'] and substr($_GET['a'], 0, 1) != '_')) {
?>
				<input class="btn--primary" type="submit" name="draft" value="<?= L_SET_OFFLINE ?>" />
<?php
			}
		}
	}
}
	if (!empty($artId) and $artId != '0000') {
		# l'article existe déjà. On peut le supprimer.
?>
				<input class="btn--warning" type="submit" name="delete" value="<?= L_DELETE ?>" onclick="return confirm('<?= L_ARTICLE_DELETE_CONFIRM ?>');" />
<?php
	}
?>
	        </div>
        </div>
    </div>

    <div>
<?php eval($plxAdmin->plxPlugins->callHook('AdminArticleTop')) # Hook Plugins ?>
        <div id="admin-art">
            <div>
                <div>
                    <fieldset>
						<label class="fullwidth caption-inside">
							<span><?= L_TITLE ?></span>
							<input type="text" name="title" value="<?= PlxUtils::strCheck(trim($result['title'])) ?>" maxlength="80" required />
						</label>
<?php
if ($artId != '' and $artId != '0000') {
	$link = $plxAdmin->urlRewrite('?article' . intval($artId) . '/' . $result['url']);
?>
						<p>
							<strong class="label-like"><?= L_LINK_FIELD ?></strong>
							<a target="_blank" href="<?= $link ?>" title="<?= L_LINK_ACCESS ?> : <?= $link ?>"><?= $link ?></a>
						</p>
<?php
}
?>
                        <div>
                            <input class="toggle" id="toggle_chapo" type="checkbox" <?= (empty($_GET['a']) || !empty(trim($result['chapo']))) ? ' checked' : ''; ?>>
                            <label class="drop" for="toggle_chapo"><?= L_HEADLINE_FIELD; ?></label>
                            <textarea name="chapo" rows="8" class="drop-box" id="id_chapo"><?= PlxUtils::strCheck(trim($result['chapo'])) ?></textarea>
                        </div>
                        <div>
                            <label for="id_content"><?= L_CONTENT_FIELD ?></label>
                            <textarea name="content" rows="20" id="id_content"><?= PlxUtils::strCheck(trim($result['content'])) ?></textarea>
                        </div>
<?php plxUtils::printInputs_Metas_Title($result); ?>
						<label class="fullwidth caption-inside">
							<span><?= L_URL ?></span>
							<input type="text" name="url" value="<?= $result['url'] ?>" maxlength="127" placeholder="<?= L_ARTICLE_URL_FIELD_TITLE ?>" />
						</label>
                    </fieldset>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminArticleContent'))
?>
                </div>
            </div>

            <!-- SIDEBAR FOR ARTICLE -->
            <div id="aside-art" class="sidebar">
                <fieldset class="pan">
                    <div class="flex-container--column">
<?php /* ------ author ------ */ ?>
<?php
	if ($_SESSION['profil'] < PROFIL_WRITER) {
?>
						<label class="fullwidth caption-inside">
							<span><?= L_AUTHOR ?></span>
<?php PlxUtils::printSelect('author', $_users, $result['author']); ?>
						</label>
<?php
	} else {
?>
                        <div>
                            <input type="hidden" id="id_author" name="author" value="<?= $result['author'] ?>" />
                            <span><?= L_AUTHOR ?></span> :
                            <strong><?= PlxUtils::strCheck($plxAdmin->aUsers[$result['author']]['name']) ?></strong>
                        </div>
<?php
	}
?>
<?php /* ------ vignette ------ */ ?>
                        <div>
<?php plxUtils::printThumbnail(!empty($_POST) ? $_POST : (!empty($result) ? $result : false)); ?>
                        </div>
<?php
/* -------- categories --------- */
$checked = ($artId != '0000' and !empty($catIds) and (count($catIds) > 1 or $catIds[0] != '000')) ? ' checked' : '';
?>
                        <input class="toggle" id="toggle_categories" type="checkbox"<?= $checked ?> />
                        <label class="drop collapsible" for="toggle_categories">Categories</label>
                        <div class="expander">
                            <ul id="cats-art" class="unstyled">
<?php
# on boucle sur les catégories
foreach (array_merge(
	array(
		'000'	=> array('name' => L_UNCLASSIFIED),
		'home'	=> array('name' => L_HOMEPAGE),
	),
	$plxAdmin->aCats
) as $cat_id => $cat_name) {
	$selected = (
		(empty($catIds) and $cat_id == '000') or
		(!empty($catIds) and in_array($cat_id, $catIds))
	) ? ' checked="checked"' : '';
	$className = !isset($plxAdmin->aCats[$cat_id]['active']) ? ' class="noactive"' : '';
?>
								<li>
									<label <?= $className ?>>
										<input type="checkbox" name="catId[]" <?= $selected ?> value="<?= $cat_id ?>"/>
										<?= PlxUtils::strCheck($cat_name['name']) ?>
									</label>
								</li>
<?php
}
?>
                            </ul>
<?php
if ($_SESSION['profil'] < PROFIL_WRITER) { ?>
							<div id="new-categorie">
								<input type="text" name="new_catname" maxlength="32" placeholder="<?= L_NEW_CATEGORY ?>" />
								<input class="btn" type="submit" name="new_category" value="<?= L_ADD ?>"/>
							</div>
<?php
}
?>
                        </div>
<?php
/* ------ tags ------ */
$tags = trim($result['tags']);
$checked = !empty($tags) ? ' checked' : '';
?>
						<input type="checkbox" class="toggle" id="toggle_tags"<?= $checked ?> />
                        <label class="drop collapsible" for="toggle_tags">Tags</label>
                        <div class="expander">
							<label for="id_tags"><?= L_ARTICLE_TAGS_FIELD; ?></label>
							<div class="tooltip icon-help-circled">
								<span class="tooltiptext"><?= L_ARTICLE_TAGS_FIELD_TITLE ?></span>
							</div>
<?php
if ($plxAdmin->aTags) {
	/*
	 * */
?>
							<input type="checkbox" class="toggle" id="toggle_tagslist"<?= empty($tags) ? ' checked' : ''; ?>>
							<label for="toggle_tagslist"  class="drop">
								<input type="text" name="tags" value="<?= $result['tags'] ?>" id="id_tags" />
							</label>
							<ul id="tags-list" class="unstyled txtcenter drop-box">
<?php
	$array = array();
	foreach ($plxAdmin->aTags as $tag) {
		if ($tags = array_map('trim', explode(',', $tag['tags']))) {
			foreach ($tags as $tag) {
				if ($tag != '') {
					if (!isset($array[$tag])) {
						$array[$tag] = 1;
					} else {
						$array[$tag]++;
					}
				}
			}
		}
	}
	array_multisort($array);
	foreach ($array as $tagname => $cnt) {
?>
								<li><span><?= PlxUtils::strCheck($tagname) ?></span> <em>( <?= $cnt ?> )</em></li>
<?php
}
?>
							</ul>
<?php
} else {
?>
							<input type="text" name="tags" value="<?= $result['tags'] ?>" id="id_tags" />
							<p><?= L_NO_TAG ?></p>
<?php
}
?>
						</div>
<?php
/* --------- dates ---------- */
plxUtils::printDates($dates5);
?>
						<label class="fullwidth caption-inside">
								<span><?= L_TEMPLATE ?></span>
<?php PlxUtils::printSelect('template', $aTemplates, $result['template']); ?>
						</label>
<?php
/* ------ comments ------ */
if(!empty($plxAdmin->aConf['allow_com'])) {
	$checked = ($artId == '0000' or !empty($result['allow_com'])) ? ' checked' : '';
?>
						<label class="fullwidth caption-inside">
							<span><?= L_ALLOW_COMMENTS ?></span>
							<input type="checkbox" name="allow_com" value="1"<?= $checked ?> />
						</label>

<?php
	if($artId != '0000') {
?>
						<ul class="comments-art">
						   <li>
								<a href="comments.php?a=<?= $artId ?>&amp;page=1"
								   title="<?= L_ARTICLE_MANAGE_COMMENTS_TITLE ?>"><?= L_ARTICLE_MANAGE_COMMENTS ?></a>
							</li>
<?php
		$href = 'comments.php?sel=%s&a=' . $artId . '&page=1';
		$suffix = '\..*\.xml$@';
		foreach(array(
			'@^_'	=> array(L_COMMENT_OFFLINE_COUNT, 'offline', L_NEW_COMMENTS_TITLE),
			'@^'	=> array(L_COMMENT_ONLINE_COUNT, 'online', L_VALIDATED_COMMENTS_TITLE),
		) as $prefix=>$infos) {
			$nbComs = $plxAdmin->getNbCommentaires($prefix . $artId . $suffix, 'all');
			if( $nbComs > 0) {
				list($caption, $selection, $title) = $infos;
?>
							<li class="nbcoms <?= $selection ?>">
								<a href="<?php printf($href, $selection); ?>" title="<?= $title ?>"><?php printf( $caption, $nbComs); ?></a>
							</li>
<?php
			}
		}
?>
							<li>
								<a href="comment_new.php?a=<?= $artId ?>" title="<?= L_NEW_COMMENTS_TITLE ?>">
									<?= L_CREATE_NEW_COMMENT ?>
								</a>
							</li>
						</ul>
<?php
	}
}

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminArticleSidebar'));
?>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</form>
<?php

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminArticleFoot'));

# On inclut le footer
include 'foot.php';

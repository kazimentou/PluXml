<?php

/**
 * Création d'un commentaire
 *
 * @package PLX
 * @author    Florent MONTHEL
 **/

include 'prepend.php';

# Contrôle du token du formulaire
plxToken::validateFormToken($_POST);

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCommentNewPrepend'));

# Contrôle de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_MODERATOR);

# Interdire de l'accès à la page si les commentaires sont désactivés
if (!$plxAdmin->aConf['allow_com']) {
    plxMsg::Error(L_COMMENTS_CLOSED);
    header('Location: index.php');
    exit;
}

# validation de l'id de l'article si passé en paramètre avec $_GET['a']
if (isset($_GET['a'])) {
    if (!preg_match('/^_?(\d{4})$/', $_GET['a'], $capture)) {
        plxMsg::Error(L_ERR_UNKNOWN_ARTICLE);
        header('Location: index.php');
        exit;
    } else {
        $artId = $capture[1];
    }
}
# validation de l'id de l'article si passé en paramètre avec $_GET['c']
if (isset($_GET['c'])) {
    if (!preg_match('/^_?(\d{4})\.(.*)$/', $_GET['c'], $capture)) {
        plxMsg::Error(L_ERR_UNKNOWN_ARTICLE);
        header('Location: index.php');
        exit;
    } else {
        $artId = $capture[1];
    }
}

# On va checker le mode (répondre ou écrire)
if (!empty($_GET['c'])) { # Mode "answer"
    # On check que le commentaire existe et est "online"
    if (!$plxAdmin->getCommentaires('/^' . plxUtils::nullbyteRemove($_GET['c']) . '.xml$/', '', 0, 1, 'all')) {
        # On redirige
        plxMsg::Error(L_ERR_ANSWER_UNKNOWN_COMMENT);
        header('Location: comments.php' . (!empty($_GET['a']) ? '?a=' . $_GET['a'] : ''));
        exit;
    }
    # Commentaire offline
    if (preg_match('/^_/', $_GET['c'])) {
        # On redirige
        plxMsg::Error(L_ERR_ANSWER_OFFLINE_COMMENT);
        header('Location: comments.php' . (!empty($_GET['a']) ? '?a=' . $_GET['a'] : ''));
        exit;
    }
    # On va rechercher notre article
    if (($aFile = $plxAdmin->plxGlob_arts->query('/^' . $artId . '.(.+).xml$/', '', 'sort', 0, 1)) == false) { # Article inexistant
        plxMsg::Error(L_ERR_COMMENT_UNKNOWN_ARTICLE);
        header('Location: index.php');
        exit;
    }
    # Variables de traitement
    if (!empty($_GET['a'])) $get = 'c=' . $_GET['c'] . '&amp;a=' . $_GET['a'];
    else $get = 'c=' . $_GET['c'];
    $aArt = $plxAdmin->parseArticle(PLX_ROOT . $plxAdmin->aConf['racine_articles'] . $aFile['0']);
    # Variable du formulaire
    $content = '';
    $article = '<a href="article.php?a=' . $aArt['numero'] . '" title="' . L_COMMENT_ARTICLE_LINKED_TITLE . '">';
    $article .= plxUtils::strCheck($aArt['title']);
    $article .= '</a>';
    # Ok, on récupère les commentaires de l'article
    $plxAdmin->getCommentaires('/^' . str_replace('_', '', $artId) . '.(.*).xml$/', 'sort');
    # Recherche du parent à partir de l'url
    if ($com = $plxAdmin->comInfoFromFilename($_GET['c'] . '.xml'))
        $parent = $com['comIdx'];
    else
        $parent = '';

} elseif (!empty($_GET['a'])) { # Mode "new"
    # On check l'article si il existe bien
    if (($aFile = $plxAdmin->plxGlob_arts->query('/^' . $_GET['a'] . '.(.+).xml$/', '', 'sort', 0, 1)) == false) {
        plxMsg::Error(L_ERR_COMMENT_UNEXISTENT_ARTICLE);
        header('Location: index.php');
        exit;
    }
    # Variables de traitement
    $artId = $_GET['a'];
    $get = 'a=' . $_GET['a'];
    $aArt = $plxAdmin->parseArticle(PLX_ROOT . $plxAdmin->aConf['racine_articles'] . $aFile['0']);
    # Variable du formulaire
    $content = '';
    $article = '<a href="article.php?a=' . $aArt['numero'] . '" title="' . L_COMMENT_ARTICLE_LINKED_TITLE . '">';
    $article .= plxUtils::strCheck($aArt['title']);
    $article .= '</a>';
    $parent = '';
    # Ok, on récupère les commentaires de l'article
    $plxAdmin->getCommentaires('/^' . str_replace('_', '', $artId) . '.(.*).xml$/', 'sort');
} else { # Mode inconnu
    header('Location: .index.php');
    exit;
}

# On a validé le formulaire
if (!empty($_POST) and !empty($_POST['content'])) {
    # Création du commentaire
    if (!$plxAdmin->newCommentaire(str_replace('_', '', $artId), $_POST)) { # Erreur
        plxMsg::Error(L_ERR_CREATING_COMMENT);
    } else { # Ok
        plxMsg::Info(L_CREATING_COMMENT_SUCCESSFUL);
    }
    header('Location: comment_new.php?a=' . $artId);
    exit;
}

# On inclut le header
include 'top.php';
?>

<form method="post" id="form_comment_new">
    <?= plxToken::getTokenPostMethod() ?>
	<input type="hidden" name="parent" value="<?= $parent ?>" id="id_parent" />
    <div class="adminheader">
        <div>
            <h2 class="h3-like"><?= L_CREATE_NEW_COMMENT; ?></h2>
<?php if (!empty($_GET['a'])) : ?>
			<p><a class="icon-left-big" href="comments.php?a=<?= $_GET['a']; ?>"><?= L_BACK_TO_ARTICLE_COMMENTS ?></a></p>
<?php else : ?>
			<p><a class="icon-left-big" href="comments.php"><?= L_BACK_TO_COMMENTS ?></a></p>
<?php endif; ?>
        </div>
        <div>
            <input class="btn--primary" type="submit" name="create" value="<?= L_SAVE ?>"/>
        </div>
    </div>
    <div class="admin">
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCommentNewTop'));
?>
        <h3><?= ucfirst(L_ARTICLE) ?> &laquo;<?= plxUtils::strCheck($aArt['title']); ?>&raquo;</h3>
        <fieldset>
	        <ul class="unstyled writer">
	            <li><?= L_AUTHOR ?> :
	                <strong><?= plxUtils::strCheck($plxAdmin->aUsers[$_SESSION['user']]['name']); ?></strong>
	            </li>
	            <li><?= L_COMMENT_TYPE_FIELD ?> : <strong>admin</strong></li>
	        </ul>
			<div>
				<div class="comment-header">
					<span><?= L_REPLY_TO ?></span>
					<span  id="id_answer-header"></span>
					<button type="reset" class="button"><?= L_CANCEL ?></button>
				</div>
				<div id="id_answer-content" class="comment-content"></div>
				<div>
					<label for="id_content"><?= L_COMMENT_ARTICLE_FIELD ?></label>
					<textarea name="content" rows="7" id="id_content" required><?= plxUtils::strCheck($content) ?></textarea>
				</div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCommentNew'))
?>
			</div>
        </fieldset>
    </div>
</form>
<div class="admin" id="comments-list">
<?php
if (isset($plxAdmin->plxRecord_coms)) {
	# On a des commentaires
?>
	<h3><?= L_ARTICLE_COMMENTS_LIST ?></h3>
<?php
	# On boucle sur les commentaires
	while ($plxAdmin->plxRecord_coms->loop()) {
		$comId = $plxAdmin->plxRecord_coms->f('article') . '.' . $plxAdmin->plxRecord_coms->f('numero');
		$index = $plxAdmin->plxRecord_coms->f('index');
		$typeAdmin = ($plxAdmin->plxRecord_coms->f('type') == 'admin');
		$fAuthor = $plxAdmin->plxRecord_coms->f('author');
		$fMail = $plxAdmin->plxRecord_coms->f('mail');
		$author = (!$typeAdmin and !empty($fMail)) ? '<a href="mailto:' . $fMail . '" class="icon-mail">' . $fAuthor . '</a>' : $fAuthor;

		$fSite = $plxAdmin->plxRecord_coms->f('site');
		$site =  (!$typeAdmin and !empty($site)) ? '<a href="' . $fSite . '" target="_blank">' . $fSite . '</a>' : '&nbsp;';

		$classList = 'comment level-' . $plxAdmin->plxRecord_coms->f('level');
		if(isset($_GET['c']) and $_GET['c'] == $comId) {
			$classList .= ' active';
		}
		if($typeAdmin) {
			$classList .= ' admin';
		}
?>
	<article class="<?= $classList ?>" id="com-<?= $index ?>">
		<header>
			<div id="com-<?= $index ?>-header" class="comment-header">
				<span class="nbcom">#<?= $plxAdmin->plxRecord_coms->i + 1 ?></span>
				<time datetime="<?= plxDate::formatDate($plxAdmin->plxRecord_coms->f('date'), '#num_year(4)-#num_month-#num_day #hour:#minute'); ?>">
					<?= plxDate::formatDate($plxAdmin->plxRecord_coms->f('date'), '#day #num_day #month #num_year(4) &agrave; #hour:#minute'); ?>
				</time>
				-
				<?= L_WRITTEN_BY ?>&nbsp;<span class="author"><?= $author ?></span> <?= $site ?>
			</div>
			<a href="comment.php<?= (!empty($_GET['a'])) ? '?c=' . $comId . '&a=' . $_GET['a'] : '?c=' . $comId; ?>" class="icon-pencil button" title="<?= L_COMMENT_EDIT_TITLE ?>"></a>
			<a href="#form_comment_new" data-index="<?= $index ?>" title="<?= L_COMMENT_REPLY_TITLE ?>" class="icon-reply-1 button "></a>
		</header>
		<blockquote class="type-<?= $plxAdmin->plxRecord_coms->f('type'); ?>" id="com-<?= $index ?>-content"><?= nl2br($plxAdmin->plxRecord_coms->f('content')); ?></blockquote>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCommentNewList'))
?>
	</article>
<?php
	}
}
?>
</div>
<script>
	(function() {
		'use strict';

	    const form = document.getElementById('form_comment_new');
	    const all_fields = ['header', 'content'];

	    if(form == null) { return; }

	    function replyCom(idCom) {
			const inAnswer = document.getElementById('id_answer');
			all_fields.forEach(function(field) {
				document.getElementById('id_answer-' + field).innerHTML = document.getElementById('com-' + idCom + '-' + field).innerHTML;
			});
			form.elements.parent.value = idCom;
	        form.elements.content.focus();
	    }

	    document.getElementById('comments-list').addEventListener('click', function(event) {
			if(event.target.hasAttribute('data-index')) {
				replyCom(event.target.dataset.index);
			}
		});

	    form.onreset = function() {
			all_fields.forEach(function(field) {
				document.getElementById('id_answer-' + field).textContent = '';
			});
	        form.elements.parent.value = '';
	        console.log('Reset');
	    }

		const parentId = form.parent.value.trim();
	    if (parentId != '') {
	        replyCom(parentId)
	    }
	})();
</script>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminCommentNewFoot'));

# On inclut le footer
include 'foot.php';

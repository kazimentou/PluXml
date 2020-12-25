<?php

/**
 * Backoffice - Medias manager
 *
 * @package PLX
 * @author  Stephane F, Pedro "P3ter" CADETE
 **/

include 'prepend.php';

const MEDIAS_REDIRECTION = 'Location: medias.php?path=';
# Control du token du formulaire
plxToken::validateFormToken($_POST);

$path = filter_input(INPUT_GET, 'path', FILTER_SANITIZE_STRING);
if($path !== null) {
	$folder = $plxAdmin->aConf['medias'] . trim($path, '/');
	if(!is_dir(PLX_ROOT . $folder)) {
		plxMsg::Error(sprintf(L_MISSING_FOLDER, $folder));
		header('Location: medias.php');
		exit;
	}
	unset($folder);
	$_SESSION['folder'] = $path;
} elseif(!isset($_SESSION['folder'])) {
	$_SESSION['folder'] = '';
	$path = '';
}

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminMediasPrepend'));

# Recherche du type de medias à afficher via la session
if (empty($_SESSION['medias'])) {
    $_SESSION['medias'] = $plxAdmin->aConf['medias'];
}

# Nouvel objet de type plxMedias
$plxMediasRoot = PLX_ROOT . $_SESSION['medias'];
if ($plxAdmin->aConf['userfolders'] and $_SESSION['profil'] == PROFIL_WRITER) {
    $plxMediasRoot .= $_SESSION['user'] . '/';
}

$plxMedias = new plxMedias($plxMediasRoot, $_SESSION['folder']);

#----

if (isset($_POST['btn_newfolder']) and !empty($_POST['newfolder'])) {
    $newdir = plxUtils::title2filename(trim($_POST['newfolder']));
    if ($plxMedias->newDir($newdir)) {
        $path = $_SESSION['folder'] . $newdir . '/';
    }
    header(MEDIAS_REDIRECTION . $path);
    exit;
}

if (isset($_POST['btn_renamefile']) and !empty($_POST['newname'])) {
    $plxMedias->renameFile($_POST['oldname'], $_POST['newname']);
    header(MEDIAS_REDIRECTION . $path);
    exit;
}

if (isset($_POST['btn_delete']) and !empty($path)) {
    if ($plxMedias->deleteDir($path)) {
		$path = preg_replace('@/[^/]+/?$@', '', $path);
    }
    header(MEDIAS_REDIRECTION . $path);
    exit;
}

# if (isset($_POST['btn_upload'])) {
if (isset($_POST['resize']) and isset($_POST['thumb']) and !empty($_FILES)) {
    $plxMedias->uploadFiles($_FILES, $_POST);
    $_SESSION['resize'] = $_POST['resize'];
    $_SESSION['thumb'] = $_POST['thumb'];
    header(MEDIAS_REDIRECTION . $path);
    exit;
}

if (isset($_POST['btn_ok']) and isset($_POST['selection']) and !empty($_POST['idFile'])) {
	switch($_POST['selection']) {
		case 'move':
			if(isset($_POST['folder']) and !empty($_POST['folder'])) {
				$plxMedias->moveFiles($_POST['idFile'], $_SESSION['folder'], $_POST['folder']);
				$path = $_POST['folder'];
			}
			break;
		case 'thumbs':
		    $plxMedias->makeThumbs($_POST['idFile'], $plxAdmin->aConf['miniatures_l'], $plxAdmin->aConf['miniatures_h']);
		    break;
		case 'delete':
		    $plxMedias->deleteFiles($_POST['idFile']);
		    break;
		default:
			# nothing
	}

    header(MEDIAS_REDIRECTION . $path);
    exit;
}

# -------- On affiche les médias ----------

# Tri de l'affichage des fichiers
if (isset($_POST['sort']) and !empty($_POST['sort'])) {
    $sort = $_POST['sort'];
} else {
    $sort = isset($_SESSION['sort_medias']) ? $_SESSION['sort_medias'] : 'date_desc';
}

$sort_title = 'title_desc';
$sort_date = 'date_desc';
switch ($sort) {
    case 'title_asc':
        $sort_title = 'title_desc';
        usort($plxMedias->aFiles, function ($b, $a) {
            return strcmp($a["name"], $b["name"]);
        });
        break;
    case 'title_desc':
        $sort_title = 'title_asc';
        usort($plxMedias->aFiles, function ($a, $b) {
            return strcmp($a["name"], $b["name"]);
        });
        break;
    case 'date_asc':
        $sort_date = 'date_desc';
        usort($plxMedias->aFiles, function ($b, $a) {
            return strcmp($a["date"], $b["date"]);
        });
        break;
    case 'date_desc':
        $sort_date = 'date_asc';
        usort($plxMedias->aFiles, function ($b, $a) {
            return strcmp($a["date"], $b["date"]);
        });
        break;
}
$_SESSION['sort_medias'] = $sort;

# On inclut le header
include 'top.php';

$curFolder = '/' . plxUtils::strCheck(basename($_SESSION['medias']) . '/' . ltrim($_SESSION['folder'], '/'));
?>

<div class="adminheader">
	<div>
	    <h2 class="h3-like"><?= L_MEDIAS_TITLE ?></h2>
	    <div>
		    <span><?= L_MEDIAS_DIRECTORY ?> : </span>
		    <ul id="medias-breadcrumb">
<?php
$curFolders = explode('/', trim($curFolder, '/'));
if($curFolders) {
	$currentPath = '';
	foreach($curFolders as $id => $folder) {
		if($id > 0) {
			$currentPath .= $folder . '/';
		}
?>
				<li><span data-path="<?= ($id > 0) ? $currentPath : '/' ?>"><?= ($id > 0) ? $folder : L_PLXMEDIAS_ROOT ?></span></li>
<?php
	}
}
?>
		    </ul>
		    <span class="ico" data-copy="<?= $_SESSION['medias'] . $_SESSION['folder'] ?>" title="<?= L_MEDIAS_LINK_COPYCLP ?>" data-notice="<?= L_MEDIAS_LINK_COPYCLP_DONE ?>">&#128203;</span>
		</div>
    </div>
</div>

<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminMediasTop'))

/* ----------- Rename File Dialog ----------- */

?>
<input type="checkbox" id="toggle-renamefile" class="toggle" />
<div id="dlgRenameFile" class="dialog">
	<form method="post">
		<?= plxToken::getTokenPostMethod() ?>
        <input id="id_oldname" type="hidden" name="oldname"/>
        <div class="dialog-content">
            <label>
				<span><?= L_MEDIAS_NEW_NAME ?></span>
				<input type="text" name="newname" value="" maxlength="50" id="id_newname" required />
			</label>
            <input type="submit" name="btn_renamefile" value="<?= L_MEDIAS_RENAME ?>" />
            <label for="toggle-renamefile" class="dialog-close icon-cancel-circled"></label>
        </div>
	</form>
</div>
<div class="admin">
	<input type="checkbox" id="toggle-medias" class="toggle" />
    <form method="post" id="form_medias" data-chk="idFile[]">
        <?= plxToken::getTokenPostMethod() ?>
		<input type="hidden" name="sort" value="<?= $sort ?>" />
<?php

/* ----------- New Folder Dialog --------- */

?>
        <input type="checkbox" id="toggle-newfolder" class="toggle" onchange="if(this.checked) { this.form.elements.newfolder.focus(); }" />
        <div id="dlgNewFolder" class="dialog">
            <div class="dialog-content">
                <?= L_MEDIAS_NEW_FOLDER ?>&nbsp;:&nbsp;
                <input id="id_newfolder" type="text" name="newfolder" value="" maxlength="50" size="15" />
                <input type="submit" name="btn_newfolder" value="<?= L_MEDIAS_CREATE_FOLDER ?>"/>
                <label for="toggle-newfolder" class="dialog-close icon-cancel-circled"></label>
            </div>
        </div>
        <div class="treeview">
			<p><label for="toggle-newfolder" class="btn--primary"><?= L_MEDIAS_NEW_FOLDER ?></label></p>
			<ul>
				<li class="<?= $_SESSION['folder'] == '/' ? 'active' : 'is-path' ?> has-children">
					<a href="?path=/"><?= L_PLXMEDIAS_ROOT ?></a>
<?= $plxMedias->displayTreeView(); ?>
				</li>
			</ul>
        </div>
<?php

/* ------------------ Liste des médias -------- */

?>
		<div>
			<div class="tableheader">
				<label for="toggle-medias" class="button btn--primary"><i class="icon-plus"></i><?= L_MEDIAS_ADD_FILE ?></label>
<?php
if($plxMedias->aFiles) {
	$dataSelect = array('selection', 'move', 'folder');
?>
				<div>
					<select name="<?= $dataSelect[0] ?>" id="id_selection">
<?php
foreach(array(
	''				=> array(L_FOR_SELECTION),
	$dataSelect[1]	=> array(L_PLXMEDIAS_MOVE_FOLDER, L_CONFIRM_MOVE_MEDIAS),
	'thumbs'		=> array(L_MEDIAS_RECREATE_THUMB, L_CONFIRM_THUMBNAIL),
	'-'				=> array('-----'),
	'delete'		=> array(L_DELETE_FILE, L_CONFIRM_DELETE),
) as $value=>$infos) {
	$disabled = ($value == '-') ? 'disabled' : '';
	$dataLang = !empty($infos[1]) ? 'data-lang="' . $infos[1] . '"' : '';
?>
						<option value="<?= $value ?>" <?= $disabled ?> <?= $dataLang ?>><?= $infos[0] ?></option>
<?php
}
?>
					</select>
					<input type="hidden" name="<?= $dataSelect[2] ?>" value="<?= $path ?>" />
					<button name="btn_ok" data-select="<?= implode('|', $dataSelect) ?>" data-alert="<?= L_REQUIRED_OPTION . '|' . L_REQUIRED_TARGET ?>" disabled><?= L_OK ?></button>
				</div>
				<input type="text" id="medias-search" onkeyup="plugFilter()" placeholder="<?= L_SEARCH ?>..."
					   title="<?= L_SEARCH ?>" />
<?php
}

if (!empty($_SESSION['folder'])) {
?>
					<input type="submit" name="btn_delete"
						   class="red"
						   value="<?= L_DELETE_FOLDER ?>"
						   onclick="return confirm('<?php printf(L_MEDIAS_DELETE_FOLDER_CONFIRM, $curFolder) ?>')"/>
<?php
}
?>
			</div>
			<div class="scrollable-table">
                <table id="medias-table" class="table sort">
                    <thead>
	                    <tr>
	                        <th><?php if($plxMedias->aFiles) { ?><input type="checkbox" /><?php } else { ?>&nbsp;<?php } ?></th>
	                        <th class="icon">&nbsp;</th>
	                        <th class="sort" data-sortname="title"><?= L_MEDIAS_FILENAME ?></th>
	                        <th class="sort" data-sortname="ext"><?= L_MEDIAS_EXTENSION ?></th>
	                        <th class="sort integer" data-sortname="bytes"><?= L_MEDIAS_FILESIZE ?></th>
	                        <th class="sort integer" data-sortname="size"><?= L_MEDIAS_DIMENSIONS ?></th>
	                        <th class="sort" data-sortname="date"><?= L_DATE ?></th>
	                    </tr>
                    </thead>
                    <tbody id="medias-table-tbody">
<?php
# Si on a des fichiers
if ($plxMedias->aFiles) {
	foreach ($plxMedias->aFiles as $v) { # Pour chaque fichier
		$isImage = preg_match(plxMedias::IMG_EXTS, $v['extension']);
		$title = pathinfo($v['name'], PATHINFO_FILENAME);
?>
						<tr>
							<td><input type="checkbox" name="idFile[]" value="<?= $v['name'] ?>"/></td>
							<td>
<?php
		if (is_file($v['path']) and $isImage):
			$attrs = 'width="' . plxUtils::THUMB_WIDTH . '" height="' . plxUtils::THUMB_HEIGHT . '"';
?>
									<a class="overlay" title="<?= $title ?>" href="<?= $v['path'] ?>"><img
												src="<?= $v['.thumb'] ?>" <?= $attrs ?> alt="<?= $title ?>"
												class="thumb"/></a>
<?php
		else: $attrs = getimagesize($v['.thumb']);
?>
									<img src="<?= $v['.thumb'] ?>" <?= !empty($attrs) ? $attrs[3] : '' ?>
										 alt="<?= substr($v['extension'], 1) ?> " class="thumb"/>
<?php
		endif;
?>
							</td>
							<td data-sort="<?= $title . $v['extension'] ?>">
								<div>
									<a href="<?= $v['path'] ?>" class="imglink" title="<?= $title ?>" target="_blank"><?= $v['name'] ?></a>
									<span data-copy="<?= str_replace(PLX_ROOT, '', $v['path']) ?>" title="<?= L_MEDIAS_LINK_COPYCLP ?>" class="ico" data-notice="<?= L_MEDIAS_LINK_COPYCLP_DONE ?>">&#128203;</span>
									<span data-rename="<?= $v['path'] ?>" title="<?= L_RENAME_FILE ?>" class="ico">&#9998;</span>
								</div>
<?php
		$href = plxUtils::thumbName($v['path']);
		if ($isImage and is_file($href)) {
?>
								<div>
									<?= L_MEDIAS_THUMB ?> : <a target="_blank" title="<?= $title ?>" href="<?= $href ?>"><?= plxUtils::strCheck(basename($href)) ?></a>
									<span data-copy="<?= str_replace(PLX_ROOT, '', $href) ?>" title="<?= L_MEDIAS_LINK_COPYCLP ?>" class="ico" data-notice="<?= L_MEDIAS_LINK_COPYCLP_DONE ?>">&#128203;</span>
								</div>
<?php
		}
?>
							</td>
							<td data-sort="<?= strtolower($v['extension']) ?>"><?= strtolower($v['extension']) ?></td>
							<td data-sort="<?= $v['filesize'] ?>">
								<?= plxUtils::formatFilesize($v['filesize']); ?><br/>
<?php
		if ($isImage and is_file($href)) {
			echo plxUtils::formatFilesize($v['thumb']['filesize']);
		}
?>
							</td>
<?php
		$dimensions = '&nbsp;';
		if ($isImage and (isset($v['infos']) and isset($v['infos'][0]) and isset($v['infos'][1]))) {
			$dimensions = $v['infos'][0] . ' x ' . $v['infos'][1];
		}
		if ($isImage and is_file($href)) {
			$dimensions .= '<br />' . $v['thumb']['infos'][0] . ' x ' . $v['thumb']['infos'][1];
		}
?>
							<td data-sort="<?= !empty($v['infos']) ? $v['infos'][0] * $v['infos'][1] : '' ?>"><?= $dimensions ?></td>
							<td data-sort="<?= $v['date'] ?>"><?= plxDate::formatDate(plxDate::timestamp2Date($v['date'])) ?></td>
						</tr>
<?php
	}
} else {
?>
                        <tr>
                            <td colspan="7" class="txtcenter"><?= L_MEDIAS_NO_FILE ?></td>
                        </tr>
<?php
}
?>
                    </tbody>
                </table>
			</div>
		</div>
    </form>
<?php

/* ------------- Téléchargement des médias ------------ */

?>
    <form method="post" id="form_uploader" class="form_uploader" enctype="multipart/form-data">
        <?= plxToken::getTokenPostMethod() ?>
		<div class="toolbar">
			<label for="toggle-medias" class="button btn--primary"><i class="icon-left-big"></i><?= L_MEDIAS_BACK ?></label>
			<input type="file" name="selector[]" multiple accept="image/*, audio/*, application/pdf, application/zip" class="drag" />
			<button class="button--primary" name="btn_upload" id="btn_upload" disabled><?= L_MEDIAS_SUBMIT_FILE ?></button>
		</div>
		<div class="limits-upload">
			<p><?= L_MEDIAS_MAX_UPLOAD_NBFILE ?> : <?= ini_get('max_file_uploads') ?></p>
			<p><?= L_MEDIAS_MAX_UPLOAD_FILE ?>	: <?= $plxMedias->maxUpload['display'] ?></p>
<?php
if ($plxMedias->maxPost['value'] > 0) {
?>
			<p><?= L_MEDIAS_MAX_POST_SIZE ?> :  <?= $plxMedias->maxPost['display']; ?></p>
<?php
}

$limits = implode(';', array(
	ini_get('max_file_uploads'),
	$plxMedias->maxPost['value'],
	$plxMedias->maxUpload['value'],
));
?>
		</div>
		<div>
			<ul class="files_list unstyled awaiting drag" id="files_list" data-limits="<?= $limits ?>"><div><?= L_DEPOSIT_FILES ?></div></ul>
			<p><span id="batch-count">0</span> <?= L_FILES ?> - <?= L_BATCH_SIZE ?> : <span id="batch-size">0</span></p>
		</div>
		<div class="img-sizes">
<?php
	$sizes = array(
		'resize' => array(
			'title'		=> L_MEDIAS_RESIZE,
			'values'	=> array_merge(
				array(''=> L_MEDIAS_RESIZE_NO),
				IMG_RESIZE,
				array(
					intval($plxAdmin->aConf['images_l']) . 'x' . intval($plxAdmin->aConf['images_h']),
					'user',
				),
			),
		),
		'thumb' => array(
			'title'		=> L_MEDIAS_THUMBS,
			'values'	=> array_merge(
				array('' => L_MEDIAS_THUMBS_NONE),
				IMG_THUMB,
				array(
					intval($plxAdmin->aConf['miniatures_l']) . 'x' . intval($plxAdmin->aConf['miniatures_h']),
					'user',
				),
			),
		),
	);
	if(!isset($_SESSION['resize'])) { $_SESSION['resize'] = ''; }
	if(!isset($_SESSION['thumb']) or empty($plxAdmin->aConf['thumbs'])) { $_SESSION['thumb'] = ''; }

	foreach($sizes as $i=>$infos) {
		$before = count($infos['values']) - 3;
?>
			<ul class="unstyled">
				<li><?= $infos['title'] ?>&nbsp;:</li>
<?php
		foreach($infos['values'] as $k=>$caption) {
			$value = is_integer($k) ? $caption : $k;
			$selected = ($_SESSION[$i] == $value) ? ' checked' : '';
			if($value != 'user') {
?>
				<li>
					<label>
						<input type="radio" name="<?= $i ?>" value="<?= $value ?>"<?= $selected ?> />
						<span><?= $caption ?></span>
					</label>
<?php
				if($k === $before) {
?>
					( <a href="parametres_affichage.php"><?= L_MEDIAS_MODIFY ?></a> )

<?php
				}
?>
				</li>
<?php
			} else {
?>
				<li>
					<input type="radio" name="<?= $i ?>" value="user"<?= $selected ?> />
					<input type="number" name="<?= $i ?>_w" min="50" max="2590" />&nbsp;x&nbsp;
					<input type="number" name="<?= $i ?>_h" min="50" max="2590" />
				</li>
<?php
			}
		}
?>
			</ul>
<?php
	}
?>
		</div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminMediasUpload'))
?>
    </form>
</div>

<input id="modal" type="checkbox" tabindex="1" class="toggle">
<div class="modal">
    <div id="modal__overlay" class="modal__overlay">
        <div id="modal__box" class="modal__box">
            <img id="zoombox-img"/>
        </div>
    </div>
    <label for="modal" class="close">🗙</label>
</div>

<input id="clipboard" type="text" value="" style="display: none;"/>
<?php

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminMediasFoot'));

# On inclut le footer
include 'foot.php';

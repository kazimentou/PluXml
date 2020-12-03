<?php
/*
L_COMMENTS_LIST_DATE
L_COMMENTS_LIST_AUTHOR
L_COMMENT_EMAIL_FIELD
L_COMMENTS_LIST_ACTION
* */
/*
 * Pour vérifier si la clé L_KEY est utilisée, se placer dans le dossier "lang" et faire :
 * grep L_KEY ../{lib,admin}/*.php ../../*.php ../../update/*.php
 * */

const ROOT = '../lang/';

$langs = array_map(
	function($value) { return substr($value, -2); },
	glob(ROOT . '??', GLOB_ONLYDIR)
);

session_start();
if(isset($_POST['principale'])) {
	$_SESSION = $_POST;
}

define('DEFAULTS', array(
	'principale'	=> isset($_SESSION['principale']) ? $_SESSION['principale'] : 'fr',
	'secondaire'	=> isset($_SESSION['secondaire']) ? $_SESSION['secondaire'] : 'en',
	'fichier'		=> isset($_SESSION['fichier'])    ? $_SESSION['fichier']    : 'core',
));

// Tri préférentiel des langues
$df = array(DEFAULTS['principale'], DEFAULTS['secondaire']);
usort($langs, function($a, $b) use($df) {
	if($a == $b) { return 0; }
	if($a == $df[0]) { return -1; }
	if($a == $df[1]) { return ($b != $df[0]) ? -1 : 1; }
	return (in_array($b, $df)) ? 1 : strcmp($a, $b);
});

// liste des fichiers à analyser pour chaque langue
define('FILES', glob(ROOT . DEFAULTS['principale'] . '/*.php'));

// Génération du dictionnaire multi-langues
// Inclus les lignes de commentaires
$translations = array();
$firstLang = true;
foreach($langs as $lang) {
	$buffer = file(ROOT . $lang . '/' . DEFAULTS['fichier'] . '.php' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach($buffer as $i=>$line) {
		if(preg_match('@^const\s+(\w+)\s*=\s*[\'"](.*)[\'"]\s*;@', $line, $matches)) {
			if(!array_key_exists($matches[1], $translations)) {
				$translations[$matches[1]] = array('ligne' => $i);
			}
			$translations[$matches[1]][$lang] = trim(stripslashes($matches[2]));
		} elseif($firstLang && preg_match('@^(?:#|//)\s*(.*)@', $line, $matches)) {
			$translations['comment-' . $i] = array('ligne' => $i, 'comment' => $matches[1]);
		}
	}

	$firstLang = false;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" href="theme/images/favicon.png" />
	<title>Aide à la traduction pour le core de PluXml version 6.0.0 et plus</title>
	<style type="text/css">
		input[type="checkbox"] { margin: 0; padding: 0; vertical-align: middle; }
		tr { border-bottom: 1px solid #666; }
		td:not(:first-of-type) { border-left: 1px solid #666; }
		td:nth-of-type(2) { border:none; }
		tr.comment { background-color: wheat; }
		tr:hover { background-color: #666; color: #fff; }
		td { padding: 0.15rem; }
		td:first-of-type, td:nth-of-type(2) { padding: 0 0.2rem; }
		td:first-of-type { text-align: right; }
		header div { background-color: #eee; }
		header label { padding: 0 0.25rem; }
		#langs p { display: inline-block; margin: 0; padding: 0 0.25rem 0 0; }
		#langs p.active { background-color: gold; }
		#translations thead { background-color: #444; color: #fff; }
		#translations tbody input:not([type="hidden"]) { margin: 0; width: 100%; border: none; border-radius: 0; }
		#translations input[value=""] { background-color: orange; color: #fff; }
		#translations thead th.active { min-width: 25rem; }
	</style>
</head><body style="margin: 0; padding: 0; background-color: #aaa;">
	<!-- Formulaire de sélection -->
	<header style="position: sticky; top: 0; background-color: inherit;">
		<form method="post" style="display:flex; padding-right: 10rem; gap: 1rem; padding: 0.3rem 1rem; ">
<?php
foreach(array('principale', 'secondaire') as $caption) {
?>
			<div>
				<label for="id_<?= $caption ?>">Langue <?= $caption ?></label>
				<select id="id_<?= $caption ?>" name="<?= $caption ?>">
					<option value="">--</option>
<?php
	foreach($langs as $lang) {
		$selected = ($lang == DEFAULTS[$caption]) ? 'selected' : '';
?>
					<option value="<?= $lang ?>" <?= $selected ?>><?= $lang ?></option>
<?php
	}
?>
				</select>
			</div>
<?php
}
?>
			<div>
				<label for="">Fichier</label>
				<select name="fichier">
					<option value="">--</option>
<?php
	foreach(FILES as $file) {
		$f = basename($file, '.php');
		$selected = ($f == DEFAULTS['fichier']) ? 'selected' : '';
?>
					<option value="<?= $f ?>" <?= $selected ?>><?= $f ?></option>
<?php
	}
?>
				</select>
			</div>
			<input type="submit" value="Sélectionner" />
		</form>
	</header>

	<aside>
		<pre style="margin: 0.25rem auto; width: 48rem; background-color: #fff; padding: 0.25rem 1rem;"><code>Pour vérifier si la clé L_KEY est utilisée, se placer à la racine du site et faire :
grep L_KEY *.php update/*.php core/{lib,admin}/*.php
Rechercher toutes les clés utilisées : grep -E '\bL_\w+' *.php core/{admin,lib}/*.php | sed -E 's/^.*(\bL_\w+).*$/\1/' | sort | uniq</code></pre>
	</aside>
	<!-- formulaire pour les traductions -->
	<section id="main">
			<form method="post" action="/variables.php">
				<div id="langs" style="position: fixed; top: 0.3rem; right: 1rem; z-index=10;">
					<input type="hidden" name="cible" value="<?= DEFAULTS['fichier'] ?>" />
<?php
	foreach($langs as $i=>$lang) {
?>
					<p><input id="lang_<?= $lang ?>" type="checkbox" name="langs[]" value="<?= $lang ?>"><label for="lang_<?= $lang ?>"><?= $lang ?></label></p>
<?php
	}
?>
					<input type="submit" value="Enregistrer la langue choisie" />
				</div>
				<table id="translations" style="width: calc(100vw - 1.5rem); margin: 0 auto; border-collapse: collapse; white-space: nowrap; background-color: #fff;">
					<thead>
						<tr id="ruler">
<?php
	ob_start();
?>
							<th>N°</th>
							<th>Clé</th>
<?php
	foreach($langs as $i=>$lang) {
?>
							<th><?= $lang ?></th>
<?php
	}
	$header = ob_get_clean();
?>
<?= $header ?>
						</tr>
					</thead>
					<tbody id="translations-body" >
<?php
	foreach($translations as $key=>$values) {
		$i = $values['ligne'];
		// Génération du tableau. Une ligne par entrée du dictionnaire (key), une cellule par langue.
?>
					<tr <?= array_key_exists('comment', $values) ? 'class="comment"' : '' ?>>
						<td><?= $i ?></td>
<?php
		if(array_key_exists('comment', $values)) {
			// ligne de commentaire
?>
						<td colspan="<?= count($langs) + 2 ?>"># <?= $values['comment'] ?><input type="hidden" name="comments[<?= $i ?>]" value="<?= $values['comment'] ?>" /></td>
<?php

		} else {
			// Traduction du mot-clé dans chaque langue
?>
						<td><input type="hidden" name="key[<?= $i ?>]" value="<?= $key ?>" /><?= $key ?></td>
<?php
			foreach($langs as $lang) {
				// traduction pour une langue
				$cell = array_key_exists($lang, $values) ? html_entity_decode($values[$lang]) : '';
?>
						<td><input name="<?= $lang ?>[<?= $i ?>]" value="<?= htmlspecialchars($cell, ENT_COMPAT) ?>" /></td>
<?php
			}
		}
?>
					</tr>
<?php
	}
?>
					</tbody>
					<thead>
						<tr>
<?= $header ?>
						</tr>
					</thead>
				</table>
			</form>
	</section>
	<script>
		(function() {
			'use strict';
			document.getElementById('translations-body').addEventListener('focusin', function(event) {
				if(event.target.tagName == 'INPUT') {
					event.preventDefault();
					const index = event.target.parentElement.cellIndex;
					const ruler = document.getElementById('ruler');
					for(var i=0, iMax=ruler.cells.length; i<iMax; i++) {
						if(i == index) {
							ruler.cells[i].classList.add('active');
						} else {
							ruler.cells[i].classList.remove('active');
						}
					}
					const langs = document.querySelectorAll('#langs input[type="checkbox"]');
					for(i=0, iMax=langs.length; i<iMax; i++) {
						const parent = langs[i].parentElement;
						if(i == index - 2) {
							parent.classList.add('active');
						} else {
							parent.classList.remove('active');
						}
					}
				}
			});

			document.forms[1].addEventListener('submit', function(event) {
				const langNodes = event.target.elements['langs[]'];
				var checked = false;
				const noSubmits = [];
				for(var i=0, iMax=langNodes.length; i<iMax; i++) {
					if(langNodes[i].checked) {
						checked = true;
					} else {
						noSubmits.push(langNodes[i].value);
					}
				}

				if(!checked) {
					alert('Aucne langue sélectionée');
					event.preventDefault();
					return false;
				} else {
					noSubmits.forEach(function(lang) {
						const els = document.querySelectorAll('#translations-body input[name^="' + lang + '["]');
						for(var j=0, jMax=els.length; j<jMax; j++) {
							els[j].disabled = true;
						}
					});
				}
			});
		})();
	</script>
</body></html>

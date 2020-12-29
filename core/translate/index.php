<?php
/*
 * Pour vérifier si la clé L_KEY est utilisée, se placer dans le dossier "lang" et faire :
 * grep L_KEY ../{lib,admin}/*.php ../../*.php ../../update/*.php
 * */

const ROOT = '../lang/';
const GOOGLE_TRANSLATOR_URL = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=#SL#&tl=#TL#&dt=t&q=#Q#';
const MYMEMORY_TRANSLATOR_URL = 'https://api.mymemory.translated.net/get?q=#Q#&langpair=#SL#|#TL#';


$langs = array_map(
	function($value) { return substr($value, -2); },
	glob(ROOT . '??', GLOB_ONLYDIR)
);

function saveNewTranslation($translations, $lang, $file) {
	ob_start();
?>
/* New translation on <?= date('Y-m-d H:i') ?> */

<?php
	foreach($translations as $token=>$infos) {
		if(isset($infos['comment'])) {
			echo PHP_EOL . $infos['comment'] . PHP_EOL;
		} elseif(isset($infos[$lang])) {
?>
const <?= $token ?> = '<?= addslashes(html_entity_decode($infos[$lang])) ?>';
<?php
		}
	}

	file_put_contents(ROOT . $lang . '/' . $file . '.php', '<?php' . PHP_EOL . ob_get_clean() . PHP_EOL);
}

session_start();

# Sauvegarde des traductions pour les langues sélectionnés
if(isset($_SESSION['principale']) and isset($_POST['saveBtn'])) {
	foreach($_POST['langs'] as $lang) {
		ob_start();
		foreach($_POST['key'] as $i=>$token) {
			if(substr($token, 0, 1) == '#') {
				# commentaire
				echo PHP_EOL . $token . PHP_EOL;
			} else {
				if(
					isset($_POST[$lang][$i]) and
					!empty(trim($_POST[$lang][$i])) and
					(substr($token, 0, 1) == '@' or !isset($_POST['cleanup']))
				) {
?>
const <?= substr($token, 1) ?> = '<?= addslashes($_POST[$lang][$i]) ?>';
<?php
				}
			}

		}
		$filename = ROOT . $lang . '/' . $_POST['cible'] . '.php';
		file_put_contents($filename, '<?php' . PHP_EOL . ob_get_clean() . PHP_EOL);
	}
} elseif(isset($_SESSION['principale']) and isset($_POST['newBtn']) and !empty($_POST['new'])) {
	# nouvelle langue
	$targetLang = $_POST['new'];
	$folder = ROOT . $targetLang;
	if(is_writable(ROOT)) {
		$files = array_map(
			function($item) {
				return basename($item, '.php');
			},
			glob(ROOT . $_SESSION['principale'] . '/*.php')
		);
		foreach($files as $f) {
			$buffer = file(ROOT . $_SESSION['principale'] . '/' . $f . '.php' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$translations = array();
			$id = 0;
			foreach($buffer as $i=>$line) {
				if(preg_match('@^const\s+(\w+)\s*=\s*[\'"](.*)[\'"]\s*;@', $line, $matches)) {
					$translations[trim($matches[1])] = array(
						'src'	=> trim(stripslashes($matches[2])),
					);
					$id++;
				} elseif(preg_match('@^(?:#|//)\s*(.*)@', $line, $matches)) {
					# ligne de commentaire dans le fichier de langue. On n'en tient compte que pour la première langue
					$translations[sprintf('comment-%02d', $id)] = array(
						'comment'	=> '# ' . $matches[1]
					);
					$id++;
				}
			}

			$req = array_filter($translations, function($item) {
				return isset($item['src']);
			});
			$filename = ROOT . 'notes.txt';
			file_put_contents($filename, implode(PHP_EOL, array_values(array_map(
				function($item) {
					return $item['src'];
				},
				$req
			))));

			$cfile = curl_file_create(realpath($filename), 'text/plain', 'notes.txt');
			$ch = curl_init('https://translate.googleusercontent.com/translate_f');
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER	=> true,
				CURLOPT_USERAGENT		=> $_SERVER["HTTP_USER_AGENT"],
				CURLOPT_SSL_VERIFYPEER	=> false,
				CURLOPT_FOLLOWLOCATION	=> true,
				CURLOPT_POST			=> true,
				CURLOPT_POSTFIELDS		=> array(
					'hl'	=> $_SESSION['principale'],
					'ie'	=> 'UTF-8',
					'js'	=> 'y',
					'prev'	=> '_t',
					'tl'	=> $targetLang,
					'sl'	=> $_SESSION['principale'],
					'file'	=> $cfile,
				),
			));
			$content = curl_exec($ch);
			$err = curl_errno($ch);
			if($err != 0) {
				$errorMsg = curl_error($ch);
			}
			curl_close($ch);
			unlink($filename);

			if($err == 0 and $content !== false) {
				$resp = explode(PHP_EOL, preg_replace('@</pre>$@', '', preg_replace('@^<pre>@', '', $content)));
				$i = 0;
				foreach(array_keys($req) as $token) {
					$translations[$token][$targetLang] = $resp[$i];
					$i++;
				}

				if(is_dir($folder) or @mkdir($folder)) {
					saveNewTranslation($translations, $targetLang, $f);
				}
			}
		}

		# On recrée la liste des langues
		$langs = array_map(
			function($value) { return substr($value, -2); },
			glob(ROOT . '??', GLOB_ONLYDIR)
		);

	}
} else {
	# On initialise la sélection du fichier, et des langues principales et secondaires
	foreach(array(
		'principale'	=> 'fr',
		'secondaire'	=> 'en',
		'fichier'		=> 'core',
	) as $k=>$default) {
		if(
			!empty($_POST[$k]) and
			($k == 'fichier' or in_array($_POST[$k], $langs))
		) {
			$_SESSION[$k] = $_POST[$k];
		} elseif(!isset($_SESSION[$k])) {
			$_SESSION[$k] = $default;
		}
	}
}


// Tri préférentiel des langues
$df = array($_SESSION['principale'], $_SESSION['secondaire']);
usort($langs, function($a, $b) use($df) {
	if($a == $b) { return 0; }
	if($a == $df[0]) { return -1; }
	if($a == $df[1]) { return ($b != $df[0]) ? -1 : 1; }
	return (in_array($b, $df)) ? 1 : strcmp($a, $b);
});

// liste des fichiers à analyser pour chaque langue
define('FILES', array_map(
	function($item) {
		return basename($item, '.php');
	},
	glob(ROOT . $_SESSION['principale'] . '/*.php'))
);
if(!in_array($_SESSION['fichier'], FILES)) {
	$_SESSION['fichier'] = FILES[0];
}

/*
 * Génération du dictionnaire multi-langues
 * Inclus les lignes de commentaires
 *
 * Pour les commentaires, la clé de traduction commence par '#',
 * les clés de traduction dans la langue principale commencent par '@'
 * les clés supplémentaires dans les autres langues commencent par ' '
 * */
$translations = array();
$firstLang = true;
$id = 0;
foreach($langs as $lang) {
	$filename = ROOT . $lang . '/' . $_SESSION['fichier'] . '.php';
	if(file_exists($filename)) {
		$buffer = file($filename , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach($buffer as $i=>$line) {
			if(preg_match('@^const\s+(\w+)\s*=\s*[\'"](.*)[\'"]\s*;@', $line, $matches)) {
				$token = trim($matches[1]);
				if(!array_key_exists($token, $translations)) {
					$translations[$token] = array(
						'line' => $id,
						'required'	=> $firstLang,
					);
					$id++;
				}
				$translations[$token][$lang] = trim(stripslashes($matches[2]));
			} elseif($firstLang && preg_match('@^(?:#|//)\s*(.*)@', $line, $matches)) {
				# ligne de commentaire dans le fichier de langue. On n'en tient compte que pour la première langue
				$translations['comment-' . $id] = array(
					'line'		=> $id,
					'comment'	=> $matches[1]
				);
				$id++;
			}
		}

		$firstLang = false;
	}
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="../admin/theme/images/favicon.png" />
	<title>Aide à la traduction pour le core de PluXml version 6.0.0 et plus</title>
	<link rel="stylesheet" type="text/css" href="translate.css" />
    <meta name="robots" content="noindex, nofollow" />
</head><body>
	<!-- Formulaire de sélection -->
	<header>
		<form method="post">
<?php
foreach(array('principale', 'secondaire') as $caption) {
?>
			<div>
				<label for="id_<?= $caption ?>">Langue <?= $caption ?></label>
				<select id="id_<?= $caption ?>" name="<?= $caption ?>">
					<option value="">--</option>
<?php
	foreach($langs as $lang) {
		$selected = ($lang == $_SESSION[$caption]) ? 'selected' : '';
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
	foreach(FILES as $f) {
		$selected = ($f == $_SESSION['fichier']) ? 'selected' : '';
?>
					<option value="<?= $f ?>" <?= $selected ?>><?= $f ?></option>
<?php
	}
?>
				</select>
			</div>
			<input type="submit" name="selectionBtn" value="Sélectionner" />
			<div>
				<select name="new" id="id_new" data-excludes="<?= implode('|', $langs) ?>">
					<option>Nouveau language</option>
				</select>
				<input type="submit" name="newBtn" value="ajouter">
			</div>
		</form>
	</header>

	<aside>
		<pre><code>Pour vérifier si la clé L_KEY est utilisée, se placer à la racine du site et faire :
grep L_KEY *.php update/*.php core/{lib,admin}/*.php
Rechercher toutes les clés utilisées : grep -E '\bL_\w+' *.php core/{admin,lib}/*.php | sed -E 's/^.*(\bL_\w+).*$/\1/' | sort | uniq</code></pre>
	</aside>

	<!-- formulaire pour les traductions -->
	<section id="main">
		<form method="post" name="translation_form">
				<table id="translations">
					<thead>
						<tr class="toolbar">
							<th colspan="<?= count($langs) + 2 ?>" id="langs">
								<input type="hidden" name="cible" value="<?= $_SESSION['fichier'] ?>" />
								<div>
									<fieldset>
<?php
	foreach($langs as $i=>$lang) {
?>
										<label>
											<input type="checkbox" name="langs[]" value="<?= $lang ?>" />
											<span><?= $lang ?></span>
										</label>
<?php
	}
?>
									</fieldset>
									<label>
										<input type="checkbox" name="cleanup" value="1" />
										<span>Nettoyer</span>
									</label>
									<input type="submit" name="saveBtn" value="Sauvegarder" />
									<div class="translator-motor">
										<input type="radio" name="translator" value="google" />
										<a href="https://translate.google.com" rel="noreferrer" target="_blank"><img src="google.svg" alt="Google" /></a>
										<input type="radio" name="translator" value="mymemory" />
										<a href="https://mymemory.translated.net" rel="noreferrer" "target="_blank"><img src="mymemory.svg" alt="MyMemory" /></a>
									</div>
								</div>
							</th>
						</tr>
						<tr class="ruler" id="ruler">
<?php
	ob_start();
?>
							<th>N°</th>
							<th>Clé</th>
<?php
	$colspan = (count($langs) + 2);
	foreach($langs as $i=>$lang) {
?>
							<th><?= $lang ?></th>
<?php
	}
	$header = ob_get_clean();
	# On répéte $header en pied de table
?>
<?= $header ?>
						</tr>
					</thead>
					<tbody id="translations-body" data-lang="Sélectionnez un moteur de traduction" data-google="<?= GOOGLE_TRANSLATOR_URL ?>" data-mymemory="<?= MYMEMORY_TRANSLATOR_URL ?>">
<?php
	foreach($translations as $key=>$values) {
		$i = $values['line'];
		// Génération du tableau. Une ligne par entrée du dictionnaire (key), une cellule par langue.
		$isComment = array_key_exists('comment', $values);
?>
					<tr <?= $isComment ? 'class="comment"' : '' ?>>
						<td><?= $i ?></td>
<?php
		if($isComment) {
			// ligne de commentaire
?>
						<td colspan="<?= $colspan ?>"># <?= $values['comment'] ?><input type="hidden" name="key[<?= $i ?>]" value="# <?= $values['comment'] ?>" /></td>
<?php

		} else {
			// Traduction du mot-clé dans chaque langue
			$prefix = $values['required'] ? '@' : ' ';
?>
						<th><input type="hidden" name="key[<?= $i ?>]" value="<?= $prefix . $key ?>" /><span><?= $key ?></span></th>
<?php
			foreach($langs as $lang) {
				// traduction pour une langue
				$value = array_key_exists($lang, $values) ? trim($values[$lang]) : '';
				$cell =  !empty($value) ? htmlspecialchars(html_entity_decode($value), ENT_COMPAT) : '';
				if($values['required']) {
					$className = empty($value) ? 'class="missing"' : '';
?>
						<td <?= $className ?>><input name="<?= $lang ?>[<?= $i ?>]" value="<?= $cell ?>" /></td>
<?php
				} elseif(!empty($cell)) {
?>
						<td><input name="<?= $lang ?>[<?= $i ?>]" value="<?= $cell ?>" data-extra /></td>
<?php
				} else {
?>
						<td class="no-request">&nbsp;</td>
<?php
				}
			}
		}
?>
					</tr>
<?php
	}
?>
					</tbody>
					<thead>
						<tr class="tfoot">
<?= $header ?>
						</tr>
					</thead>
				</table>
		</form>
	</section>
	<script src="translate.js"></script>
</body></html>

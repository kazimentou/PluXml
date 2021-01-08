<?php
/*
 * Pour vérifier si la clé L_KEY est utilisée, se placer dans le dossier "lang" et faire :
 * grep L_KEY ../{lib,admin}/*.php ../../*.php ../../update/*.php
 *
 * https://www.loc.gov/standards/iso639-2/ISO-639-2_utf-8.txt
 * */

const PLX_ROOT = '../../';
include PLX_ROOT . 'core/lib/config.php';

const PLX_LANGS = PLX_CORE .'lang/';
const GOOGLE_TRANSLATOR_URL = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=#SL#&tl=#TL#&dt=t&q=#Q#';
const MYMEMORY_TRANSLATOR_URL = 'https://api.mymemory.translated.net/get?q=#Q#&langpair=#SL#|#TL#';

const PATTERN = '@^(?:const\s+(\w+)\s*=\s*[\'"](.*)[\'"]\s*;|(?:#|//)\s*(.*))@';

$langs = plxUtils::getLangs();
$success = '';
$errorMsg = '';

const SOURCES = array(
	'core'		=> array(
		'include'	=> array('index', 'feed', 'core/lib/class.plx.*',),
		'exclude'	=> array('core/lib/class.plx.admin',),
	),
	'install'	=> 'install',
	'update'	=> 'update/*',
	'admin'		=> array(
		'include'	=> array('core/admin/*', 'core/lib/class.plx.admin',),
	),
);

function saveNewTranslation($translations, $lang, $file) {
	global $success, $errorMsg;

	ob_start();
?>
# New translation on <?= date('Y-m-d H:i') ?>

<?php
	foreach($translations as $token=>$infos) {
		if(isset($infos['comment'])) {
			echo PHP_EOL . $infos['comment'] . PHP_EOL . PHP_EOL;
		} elseif(isset($infos[$lang])) {
?>
const <?= $token ?> = '<?= str_replace('\"', '"', addslashes(html_entity_decode($infos[$lang]))) ?>';
<?php
		}
	}

	file_put_contents(PLX_LANGS . $lang . '/' . $file . '.php', '<?php' . PHP_EOL . ob_get_clean() . PHP_EOL);
}

function addNewTranslation($targetLang, $srcLang) {
	global $success, $errorMsg;

	$folder = PLX_LANGS . $targetLang;

	$files = array_map(
		function($item) {
			return basename($item, '.php');
		},
		glob(PLX_LANGS . $srcLang . '/*.php')
	);

	foreach($files as $f) {
		$buffer = file(PLX_LANGS . $srcLang . '/' . $f . '.php' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$translations = array();
		$id = 0;

		$filename = PLX_LANGS . $_SESSION['principale'] . '/' . $f . '.php';
		$buffer = file($filename , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach($buffer as $line) {
			if(preg_match(PATTERN, $line, $matches)) {
				if(!isset($matches[3])) {
					# traduction
					$token = $matches[1];
					if(!array_key_exists($token, $translations)) {
						$translations[$token] = array(
							'src'	=> trim(stripslashes($matches[2])),
						);
						$id++;
					} else {
						$translations[$token][$lang] = trim(stripslashes($matches[2]));
					}
				} else {
					# commentaire
					$translations[sprintf('comment-%02d', $id)] = array(
						'comment'	=> trim('// ' . $matches[3]),
					);
					$id++;
				}
			}
		}

		$req = array_filter($translations, function($item) {
			return isset($item['src']);
		});
		$filename = PLX_LANGS . 'translate-notes-' . $f . '.txt';
		file_put_contents($filename, implode(PHP_EOL, array_values(array_map(
			function($item) {
				return $item['src'];
			},
			$req
		))));

		$cfile = curl_file_create(realpath($filename), 'text/plain', plxUtils::charAleatoire() . '.txt');
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
				'sl'	=> $srcLang,
				'file'	=> $cfile,
			),
		));
		$content = curl_exec($ch);
		$err = curl_errno($ch);
		if($err != 0) {
			$errorMsg = curl_error($ch);
		}
		curl_close($ch);

		if($err == 0 and $content !== false) {
			$resp = explode(PHP_EOL, preg_replace('@</pre>$@', '', preg_replace('@^<pre>@', '', $content)));
			$i = 0;
			foreach(array_keys($req) as $token) {
				if($i >= count($resp) or count($req) < count($resp)) {
					# Google refuse la traduction sans envoyer de code d'erreur
					$errorMsg = array(
						'lang'		=> $targetLang,
						'src'		=> $f,
						'files'		=> array(
							'response'	=> PLX_LANGS . 'translate-google.html',
							'tokens'	=> PLX_LANGS . 'translate-tokens-' . $f . '.txt',
							'sentences'	=> $filename,
						),
					);
					# On sauvegarde les données utilisés pour la traduction
					file_put_contents($errorMsg['files']['response'], $content);
					file_put_contents($errorMsg['files']['tokens'], implode(PHP_EOL, array_keys($req)));
					return;
				}
				$translations[$token][$targetLang] = $resp[$i];
				$i++;
			}

			if(is_dir($folder) or @mkdir($folder)) {
				saveNewTranslation($translations, $targetLang, $f);
			}
		}

		unlink($filename);

		# Ne soyons pas plus rapide qu'un être humain avec Google
		sleep(2);
	}
}

function saveTranslation($lang, $keep=false) {
	global $success, $errorMsg, $langs;

	$filename = PLX_LANGS . $lang . '/' . $_POST['cible']  . '.php';
	if(!is_writable($filename) or !is_writable(PLX_LANGS . $lang)) {
		$errorMsg = 'Pas de permission en écriture pour la langue "' . $langs[$lang] . ' ' . $lang . '" sur le fichier' . ' :<br />' . realpath($filename);
		return;
	}

	ob_start();
	foreach($_POST['token'] as $i=>$token) {
		if(preg_match('@^(?://|#)@', $token)) {
			# commentaire
			echo PHP_EOL . $token . PHP_EOL . PHP_EOL;
		} elseif(isset($_POST[$lang][$i])) {
			$value = trim($_POST[$lang][$i]);
			if(
				!empty($value) and (
					substr($token, 0, 1) == '@' or
					$keep
				)
			) {
				# la traduction n'est pas nulle
				# le token est présent dans la langue principale si commençant par '@'. A conserver dans ce cas
?>
const <?= substr($token, 1) ?> = '<?= str_replace('\"', '"', addslashes($value)) ?>';
<?php
			}
		}
	}

	# On sauvegarde
	file_put_contents($filename, '<?php' . PHP_EOL . ob_get_clean() . PHP_EOL);
	$success = 'Traduction ' . $lang . ' fichier ' . $_POST['cible'] . '.php enregistrée' . PHP_EOL;
}

session_start();

# Sauvegarde des traductions pour les langues sélectionnés
if(isset($_SESSION['principale']) and isset($_POST['saveBtn'])) {
	if(isset($_POST['lang']) and isset($_POST['cible'])) {
		$lang = $_POST['lang'];
		if(array_key_exists($lang, $langs)) {
			saveTranslation($lang, !isset($_POST['cleanup']));
			header('Content-Type: text/plain; charset=utf-8');
			echo 'ok' . PHP_EOL;
		} else {
			header($_SERVER['SERVER_PROTOCOL'] . ' 404 ' . $lang . ' language not found');
		}
	} else {
		header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad request');
	}
	exit;
} elseif(isset($_SESSION['principale']) and isset($_POST['newBtn']) and !empty($_POST['new'])) {
	# nouvelle langue
	if(is_writable(PLX_LANGS)) {
		addNewTranslation($_POST['new'], $_SESSION['principale']);
		# On recrée la liste des langues
		$langs = plxUtils::getLangs();
	} else {
		$errorMsg = 'Pas de droit en écriture sur le dossier ' . PLX_LANGS;
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
			($k == 'fichier' or array_key_exists($_POST[$k], $langs))
		) {
			$_SESSION[$k] = $_POST[$k];
		} elseif(!isset($_SESSION[$k])) {
			$_SESSION[$k] = $default;
		}
	}
}

if(!is_writable(PLX_LANGS)) {
	$errorMsg = 'Pas de droits en écriture pour le dossier' . ' :<br />' . realpath(PLX_LANGS);
	$noGrants = true;
} else {
	foreach(array_keys($langs) as $lang) {
		if(!is_writable(PLX_LANGS . $lang)) {
			$errorMsg = 'Pas de droits en écriture pour le dossier' . ' :<br />' . realpath(PLX_LANGS . $lang . '/');
			$noGrants = true;
			break;
		}
	}
}

// Tri préférentiel des langues
$df = array($_SESSION['principale'], $_SESSION['secondaire']);
uksort($langs, function($a, $b) use($df) {
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
	glob(PLX_LANGS . $_SESSION['principale'] . '/*.php'))
);
if(!in_array($_SESSION['fichier'], FILES)) {
	$_SESSION['fichier'] = FILES[0];
}

/*
 * Génération du dictionnaire multi-langues
 * Inclus les lignes de commentaires
 *
 * */
$translations = array();
$firstLang = true;
$id = 0;
foreach(array_keys($langs) as $lang) {
	# $_SESSION['fichier'] : nom du fichier à analyser pour chaque langue
	$filename = PLX_LANGS . $lang . '/' . $_SESSION['fichier'] . '.php';
	if(file_exists($filename)) {
		$buffer = file($filename , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach($buffer as $line) {
			if(preg_match(PATTERN, $line, $matches)) {
				if(!isset($matches[3])) {
					# traduction
					$token = $matches[1];
					if(!array_key_exists($token, $translations)) {
						$translations[$token] = array(
							'line'		=> $id,
							'required'	=> $firstLang,
							$lang		=> trim(stripslashes($matches[2])),
						);
						$id++;
					} else {
						$translations[$token][$lang] = trim(stripslashes($matches[2]));
					}
				} elseif($firstLang) {
					# commentaire
					$translations[sprintf('comment-%02d', $id)] = array(
						'line'		=> $id,
						'comment'	=> trim('// ' . $matches[3]),
					);
					$id++;
				}
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
<?php
if(!empty($errorMsg) and is_array($errorMsg)) {
	$captions = array(
		'response'	=> 'Traduction reçue',
		'tokens'	=> 'Clés de traduction (tokens)',
		'sentences'	=> 'Phrases à traduire',
	);
?>
	<h1>Erreur de traduction</h1>
	<p>Langue cible : <?= $errorMsg['lang'] ?></p>
	<p>Source  : <a href="<?= PLX_LANGS . $_SESSION['principale'] . '/' . $errorMsg['src'] ?>.php" download><?= $_SESSION['principale'] . '/' . $errorMsg['src'] ?>.php</a></p>
<?php
	if(isset($errorMsg['files'])) {
?>
	<p>Fichiers :</p>
	<ul>
<?php
		foreach($captions as $k=>$caption) {
?>
		<li><a href="<?= $errorMsg['files'][$k] ?>" target="_blank"><?= $caption ?></a></li>
<?php
		}
?>
	</ul>
	<p><a href="index.php">Retour</a></p>
<?php
	}
} else {
?>
	<!-- Formulaire de sélection -->
	<header>
		<form method="post">
<?php
foreach(array('principale', 'secondaire') as $k) {
?>
			<div>
				<label for="id_<?= $k ?>">Langue <?= $k ?></label>
				<select id="id_<?= $k ?>" name="<?= $k ?>">
					<option value="">--</option>
<?php
	foreach($langs as $lang=>$caption) {
		$selected = ($lang == $_SESSION[$k]) ? 'selected' : '';
?>
					<option value="<?= $lang ?>" <?= $selected ?>><?= $caption ?></option>
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
				<select name="new" id="id_new" data-excludes="<?= implode('|', array_keys($langs)) ?>">
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
<?php
if(empty($noGrants)) {
	# On a des droits en écriture dans les dossiers
?>
						<tr class="toolbar">
							<th colspan="<?= count($langs) + 2 ?>" id="langs">
								<input type="hidden" name="cible" value="<?= $_SESSION['fichier'] ?>" />
								<div>
									<fieldset>
<?php
	foreach($langs as $lang=>$caption) {
		if(is_writable(PLX_LANGS . $lang . '/' . $_SESSION['fichier'] . '.php')) {
?>
										<label>
											<input type="checkbox" name="langs[]" value="<?= $lang ?>" />
											<span><?= $lang ?></span>
										</label>
<?php
		}
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
										<a href="https://mymemory.translated.net" rel="noreferrer" target="_blank"><img src="mymemory.svg" alt="MyMemory" /></a>
									</div>
								</div>
							</th>
<?php
}
?>
						</tr>
						<tr class="ruler" id="ruler">
<?php
	ob_start();
?>
							<th>N°</th>
							<th>Clé</th>
<?php
	$colspan = (count($langs) + 2);
	# https://flagpedia.net/emoji
	$lang2flag = array(
		'en'	=> 'GB',
		'oc'	=> 'FR',
	);
	foreach($langs as $lang=>$caption) {
		if(!is_writable(PLX_LANGS . $lang . '/' . $_SESSION['fichier'] . '.php')) {
			$className = 'class="unwritable"';
			if(empty($errorMsg)) {
				$errorMsg = 'Pas de droits en écriture sur le fichier "' . $_SESSION['fichier'] . '.php" pour les langues marquées en rouge';
			}
		} else {
			$className = '';
		}
?>
							<th <?= $className ?>><?= $caption ?></th>
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
						<td colspan="<?= $colspan ?>"><?= $values['comment'] ?><input type="hidden" name="token[<?= $i ?>]" value="<?= $values['comment'] ?>" /></td>
<?php

		} else {
			// Traduction du mot-clé dans chaque langue
			$prefix = $values['required'] ? '@' : ' ';
?>
						<th><input type="hidden" name="token[<?= $i ?>]" value="<?= $prefix . $key ?>" /><span><?= $key ?></span></th>
<?php
			foreach(array_keys($langs) as $lang) {
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
	<div class="spinner">
		<div class="bounce1"></div>
		<div class="bounce2"></div>
		<div class="bounce3"></div>
	</div>
<?php
	if(!empty($errorMsg) or !empty($success)) {
?>
	<div class="notification <?= !empty($errorMsg) ? 'error' : '' ?>">
		<div>
			<?= !empty($errorMsg) ? $errorMsg : $success ?>
		</div>
	</div>
<?php
	}
?>
	<script src="translate.js"></script>
<?php
}
?>
</body></html>

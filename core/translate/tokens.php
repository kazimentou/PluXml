<?php

const PLX_ROOT = '../../';
const ADMIN_CLASSES = array(
	PLX_ROOT . 'core/lib/class.plx.admin.php',
	PLX_ROOT . 'core/lib/class.plx.medias.php',
);
const UPDATER_CLASSES = array(
	PLX_ROOT . 'core/lib/class.plx.updater.php',
);

$patterns = array(
	'core'		=> 'core/lib/class.plx.*.php',
	'admin'		=> 'core/admin/*.php',
	'install'	=> 'install.php',
	'update'	=> 'update/*.php',
);

$tokensList = array(
);


foreach($patterns as $k=>$v) {
	echo str_repeat('=', 5) . ' ' . $k . ' ' . str_repeat('=', 15 - strlen($k)) . PHP_EOL;
	$filenames = glob(PLX_ROOT . $v);
	switch($k) {
		case 'core' :
			$filenames = array_diff($filenames, ADMIN_CLASSES, UPDATER_CLASSES);
			$filenames1 = array_filter(glob(PLX_ROOT . '*.php'), function($item) {
				return !in_array(basename($item, '.php'), array('install', 'config'));
			});
			$filenames = array_merge($filenames, $filenames1);
			break;
		case 'admin' :
			$filenames = array_merge($filenames, ADMIN_CLASSES);
			break;
		case 'update' :
			$filenames = array_merge($filenames, UPDATER_CLASSES);
			break;
	}
	$tokens = array();
	foreach($filenames as $f) {
		echo $f . PHP_EOL;
		if(preg_match_all('#\bL_\w+#', file_get_contents($f), $matches)) {
			$tokens = array_merge($tokens, $matches[0]);
		}
	}
	sort($tokens);
	$tokens = array_unique($tokens);
	if($k == 'core') {
		$tokensList[$k] = $tokens;
	} else {
		$tokensList[$k] = array_filter($tokens, function($item) use($tokensList) {
			 return !in_array($item, $tokensList['core']);
		});
	}
	echo count($tokensList[$k]) . ' tokens' . PHP_EOL;
}

$commonTokens = array_merge(
	array_intersect($tokensList['admin'], $tokensList['install']),
	array_intersect($tokensList['admin'], $tokensList['update']),
	array_intersect($tokensList['install'], $tokensList['update']),
);
$tokensList['common'] = $commonTokens; // for information
if(!empty($commonTokens)) {
	sort($commonTokens);
	// On vérifie qu'il n'y a pas d'éléments de $commonTokens déjà présents dans $tokensList['core']
	$commonTokens = array_diff(array_unique($commonTokens), $tokensList['core']);

	if(!empty($commonTokens)) {
		echo PHP_EOL . count($commonTokens) . ' common tokens :' . PHP_EOL;
		array_walk($commonTokens, function($item) {
			echo $item . PHP_EOL;
		});
		echo PHP_EOL;
		echo 'grep -E \'L_(?:' . implode('|', array_map(function($item) { return substr($item, 2); }, $commonTokens)) . ')\' core/lang/fr/*.php';
		echo str_repeat(PHP_EOL, 2);

		// Mise à jour de $tokensList['core'] avec $commonTokens;
		$tokensList['core'] = array_merge($tokensList['core'], $commonTokens);
		sort($tokensList['core']);
		echo count($tokensList['core']) . ' tokens for core' . PHP_EOL;

		foreach(array(
			'admin',
			'install',
			'update',
		) as $v) {
			$n = count($tokensList[$v]);
			$tokensList[$v] = array_diff($tokensList[$v], $commonTokens);
			echo count($tokensList[$v]) . ' tokens for ' . $v;
			if($n != count($tokensList[$v])) {
				echo ' instead of ' . $n;
			}
			echo PHP_EOL;
		}
	}
}

file_put_contents('tokens.json', json_encode($tokensList));

echo 'Done' . PHP_EOL;

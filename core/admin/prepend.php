<?php
const PLX_ROOT = '../../';
const PLX_CORE = PLX_ROOT . 'core/';

const SESSION_DOMAIN = __DIR__ ;
const SESSION_LIFETIME = 7200; // 2 hours

include '../lib/config.php';

/*
 * Close the session
 * */
function log_out($redirect='auth.php') {
	$_SESSION = array();
	# See https://www.php.net/manual/fr/function.session-destroy.php
	if (ini_get('session.use_cookies')) {
		# Delete cookie on client ( expired time )
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params['path'], $params['domain'],
			$params['secure'], $params['httponly']
		);
	}
	# Delete cookie on server
	session_destroy();

	# Redirection on authentification page
	header('Location: ' . $redirect);
	exit;
}

# On démarre la session
plx_session_start();

if(!defined('PLX_AUTHPAGE') OR PLX_AUTHPAGE !== true){ # si on est pas sur la page de login
	# Test sur le domaine et sur l'identification
	if(
		empty($_SESSION['domain']) or
		$_SESSION['domain'] != SESSION_DOMAIN or
		empty($_SESSION['ip']) or
		$_SESSION['ip'] != $_SERVER['REMOTE_ADDR'] or
		empty($_SESSION['user']) or
		!preg_match('#^\d{3}#', $_SESSION['user'])
	) {
		header('Location: auth.php?p=' . htmlentities($_SERVER['REQUEST_URI']));
		exit;
	}
}

# Echappement des caractères
if($_SERVER['REQUEST_METHOD'] == 'POST') $_POST = plxUtils::unSlash($_POST);

# Creation de l'objet principal et premier traitement
$plxAdmin = plxAdmin::getInstance();
$lang = $plxAdmin->aConf['default_lang'];

if(isset($_SESSION['user'])) {
	# Si utilisateur désactivé ou supprimé par un admin, hors page de login. (!PLX_AUTHPAGE)
	if(
		!array_key_exists($_SESSION['user'], $plxAdmin->aUsers) or
		empty($plxAdmin->aUsers[$_SESSION['user']]['active']) or
		!empty($plxAdmin->aUsers[$_SESSION['user']]['delete'])
	) {
		log_out();
	} else {
		$lang = $plxAdmin->aUsers[$_SESSION['user']]['lang'];
		$_SESSION['profil'] = $plxAdmin->aUsers[$_SESSION['user']]['profil'];
	}
}

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminPrepend'));

# Restricted grants for PROFIL_SUBSCRIBER and others
if(
	!defined('PLX_AUTHPAGE') and
	$_SESSION['profil'] > PROFIL_WRITER and
	!preg_match('#/core/admin/profil\.php$#', $_SERVER['PHP_SELF'])
) {
	header('Location: profil.php');
	exit;
}

# Chargement des fichiers de langue en fonction du profil de l'utilisateur connecté
loadLang('../lang/'.$lang.'/admin.php');
loadLang('../lang/'.$lang.'/core.php');

# on stocke la langue utilisée pour l'affichage de la zone d'administration en variable de session
# nb: la langue peut etre modifiée par le hook AdminPrepend via des plugins
$_SESSION['admin_lang'] = $lang;

# Tableau des profils
const PROFIL_NAMES = array(
	PROFIL_ADMIN => L_PROFIL_ADMIN,
	PROFIL_MANAGER => L_PROFIL_MANAGER,
	PROFIL_MODERATOR => L_PROFIL_MODERATOR,
	PROFIL_EDITOR => L_PROFIL_EDITOR,
	PROFIL_WRITER => L_PROFIL_WRITER,
	PROFIL_SUBSCRIBER => L_PROFIL_SUBSCRIBER,
);

const ALLOW_COM_OPTIONS = [
	0 => L_NO,
	1 => L_EVERY_BODY,
	2 => L_SUBSCRIBERS_ONLY,
];

const ALLOW_COM_SUBSCRIBERS = [
	0 => L_NO,
	2 => L_SUBSCRIBERS_ONLY,
];

# On impose le charset
header('Content-Type: text/html; charset='.PLX_CHARSET);

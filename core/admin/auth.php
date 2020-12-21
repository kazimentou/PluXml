<?php

/**
 * PluXml backoffice authentication page
 *
 * @package PLX
 * @author Stephane F, Florent MONTHEL, Pedro "P3ter" CADETE
 **/

const PLX_AUTHPAGE = true;
// Brut force protection
const MAX_LOGIN_COUNTER = 3; // max tries
const MAX_LOGIN_TIMER = 3; // Wait time in minutes until the next attempt if counter resets

include 'prepend.php';

# CSRF token validation
plxToken::validateFormToken($_POST);

# Plugins hook
eval($plxAdmin->plxPlugins->callHook('AdminAuthPrepend'));

# Disconnection if query == "d=1"
if (!empty($_GET['d']) and $_GET['d'] == 1) {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . PLX_ROOT . 'index.php');
    exit;
}

# Set the counter and timer for authenticaton if not
if(
	!isset($_SESSION['login_timer']) or
	$_SESSION['login_timer'] < time() or
	!isset($_SESSION['login_counter'])
) {
    // count preset
	$_SESSION['login_counter'] = MAX_LOGIN_COUNTER;
	$_SESSION['login_timer'] = time() + MAX_LOGIN_TIMER * 60;
}

if(!empty($_POST['login'])) {
	if(
		!empty($plxAdmin->aConf['lostpassword']) and
		!empty($_POST['new_password']) and
		!empty($_POST['email'])
	) {
		# New password requested
		$unknown = true;
		foreach ($plxAdmin->aUsers as $userid => $user) {
			if($user['login'] == $_POST['login'] and $user['email'] == $_POST['email']) {
				$new_password = plxUtils::charAleatoire();
				$_SESSION['user'] = $userid;
				if($plxAdmin->editPassword(array(
					'password1'		=> $new_password,
					'password2'		=> $new_password,
					'save_password'	=> true,
				))) {
					# Send $new_password by e-mail
					foreach(array($user['lang'], $plxAdmin->aConf['default_lang'], 'en', 'fr', 'de', 'es') as $lang) {
						$filename = PLX_CORE . 'lang/'. $lang . '/new_password.txt';
						if(is_readable($filename)) {
							break;
						}
					}
					list($subject, $body) = explode(
						'-----',
						strtr(
							file_get_contents($filename),
							array(
								'990'	=> $plxAdmin->aConf['title'],
								'991'	=> $user['name'],
								'992'	=> $user['login'],
								'993'	=> $new_password,
								'994'	=> L_PROFIL,
								'995'	=> $_SERVER['REMOTE_ADDR'],
								'996'	=> $_SERVER['HTTP_USER_AGENT'],
								'997'	=> $_SERVER['HTTP_ACCEPT_LANGUAGE'],
								'998'	=> $plxAdmin->aConf['racine'],
							)
						)
					);

					if(plxUtils::sendMail('', '', $user['email'], $subject, $body)) {
						$msg = sprintf(L_MAIL_TEST_SENT_TO, $_POST['email']);
					} else {
						$msg = L_SENT_MAIL_FAILURE;
					}
				};

				unset($_SESSION['user']);
				$unknown = false;
				break;
			}
		}
		if($unknown) {
			$msg = L_UNKNOWN_USER;
			$alert = true;
		}
	} elseif($_SESSION['login_counter'] > 0) {
		if(!empty($_POST['password'])) {
			# Checks authentication
		    foreach ($plxAdmin->aUsers as $userid => $user) {
				if(!empty($user['active']) and empty($user['delete']) and $user['login'] == $_POST['login']) {
					$hash = sha1($user['salt'] . md5($_POST['password']));
					foreach(array('password', 'old_password') as $pwd) {
				        if (!empty($user[$pwd]) and $hash === $user[$pwd]) {
							# Authentication success !
				            $_SESSION = array(
					            'user'			=> $userid,
					            'hash'			=> plxUtils::charAleatoire(10),
					            'domain'		=> $session_domain,
					            'profil'		=> $user['profil'],
					            'admin_lang'	=> $user['lang'],
					            'auth_time'		=> $user['timestamp'],
				            );

				            $plxAdmin->editPassword(array(
								# move old password to password
								'restore'	=> ($pwd == 'old_password') ? 1 : 0,
								# clear old_password if not empty
								'auth' 		=> $plxAdmin->aConf['clef'],
				            ));

							header('Location: index.php');
							exit;
				        }
					}
				}
		    }
		}

		# Echec à l'authentification
		$_SESSION['login_counter']--;
		$msg = L_ERR_WRONG_PASSWORD;
		$alert = true;

		if (intval($_SESSION['login_counter']) == 0) {
			// write in the logs if the unsucessfull connexion attempts
			@error_log('PluXml: Max login failed. IP : ' . plxUtils::getIp());
			$_SESSION['login_timer'] = time() + MAX_LOGIN_TIMER * 60;
		}
	} else {
		// alert to display
		$msg = sprintf(L_ERR_MAXLOGIN,  MAX_LOGIN['timer']);
		$alert = true;
	}
}

// View construction
plxUtils::cleanHeaders();
?>
<!DOCTYPE html>
<html lang="<?= $plxAdmin->aConf['default_lang'] ?>">
<head>
	<meta charset="<?= strtolower(PLX_CHARSET) ?>">
	<meta name="robots" content="noindex, nofollow"/>
    <meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
    <title>PluXml - <?= L_AUTH_PAGE_TITLE ?></title>
    <link rel="stylesheet" type="text/css" href="theme/css/knacss.css?v=<?= PLX_VERSION ?>" media="screen"/>
    <link rel="stylesheet" type="text/css" href="theme/css/theme.css?v=<?= PLX_VERSION ?>" media="screen"/>
    <link rel="stylesheet" type="text/css" href="theme/fontello/css/fontello.css?v=<?= PLX_VERSION ?>" media="screen"/>
    <link rel="icon" href="theme/images/favicon.png"/>
<?php
PlxUtils::printLinkCss($plxAdmin->aConf['custom_admincss_file'], true);
PlxUtils::printLinkCss($plxAdmin->aConf['racine_plugins'] . 'admin.css', true);

eval($plxAdmin->plxPlugins->callHook('AdminAuthEndHead'));

$logo = (!empty($plxAdmin->aConf['thumbnail']) and file_exists(PLX_ROOT . $plxAdmin->aConf['thumbnail'])) ? PLX_ROOT . $plxAdmin->aConf['thumbnail'] : 'theme/images/pluxml.png';
$logoSize = getimagesize($logo);
?>
</head>
<body id="auth">
	<div class="auth">
	    <header>
			<a class="logo" href="<?= PLX_ROOT ?>"><img src="<?= $logo ?>" alt="Logo" <?= $logoSize[3] ?> /></a>
		</header>
		<section>
<?php
eval($plxAdmin->plxPlugins->callHook('AdminAuthBegin'));
?>
			<form method="post">
				<?= PlxToken::getTokenPostMethod() ?>
				<h1 class="h3-like txtcenter mam"><?= L_LOGIN_PAGE ?></h1>
<?php
if(!empty($msg)) {
	PlxUtils::showMsg($msg, !empty($alert) ? 'alert--danger' : '');
}
?>
				<fieldset>
					<p>
						<input type="text" name="login" value="<?= !empty($_POST['login']) ? PlxUtils::strCheck($_POST['login']) : '' ?>" placeholder="<?= L_AUTH_LOGIN_FIELD ?>" required />
					</p>
					<input type="checkbox" name="new_password" value="1" id="toggle-email" class="toggle" />
					<p>
						<input type="password" name="password" value="" maxlength="64" placeholder="<?= L_PASSWORD ?>" required />
					</p>
<?php
	if(!empty($plxAdmin->aConf['lostpassword'])) {
?>
					<p>
						<input type="email" name="email" value="" placeholder="<?= L_EMAIL ?>" />
					</p>
					<p>
						<label for="toggle-email" class="btn--warning"><span><?= L_NEW_PASSWORD ?></span><span><?= L_CANCEL ?></span></label>
					</p>
<?php
	}
?>
					<p>
						<input class="btn--primary" role="button" type="submit" value="<?= L_SUBMIT_BUTTON ?>"/>
					</p>
					<?php eval($plxAdmin->plxPlugins->callHook('AdminAuth')); ?>
				</fieldset>
			</form>
		</section>
		<footer>
			<a href="<?= PLX_ROOT ?>index.php" ><i class="icon-home-1"></i><?= L_HOMEPAGE ?></a>
		</footer>
	</div>
<script>
	'use strict;'
	document.forms[0].elements.new_password.onchange = function(event) {
		const pwd = document.forms[0].elements.password;
		const email = document.forms[0].elements.email;
		email.required = event.target.checked;
		pwd.required = !email.required;
		pwd.style.display = email.required ? 'none' : '';
		if(event.target.checked) {
			email.focus();
		}
	}
</script>
</body>
</html>

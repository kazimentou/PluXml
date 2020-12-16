<?php

/**
 * Edition des paramètres d'affichage
 *
 * @package PLX
 * @author    Florent MONTHEL
 **/

include 'prepend.php';

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

# Control du token du formulaire
plxToken::validateFormToken($_POST);

const EMAIL_HEAD = <<< HEAD
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8" />
<title>sans titre</title>
</head><body>
HEAD;
const EMAIL_FOOT = '</body></html>';

$email = filter_var($plxAdmin->aUsers[$_SESSION['user']]['email'], FILTER_VALIDATE_EMAIL);
$emailBuild = (is_string($email) and filter_has_var(INPUT_POST, 'sendmail-test'));
if ($emailBuild) {
    # body of test e-mail starts here
    ob_start();
} else {
    # direct output
    # administration header
    include 'top.php';
}

?>
<div class="adminheader">
    <div>
        <h2 class="h3-like"><?= L_CONFIG_INFOS_TITLE ?></h2>
        <?php $plxAdmin->checkMaj(); ?>
    </div>
</div>

<div class="admin">
	<div>
	    <p><?= L_CONFIG_INFOS_DESCRIPTION ?></p>

	    <p><strong><?= L_PLUXML_VERSION; ?> <?= PLX_VERSION; ?>
	            (<?= L_INFO_CHARSET ?> <?= PLX_CHARSET ?>)</strong></p>
	    <ul class="unstyled-list">
	        <li><?= L_INFO_PHP_VERSION; ?> : <?= phpversion(); ?></li>
<?php if (!empty($_SERVER['SERVER_SOFTWARE'])) { ?>
            <li><?= $_SERVER['SERVER_SOFTWARE']; ?></li>
<?php } ?>
	    </ul>
	    <ul class="unstyled-list">
<?php
foreach(array(
	'',
	PLX_CONFIG_PATH,
	PLX_CONFIG_PATH . 'plugins/',
) as $p) {
	plxUtils::testWrite(PLX_ROOT . $p);
}

foreach(array(
	'racine_articles',
	'racine_commentaires',
	'racine_statiques',
	'medias',
	'racine_plugins',
	'racine_themes',
) as $k) {
	plxUtils::testWrite(PLX_ROOT . $plxAdmin->aConf[$k]);
}

# Test module Apache2
plxUtils::testModReWrite();

# Test librairies PHP
plxUtils::testLib();

?>
	        <li><span class="success">&#10004; PLX_CONFIG_PATH = '<?= PLX_CONFIG_PATH ?>'</span></li>
<?php
if (plxUtils::testMail() and is_string($email) and !$emailBuild) {
?>
	            <form method="post">
	                <?= plxToken::getTokenPostMethod() ?>
	                <input type="submit" name="sendmail-test" value="<?= L_MAIL_TEST ?>"/>
	            </form>
<?php
}
?>
	    </ul>
	</div>
	<div>
		<p><?= L_CONFIG_INFOS_NB_CATS ?> <?= sizeof($plxAdmin->aCats); ?></p>
		<p><?= L_CONFIG_INFOS_NB_STATICS ?> <?= sizeof($plxAdmin->aStats); ?></p>
		<p><?= L_CONFIG_INFOS_WRITER ?> <?= $plxAdmin->aUsers[$_SESSION['user']]['name'] ?></p>
		<p><a href="theme/fontello/demo.html" target="_blank" class="button">Fontello icons</a></p>

<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminSettingsInfos'))
?>
	</div>
</div>

<?php
if ($emailBuild) {
    $content = ob_get_clean();
    $subject = sprintf(L_MAIL_TEST_SUBJECT, $plxAdmin->aConf['title']);

    if (
		empty($plxAdmin->aConf['email_method']) or
		$plxAdmin->aConf['email_method'] == 'sendmail' or
		!class_exists('PHPMailer') or
		!method_exists('plxUtils', 'sendMailPhpMailer')
	) {
        # fonction mail() intrinséque à PHP
        $method = '<p style="font-size: 80%;"><em>mail() function from PHP</em></p>';
        $body = EMAIL_HEAD . $content . $method . EMAIL_FOOT;
        if (plxUtils::sendMail('', '', $email, $subject, $body, 'html')) {
            plxMsg::Info(sprintf(L_MAIL_TEST_SENT_TO, $email));
        } else {
            plxMsg::Error(L_MAIL_TEST_FAILURE);
        }
    } else {
        # module externe PHPMailer -
        $method = '<p style="font-size: 80%;"><em>' . $plxAdmin->aConf['email_method'] . ' via PHPMailer</em></p>';
        $body = EMAIL_HEAD . $content . $method . EMAIL_FOOT;

	    // Webmaster
	    $name = $plxAdmin->aUsers['001']['name']; // Peut être vide pour PHPMailer
	    $from = $plxAdmin->aUsers['001']['email'];

        if (plxUtils::sendMailPhpMailer($name, $from, $email, $subject, $body, true, $plxAdmin->aConf)) {
            plxMsg::Info(sprintf(L_MAIL_TEST_SENT_TO, $email));
        } else {
            plxMsg::Error(L_MAIL_TEST_FAILURE);
        }
    }

    header('Location: ' . basename(__FILE__));
    exit;
}

# On inclut le footer
include 'foot.php';

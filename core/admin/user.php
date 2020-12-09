<?php

/**
 * Edition des options d'un utilisateur
 *
 * @package PLX
 * @author    Stephane F.
 **/

include 'prepend.php';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminUserPrepend'));

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

if (!empty($_POST) and isset($plxAdmin->aUsers[$_POST['id']])) {
    $plxAdmin->editUser($_POST);
    header('Location: user.php?p=' . $_POST['id']);
    exit;
}

if(!empty($_POST['password'])) {
	$plxAdmin->editPassword($_POST);
    header('Location: user.php');
    exit;
}

if (isset($_GET['p'])) {
    $id = plxUtils::strCheck(plxUtils::nullbyteRemove($_GET['p']));
} else {
	$id = $_SESSION['user'];
}

# On vérifie l'existence de l'utilisateur
if (!array_key_exists($id, $plxAdmin->aUsers)) {
	plxMsg::Error(L_USER_UNKNOWN);
	header('Location: parametres_users.php');
	exit;
}

# On inclut le header
include 'top.php';
?>
<form method="post" id="form_user" class="first-level">
    <?= plxToken::getTokenPostMethod() ?>
    <input type="hidden" name="id" value="<?= $id ?>" />
	<div class="adminheader">
		<div>
            <h2><?php
if($id != $_SESSION['user']) {
		printf(L_USER_PAGE_TITLE, '<span class="name">' . plxUtils::strCheck($plxAdmin->aUsers[$id]['name']) . '</span>');
} else {
	 echo L_PROFIL_EDIT_TITLE;
} ?></h2>
<?php
if(isset($_GET['p'])) {
?>
            <p><a class="back icon-left-big" href="parametres_users.php"><?= L_USER_BACK_TO_PAGE ?></a></p>
<?php
}
?>
		</div>
		<div>
            <input type="submit" class="button--primary" value="<?= L_SAVE ?>" />
		</div>
	</div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminUserTop')) ;
?>
	<fieldset>
		<div class="label-expanded">
			<label for="id_name"><?= L_PROFIL_USER ?></label>
			<?php plxUtils::printInput('name', plxUtils::strCheck($plxAdmin->aUsers[$id]['name']), 'text', '20-255') ?>
		</div>
		<div class="label-expanded">
			<label for="id_email"><?= L_MAIL_ADDRESS ?></label>
			<input type="email" name="email" value="<?= plxUtils::strCheck($plxAdmin->aUsers[$id]['email']) ?>" id="id_email" />
		</div>
		<div class="label-expanded">
			<label for="id_name"><?= L_PROFIL_LOGIN ?></label>
			<?php plxUtils::printInput('login', plxUtils::strCheck($plxAdmin->aUsers[$id]['login']), 'text', '20-255') ?>
		</div>
		<div class="label-expanded">
			<label for="id_lang"><?= L_USER_LANG ?></label>
<?php plxUtils::printSelect('lang', plxUtils::getLangs(), $plxAdmin->aUsers[$id]['lang']) ?>
		</div>
		<div>
            <label for="id_content"><?= L_INFOS ?></label>
            <textarea name="content" rows="8" id="id_content"><?= plxUtils::strCheck($plxAdmin->aUsers[$id]['infos']) ?></textarea>
		</div>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminUser'))
?>
	</fieldset>
</form>
<?php
if($id == $_SESSION['user']) {
?>
<form method="post" id="form_password" class="first-level">
	<?= plxToken::getTokenPostMethod(); ?>
	<fieldset>
		<h3><?= L_PROFIL_CHANGE_PASSWORD ?></h3>
		<div class="grid-2">
			<label for="id_password1"><?= L_PASSWORD ?></label>
			<?php plxUtils::printInput('password1', '', 'password', '20-255', false, '', '', 'onkeyup="pwdStrength(this.id)"') ?>
			<label for="id_password2"><?= L_CONFIRM_PASSWORD ?></label>
			<?php plxUtils::printInput('password2', '', 'password', '20-255') ?>
		</div>
		<input class="btn--primary" type="submit" name="password" role="button" value="<?= L_PROFIL_UPDATE_PASSWORD ?>"/>
	</fieldset>
</form>
<?php
}

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminUserFoot'));

# On inclut le footer
include 'foot.php';

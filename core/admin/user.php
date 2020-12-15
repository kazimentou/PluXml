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

if (!empty($_POST['id']) and isset($plxAdmin->aUsers[$_POST['id']])) {
    $plxAdmin->editUser($_POST);
    header('Location: user.php?p=' . $_POST['id']);
    exit;
}

if(!empty($_POST['password1'])) {
	$plxAdmin->editPassword($_POST);
    header('Location: user.php');
    exit;
}

if (isset($_GET['p'])) {
	# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
	$plxAdmin->checkProfil(PROFIL_ADMIN);
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
		<label class="caption-inside">
			<span><?= L_PROFIL_USER ?></span>
			<input type="text" name="name" value="<?= plxUtils::strCheck($plxAdmin->aUsers[$id]['name']) ?>" required />
		</label>
		<label class="caption-inside">
			<span><?= L_MAIL_ADDRESS ?></span>
			<input type="email" name="email" value="<?= plxUtils::strCheck($plxAdmin->aUsers[$id]['email']) ?>" />
		</label>
		<label class="caption-inside">
			<span><?= L_PROFIL_LOGIN ?></span>
			<input type="text" name="login" value="<?= plxUtils::strCheck($plxAdmin->aUsers[$id]['login']) ?>" required />
		</label>
		</div>
		<label class="caption-inside">
			<span><?= L_USER_LANG ?></span>
<?php plxUtils::printSelect('lang', plxUtils::getLangs(), $plxAdmin->aUsers[$id]['lang']) ?>
		</label>
		<div>
            <label for="id_content"><?= L_INFOS ?></label>
            <textarea name="content" rows="8" id="id_content"><?= plxUtils::strCheck($plxAdmin->aUsers[$id]['infos']) ?></textarea>
		</div>
	</fieldset>
<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminUser'))
?>
</form>
<?php
if($id == $_SESSION['user']) {
?>
<form method="post" id="form_password" class="first-level">
	<?= plxToken::getTokenPostMethod(); ?>
	<fieldset>
		<h3 class="txtcenter"><?= L_PROFIL_CHANGE_PASSWORD ?></h3>
		<label class="caption-inside">
			<span><?= L_PASSWORD ?></span>
			<input type="password" name="password1" onkeyup="pwdStrength(this.id);" required id="password1" />
		</label>
		<label class="caption-inside">
			<span><?= L_CONFIRM_PASSWORD ?></span>
			<input type="password" name="password2" required />
		</label>
		<div class="txtcenter">
			<input class="btn--primary" type="submit" name="password" role="button" value="<?= L_SAVE ?>"/>
		</div>
	</fieldset>
</form>
<?php
}

# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminUserFoot'));

# On inclut le footer
include 'foot.php';

<?php
/**
 * Classe de mise a jour pour PluXml version 5.1.1
 *
 * @package PLX
 * @author	Stephane F, Jean-Pierre Pourrez @bazooka07
 **/
class update_5_1_1 extends plxUpdate{

	# Migration du fichier des utilisateurs: renforcement des mots de passe
	public function step1() {
?>
		<li><?= L_UPDATE_USERS_MIGRATION ?></li>
<?php
		# On génère le fichier XML
		ob_start();
?>
<document>
<?php
		foreach($this->plxAdmin->aUsers as $user_id => $user) {
			$salt = plxUtils::charAleatoire(10);
			$password = sha1($salt.$user['password']);
?>
	<user number="<?= $user_id ?>" active="<?= $user['active'] ?>" profil="<?= $user['profil'] ?>" delete="<?= $user['delete'] ?>">
		<login><![CDATA[<?= plxUtils::cdataCheck($user['login']) ?>]]></login>
		<name><![CDATA[<?= plxUtils::cdataCheck($user['name']) ?>]]></name>
		<infos><![CDATA[<?= plxUtils::cdataCheck($user['infos']) ?>]]></infos>
		<password><![CDATA[<?= $password ?>]]></password>
		<salt><![CDATA[<?= $salt ?>]]></salt>
		<email><![CDATA[<?= $user['email'] ?>]]></email>
		<lang><![CDATA[<?= $user['lang'] ?>]]></lang>
	</user>
<?php
		}
?>
</document>
<?php

		if(!plxUtils::write(XML_HEADER . ob_get_clean() , PLX_ROOT . $this->plxAdmin->aConf['users'])) {
?>
		<p class="error"><?= L_UPDATE_ERR_USERS_MIGRATION ?> (<?= $this->plxAdmin->aConf['users'] ?>)</p>
<?php
			return false;
		}

		return true;
	}

}

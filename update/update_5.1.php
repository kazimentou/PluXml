<?php
/**
 * Classe de mise a jour pour PluXml version 5.1
 *
 * @package PLX
 * @author	Stephane F, Jean-Pierre Pourrez @bazooka07
 **/
class update_5_1 extends plxUpdate{

	# mise à jour fichier parametres.xml
	public function step1() {
?>
		<li><?= L_UPDATE_UPDATE_PARAMETERS_FILE ?></li>
<?php
		# on supprime les parametres obsoletes
		unset($this->plxAdmin->aConf['editor']);
		unset($this->plxAdmin->aConf['style_mobile']);

		# mise à jour du fichier des parametres
		# nouveaux parametres
		$this->updateParameters(array(
			'bypage_archives'	=> 5,
			'userfolders'		=> 1,
			'meta_description'	=> '',
			'meta_keywords'		=> '',
			'plugins'			=> 'data/configuration/plugins.xml',
			'default_lang'		=> isset($_POST['default_lang']) ? $_POST['default_lang'] : DEFAULT_LANG,
		));
		return true; # pas d'erreurs
	}

	# création d'un fichier	.htacces dans le dossier data pour eviter de lister les dossiers
	public function step2() {
?>
		<li><?= L_UPDATE_CREATE_HTACCESS_FILE ?> <?= PLX_ROOT ?>data/.htaccess<</li>
<?php
		if(!plxUtils::write('options -indexes', PLX_ROOT.'data/.htaccess')) {
?>
		<p class="error"><?= L_UPDATE_CREATE_HTACCESS_FILE . ' ' . PLX_ROOT ?>data/.htaccess</p>
<?php
			return false;
		}
		return true; # pas d'erreurs
	}

	# Migration du fichier des categories
	public function step3() {
?>
		<li><?= L_UPDATE_CATEGORIES_MIGRATION ?></li>
<?php
		if($categories = $this->_getCategories(PLX_ROOT.$this->plxAdmin->aConf['categories'])) {
			# On génère le fichier XML
			ob_start();
?>
<document>
<?php
			foreach($categories as $cat_id => $cat) {
?>
	<categorie number="<?= $cat_id ?>" tri="<?= $cat['tri'] ?>" bypage="<?= $cat['bypage'] ?>" menu="<?= $cat['menu'] ?>" url="<?= $cat['url'] ?>" template="<?= $cat['template'] ?>">
		<name><![CDATA[<?= plxUtils::cdataCheck($cat['name']) ?>]]></name>
		<description></description>
		<meta_description></meta_description>
		<meta_keywords></meta_keywords>
	</categorie>
<?php
			}
?>
</document>
<?php
			if(!plxUtils::write(XML_HEADER . ob_get_clean(), PLX_ROOT.$this->plxAdmin->aConf['categories'])) {
?>
		<p class="error"><?= L_UPDATE_ERR_CATEGORIES_MIGRATION ?> (<?= $this->plxAdmin->aConf['categories'] ?>)</p>
<?php
				return false;
			}
		}
		return true;
	}


	# Migration du fichier des page statiques
	public function step4() {
?>
		<li><?= L_UPDATE_STATICS_MIGRATION ?></li>
<?php
		if($statics = $this->_getStatiques(PLX_ROOT.$this->plxAdmin->aConf['statiques'])) {
			# On génère le fichier XML
			$xml = "<?xml version=\"1.0\" encoding=\"".PLX_CHARSET."\"?>\n";
			$xml .= "<document>\n";
			foreach($statics as $static_id => $static) {
				$xml .= "\t<statique number=\"".$static_id."\" active=\"".$static['active']."\" menu=\"".$static['menu']."\" url=\"".$static['url']."\" template=\"".$static['template']."\">";
				$xml .= "<group><![CDATA[".plxUtils::cdataCheck($static['group'])."]]></group>";
				$xml .= "<name><![CDATA[".plxUtils::cdataCheck($static['name'])."]]></name>";
				$xml .= "<meta_description><![CDATA[]]></meta_description>";
				$xml .= "<meta_keywords><![CDATA[]]></meta_keywords>";
				$xml .=	"</statique>\n";
			}
			$xml .= "</document>";
			if(!plxUtils::write($xml,PLX_ROOT.$this->plxAdmin->aConf['statiques'])) {
				echo '<p class="error">'.L_UPDATE_ERR_STATICS_MIGRATION.' ('.$this->plxAdmin->aConf['statiques'].')</p>';
				return false;
			}
		}
		return true;
	}

	# Migration du fichier des utilisateurs
	public function step5() {
?>
		<li><?= L_UPDATE_USERS_MIGRATION ?></li>
<?php
		if($users = $this->_getUsers(PLX_ROOT.$this->plxAdmin->aConf['users'])) {
			# On génère le fichier XML
			ob_start();
?>
<document>
<?php
			foreach($users as $user_id => $user) {
				if(intval($user['profil'] == 2)) {
					$user['profil'] = 4;
				}
?>
	<user number="<?= $user_id ?>" active="<?= $user['active'] ?>" profil="<?= $user['profil'] ?>" delete="<?= $user['delete'] ?>">
		<login><![CDATA[<?= plxUtils::cdataCheck(trim($user['login'])) ?>]]></login>
		<name><![CDATA[<?= plxUtils::cdataCheck(trim($user['name'])) ?>]]></name>
		<infos><![CDATA[<?= plxUtils::cdataCheck(trim($user['infos'])) ?>]]></infos>
		<password><![CDATA[<?= $user['password'] ?>]]></password>
		<email></email>
	</user>
<?php
			}
?>
</document>
<?php
			if(!plxUtils::write(XML_HEADER . ob_get_clean(), PLX_ROOT . $this->plxAdmin->aConf['users'])) {
?>
		<p class="error"><?= L_UPDATE_ERR_USERS_MIGRATION ?> (<?= $this->plxAdmin->aConf['users'] ?>)</p>
<?php
				return false;
			}
		}
		return true;
	}

	# Création du fichier data/configuration/plugins.xml
	public function step6() {
?>
		<li><?= L_UPDATE_CREATE_PLUGINS_FILE ?></li>
<?php
		ob_start();
?>
<document>
</document>
<?php
		if(!plxUtils::write(XML_HEADER . ob_get_clean(), PLX_ROOT . $this->plxAdmin->aConf['plugins'])) {
?>
		<p class="error">><?= L_UPDATE_ERR_CREATE_PLUGINS_FILE ?></p>
<?php
			return false;
		}
		return true;
	}

	# suppression du fichier core/admin/fullscreen.php
	public function step7() {
		$filename = PLX_ROOT.'core/admin/fullscreen.php';
		if(file_exists($filename)) {
?>
		<li><?= L_UPDATE_DELETE_FULLSCREEN_FILE ?></li>
<?php

			if(!unlink($filename)) {
?>
		<p class="error"><?= L_UPDATE_ERR_DELETE_FULLSCREEN_FILE ?></p>
<?php
			}
		}
		return true;
	}

	# suppression du dossier de la plxtoolar
	public function step8() {
		$filename = PLX_ROOT . 'core/plxtoolbar';
		if(is_dir($filename)) {
?>
		<li><?= L_UPDATE_DELETE_PLXTOOLBAR_FOLDER ?></li>
<?php
			if(!$this->deleteDir($filename)) {
?>
		<p class="error"><?= L_UPDATE_ERR_DELETE_PLXTOOLBAR_FOLDER ?></p>
<?php
			}
		}
		return true;
	}

	/***************/

	private function _getCategories($filename) {
		$aCats=array();
		if(is_file($filename)) {
			# Mise en place du parseur XML
			$data = implode('',file($filename));
			$parser = xml_parser_create(PLX_CHARSET);
			xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
			xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
			xml_parse_into_struct($parser,$data,$values,$iTags);
			xml_parser_free($parser);

			# On verifie qu'il existe des tags "categorie"
			if(isset($iTags['categorie'])) {
				foreach($iTags['categorie'] as $idx) {
					$attrs = $values[$idx]['attributes'];
					$id = $attrs['number'];
					$aCats[$id] = array(
						'name' => $values[$idx]['value'], # nom de la categorie
						'url' => strtolower($attrs['url']), # url de la categorie
						'tri' => isset($attrs['tri']) ? $attrs['tri'] : $this->aConf['tri'], # tri de la categorie si besoin
						'bypage' => isset($attrs['bypage']) ? $attrs['bypage'] : $this->bypage, # nb d'articles par page de la categorie si besoin
						'template' => isset($attrs['template']) ? $attrs['template'] : 'categorie.php',
						'menu' => isset($attrs['menu']) ? $attrs['menu'] :'oui', # afficher la categorie dans le menu ?
					);
				}
			}
		}

		return $aCats;
	}

	private function _getStatiques($filename) {
		$aStats=array();
		if(is_file($filename)) {
			# Mise en place du parseur XML
			$data = implode('',file($filename));
			$parser = xml_parser_create(PLX_CHARSET);
			xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
			xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
			xml_parse_into_struct($parser,$data,$values,$iTags);
			xml_parser_free($parser);

			# On verifie qu'il existe des tags "statique"
			if(isset($iTags['statique']) AND isset($iTags['name'])) {
				$i = 0;
				foreach($iTags['statique'] as $idx) {
					if(
						!isset($values[$idx]['attributes']) or
						empty($values[$iTags['name'][$i]])
					) {
						continue;
					}

					$attrs = $values[$idx]['attributes'];
					$id = $attrs['number'];
					$aStats[$id] = array(
						'group' => isset($values[$iTags['group'][$i]]) ? $values[$iTags['group'][$i]]['value'] : '',
						'name' => $values[$iTags['name'][$i]]['value'],
						'url' => $attrs['url'],
						'active' => intval($attrs['active']),
						'menu' => isset($attrs['menu']) ? $attrs['menu'] : 'oui',
						'template'=> isset($attrs['template']) ? $attrs['template'] : 'static.php',
					);
					$i++;
				}
			}
		}
		return $aStats;
	}

	private function _getUsers($filename) {
		$aUsers=array();
		if(is_file($filename)) {
			# Mise en place du parseur XML
			$data = implode('',file($filename));
			$parser = xml_parser_create(PLX_CHARSET);
			xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
			xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
			xml_parse_into_struct($parser,$data,$values,$iTags);
			xml_parser_free($parser);

			# On verifie qu'il existe des tags "user"
			if(isset($iTags['user']) AND isset($iTags['login'])) {
				$i = 0;
				foreach($iTags['user'] as $idx) {
					if(
						!isset($values[$idx]['attributes']) or
						empty($values[$iTags['login'][$i]])
					) {
						continue;
					}

					$attrs = $values[$idx]['attributes'];
					$id = $attrs['number'];
					$aUsers[$id] = array(
						'active' => $attrs['active'],
						'profil' => $attrs['profil'],
						'delete' => $attrs['delete'],
						'login' => $values[$iTags['login'][$i]]['value'],
						'name' => isset($values[$iTags['name'][$i]]) ? $values[$iTags['name'][$i]]['value'] : '',
						'infos' => isset($values[$iTags['infos'][$i]]) ? $values[$iTags['infos'][$i]]['value'] : '',
						'password' => isset($values[$iTags['password'][$i]]) ? $values[$iTags['password'][$i]]['value'] : '',
					);
					$i++;
				}
			}
		}
		# On retourne le tableau
		return $aUsers;
	}

}

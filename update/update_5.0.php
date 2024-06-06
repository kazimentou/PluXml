<?php
/**
 * Classe de mise a jour pour PluXml version 5.0
 *
 * @package PLX
 * @author	Stephane F
 **/
class update_5_0 extends plxUpdate{

	/* Création des nouveaux paramètres dans le fichier parametres.xml */
	public function step1() {
?>
		<li><?= L_UPDATE_UPDATE_PARAMETERS_FILE ?></li>
<?php
		$new_parameters = array(
			'urlrewriting' 	=> 0,
			'gzip'		 	=> 0,
			'feed_chapo' 	=> 0,
			'feed_footer' 	=> '',
			'users' 		=> 'data/configuration/users.xml',
			'tags' 			=> 'data/configuration/tags.xml',
			'editor'		=> 'plxtoolbar',
			'homestatic'	=> ''
		);
		$this->updateParameters($new_parameters);
		$this->plxAdmin->getConfiguration(path('XMLFILE_PARAMETERS')); # on recharge le fichier de configuration
		return true; # pas d'erreurs
	}

	/* Création du fichier data/configuration/tags.xml */
	public function step2() {
?>
		<li><?= L_UPDATE_CREATE_TAGS_FILE ?></li>
<?php
		$xml = XML_HEADER . '<document>'. PHP_EOL . '</document>';
		if(!plxUtils::write($xml,PLX_ROOT.$this->plxAdmin->aConf['tags'])) {
?>
	<p class="error"><?= L_UPDATE_ERR_CREATE_TAGS_FILE ?></p>
<?php
			return false;
		}
		return true;
	}

	/* Création du fichier themes/style/tags.php */
	public function step3() {
		$srcfile = PLX_ROOT.'themes/'.$this->plxAdmin->aConf['style'].'/home.php';
		$dstfile = PLX_ROOT.'themes/'.$this->plxAdmin->aConf['style'].'/tags.php';
		if(!is_file($dstfile)) {
?>
		<li><?= L_UPDATE_CREATE_THEME_FILE ?>: themes/ <?= $this->plxAdmin->aConf['style'] ?>/tags.php</li>
<?php
			if(!copy($srcfile, $dstfile)) {
?>
		<p class="error"><?= L_UPDATE_ERR_CREATE_THEME_FILE ?> themes/style/tags.php</p>
<?php
				return false;
			}
		}
		return true;
	}

	/* Création du fichier themes/style/archives.php */
	public function step4() {
		$srcfile = PLX_ROOT.'themes/'.$this->plxAdmin->aConf['style'].'/home.php';
		$dstfile = PLX_ROOT.'themes/'.$this->plxAdmin->aConf['style'].'/archives.php';
		if(!is_file($dstfile)) {
?>
		<li><?= L_UPDATE_CREATE_THEME_FILE ?> themes/<?= $this->plxAdmin->aConf['style'] ?>/archives.php</li>
<?php
			if(!copy($srcfile, $dstfile)) {
?>
		<p class="error"><?= L_UPDATE_ERR_CREATE_THEME_FILE ?> themes/style/archives.php</p>
<?php
				return false;
			}
		}
		return true;
	}

	/* Migration des articles: formatage xml + renommage des fichiers */
	public function step5() {
?>
		<li><?= L_UPDATE_ARTICLES_CONVERSION ?></li>
<?php
		$plxGlob_arts = plxGlob::getInstance(PLX_ROOT.$this->plxAdmin->aConf['racine_articles']);
        if($files = $plxGlob_arts->query('/^[0-9]{4}.([0-9]{3}|home|draft).[0-9]{12}.[a-z0-9-]+.xml$/','art')) {
			foreach($files as $id => $filename){
				$art = $this->parseArticle(PLX_ROOT.$this->plxAdmin->aConf['racine_articles'].$filename);
				if(!$this->plxAdmin->editArticle($art, $art['numero'])) {
?>
		<p class="error"><?= L_UPDATE_ERR_FILE_PROCESSING ?> : <?= $filename ?></p>
<?php
					return false;
				}
			}
		}

		return true;
	}

	/* Migration du fichier des pages statiques */
	public function step6() {
?>
		<li><?= L_UPDATE_STATICS_MIGRATION ?></li>
<?php
		if($statics = $this->getStatiques(PLX_ROOT.$this->plxAdmin->aConf['statiques'])) {
			# On génère le fichier XML
			$xml = "<?xml version=\"1.0\" encoding=\"".PLX_CHARSET."\"?>\n";
			ob_start();
?>
<document>
<?php
			foreach($statics as $static_id => $static) {
?>
	<statique number="<?= $static_id ?>" active="<?= $static['active'] ?>" menu="<?= $static['menu'] ?>" url="<?= $static['url'] ?>" template="static.php"><group></group><name><![CDATA[" <?= $static['name'] ?>"]]></name></statique>
<?php
			}
?>
</document>
<?php
			if(!plxUtils::write(XML_HEADER . ob_get_clean(), PLX_ROOT . $this->plxAdmin->aConf['statiques'])) {
?>
		<p class="error"><?= L_UPDATE_ERR_STATICS_MIGRATION ?> (<em>data/configuration/statiques.xml</em>)</p>
<?php
				return false;
			}
		}
		return true;
	}

	/* Création du fichier des utilisateurs */
	public function step7() {
?>
		<li><?= L_UPDATE_CREATE_USERS_FILE ?></li>
<?php
		if($users = $this->getUsers(PLX_ROOT.$this->plxAdmin->aConf['passwords'])) {
			ob_start();
?>
<document>
<?php
			$num_user = 1;
			foreach($users as $login => $password) {
?>
	<user number="<?= str_pad($num_user++, 3, '0', STR_PAD_LEFT) ?>" active="1" profil="0" delete="0">
		<login><![CDATA[<?= $login ?>]]></login>
		<name><![CDATA[<?= $login ?>]]></name>
		<infos></infos>
		<password><![CDATA[<?= $password ?>]]></password>
	</user>
<?php
			}
?>
</document>
<?php
			if(!plxUtils::write(XML_HEADER. ob_get_clean(), PLX_ROOT . $this->plxAdmin->aConf['users'])) {
?>
		<p class="error"><?= L_UPDATE_ERR_CREATE_USERS_FILE ?> (<em>data/configuration/users.xml</em>)</p>
<?php
				return false;
			}
		}
		else {
?>
		<p class="error"><?= L_UPDATE_ERR_NO_USERS ?> data/configuration/passwords.xml</p>
<?php
			return false;
		}
		return true;
	}

	/* Suppression des données obsolètes */
	public function step8() {
		# suppression du fichier data/configuration/passwords.xml
		# suppression du fichier d'installation
		foreach(
			array(
				PLX_ROOT . $this->plxAdmin->aConf['passwords'],
				PLX_ROOT . 'install.php',
			) as $filename) {
			if(is_file($filename)) {
				unlink($filename);
			}
		}

		# suppression des clés obsolètes dans le fichier data/configuration/parametres.xml
		unset($this->plxAdmin->aConf['password']);
		$this->plxAdmin->editConfiguration($this->plxAdmin->aConf, $this->plxAdmin->aConf);
		return true;
	}

	# Création du fichier .htaccess
	public function step9() {
		if(!is_file(PLX_ROOT.'.htaccess')) {
?>
		<li><?= L_UPDATE_CREATE_HTACCESS_FILE ?></li>
<?php
			$txt = <<< EOT
<Files "version">
    Order allow,deny
    Deny from all
</Files>
EOT;
			if(!plxUtils::write($txt, PLX_ROOT.'.htaccess')) {
?>
		<p class="error"><?= L_UPDATE_ERR_CREATE_HTACCESS_FILE ?></p>
<?php
				return false;
			}
		}
		return true;
	}

	/*=====*/

	private	function artInfoFromFilename($filename) {

		# On effectue notre capture d'informations
		preg_match('#(\d{4})\.(\d{3}|home|draft)\.(\d{12})\.([\w-]+)\.xml$#',$filename,$capture);
		return array(
			'artId'		=> $capture[1],
			'catId'		=> $capture[2],
			'artDate'	=> $capture[3],
			'artUrl'	=> $capture[4]
		);
	}

	private function parseArticle($filename) {
		# Mise en place du parseur XML
		$data = implode('',file($filename));
		$parser = xml_parser_create(PLX_CHARSET);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parse_into_struct($parser,$data,$values,$iTags);
		xml_parser_free($parser);
		$tmp = $this->artInfoFromFilename($filename);
		preg_match('#^(\d{4})(\d{2})(\d{2})(\d{4})$#', $tmp['artDate'], $capture);
		# Recuperation des valeurs de nos champs XML
		$art= array(
			'title'		=> trim($values[ $iTags['title'][0] ]['value']),
			'author'	=> '001',
			'allow_com'	=> trim($values[ $iTags['allow_com'][0] ]['value']),
			'chapo'		=> isset($values[ $iTags['chapo'][0] ]['value']) ? trim($values[ $iTags['chapo'][0] ]['value']) : '',
			'content'	=> isset($values[ $iTags['content'][0] ]['value']) ? trim($values[ $iTags['content'][0] ]['value']) : '',
			# Informations obtenues en analysant le nom du fichier
			'filename'	=> $filename,
			'numero'	=> $tmp['artId'],
			'artId'		=> 'numero',
			'catId'		=> array($tmp['catId']),
			'url'		=> $tmp['artUrl'],
			'date'		=> array (
				'year'	=> $capture[1],
				'month'	=> $capture[2],
				'day'	=> $capture[3],
				'time'	=> $capture[4]
			),
			'day'		=> 'date'['day'],
			'month'		=> 'date'['month'],
			'year'		=> 'date'['year'],
			'time'		=> 'date'['time'],
			#nouveaux champs
			'template'	=> 'article.php',
			'tags'		=> '',
		);
		# On retourne le tableau
		return $art;
	}

	private function getUsers($filename) {
		$users = array();
		if(is_file($filename)) {
			# Mise en place du parseur XML
			$data = implode('',file($filename));
			$parser = xml_parser_create(PLX_CHARSET);
			xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
			xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
			xml_parse_into_struct($parser,$data,$values,$iTags);
			xml_parser_free($parser);
			# On verifie qu'il existe des tags "user"
			if(isset($iTags['user'])) {
				# On compte le nombre de tags "user"
				$nb = sizeof($iTags['user']);
				# On boucle sur $nb
				for($i = 0; $i < $nb; $i++) {
					$users[ $values[ $iTags['user'][$i] ]['attributes']['login'] ] = $values[ $iTags['user'][$i] ]['value'];
				}
			}
		}
		# On retourne le tableau
		return $users;
	}

	private function getStatiques($filename) {
		$aStats = array();
		if(is_file($filename)) {
			# Mise en place du parseur XML
			$data = implode('',file($filename));
			$parser = xml_parser_create(PLX_CHARSET);
			xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
			xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
			xml_parse_into_struct($parser,$data,$values,$iTags);
			xml_parser_free($parser);
			# On verifie qu'il existe des tags "statique"
			if(isset($iTags['statique'])) {
				# On compte le nombre de tags "statique"
				$nb = sizeof($iTags['statique']);
				# On boucle sur $nb
				for($i = 0; $i < $nb; $i++) {
					# Recuperation du nom de la page statique
					$aStats[ $values[ $iTags['statique'][$i] ]['attributes']['number'] ]['name']
					= $values[ $iTags['statique'][$i] ]['value'];
					# Recuperation de l'url de la page statique
					$aStats[ $values[ $iTags['statique'][$i] ]['attributes']['number'] ]['url']
					= strtolower($values[ $iTags['statique'][$i] ]['attributes']['url']);
					# Recuperation de l'etat de la page
					$aStats[ $values[ $iTags['statique'][$i] ]['attributes']['number'] ]['active']
					= intval($values[ $iTags['statique'][$i] ]['attributes']['active']);
					# On affiche la page statique dans le menu ?
					if(isset($values[ $iTags['statique'][$i] ]['attributes']['menu']))
						$aStats[ $values[ $iTags['statique'][$i] ]['attributes']['number'] ]['menu']
						= $values[ $iTags['statique'][$i] ]['attributes']['menu'];
					else
					$aStats[ $values[ $iTags['statique'][$i] ]['attributes']['number'] ]['menu'] = 'oui';
					# On verifie que la page statique existe bien
					$file = PLX_ROOT.$this->plxAdmin->aConf['racine_statiques'].$values[ $iTags['statique'][$i] ]['attributes']['number'];
					$file .= '.'.$values[ $iTags['statique'][$i] ]['attributes']['url'].'.php';
					if(is_readable($file)) # Le fichier existe
						$aStats[ $values[ $iTags['statique'][$i] ]['attributes']['number'] ]['readable'] = 1;
					else # Le fichier est illisible
						$aStats[ $values[ $iTags['statique'][$i] ]['attributes']['number'] ]['readable'] = 0;
				}
			}
		}
		return $aStats;
	}
}

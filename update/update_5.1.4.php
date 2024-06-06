<?php
/**
 * Classe de mise a jour pour PluXml version 5.1.4
 *
 * @package PLX
 * @author	Stephane F, Jean-Pierre Pourrez @bazooka07
 **/
class update_5_1_4 extends plxUpdate{

	# mise à jour fichier parametres.xml
	public function step1() {
?>
		<li><?= L_UPDATE_UPDATE_PARAMETERS_FILE ?></li>
<?php
		# nouveaux parametres
		$new_parameters = array(
			'mod_art'			=> 0,
			'racine_themes'		=> 'themes/',
			'racine_plugins'	=> 'plugins/',
		);
		# mise à jour du fichier des parametres
		$this->updateParameters($new_parameters);
		return true; # pas d'erreurs
	}

	# Migration des articles: ajout nouveau champ title_htmltag
	public function step2() {
?>
		<li><?= L_UPDATE_ARTICLES_CONVERSION ?></li>
<?php
		$plxGlob_arts = plxGlob::getInstance(PLX_ROOT.$this->plxAdmin->aConf['racine_articles']);
        if($files = $plxGlob_arts->query('#(.*)\.xml$#', 'art')) {
			foreach($files as $filename){
				if(is_readable($filename)) {
					$data = file_get_contents(PLX_ROOT.$this->plxAdmin->aConf['racine_articles'].$filename);
					if(!preg_match('#\]\]</title_htmltag>#', $data)) {
						$data = preg_replace('#</document>$#', "\t<title_htmltag></title_htmltag>" . PHP_EOL . "</document>", $data);
					}
					if(!plxUtils::write($data, PLX_ROOT.$this->plxAdmin->aConf['racine_articles'].$filename)) {
?>
		<p class="error"><?= L_UPDATE_ERR_FILE_PROCESSING.' : ' . $filename ?></p>
<?php
						return false;
					}
				}
			}
		}
		return true;
	}

	# Suppression des fichiers obsoletes
	public function step3() {
		foreach(
			array(
				PLX_ROOT . $this->plxAdmin->aConf['racine_articles'] . 'index.html',
				PLX_ROOT . $this->plxAdmin->aConf['racine_commentaires'] . 'index.html',
				PLX_ROOT . $this->plxAdmin->aConf['racine_statiques'] . 'index.html',
				PLX_ROOT . 'blog.php',
			) as $filename
		) {
			if(file_exists($filename)) {
				unlink($filename);
			}
		}

		return true;
	}

}

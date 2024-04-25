<?php
/**
 * Classe de mise a jour pour PluXml version 5.5
 *
 * @package PLX
 * @author	Stephane F
 **/
class update_5_5 extends plxUpdate{

	# mise à jour fichier parametres.xml
	public function step1() {
		# migration avec réindexation des commentaires
?>
			<li><?= L_UPDATE_COMMENTS_MIGRATION ?></li>
<?php

		$dir_coms = PLX_ROOT.$this->plxAdmin->aConf['racine_commentaires'];
		$dir_bkp  = $dir_coms.'backup-5.4/';

		# création d'un dossier de sauvegarde
		if(!is_dir($dir_bkp) and !@mkdir($dir_bkp,0755,true)) {
?>
<p class="error"><?= L_UPDATE_ERR_COMMENTS_MIGRATION ?></p>
<?php
			return false;
		}

		# réindexation
		if($hd = opendir($dir_coms)) {
			$coms = array();
			while (false !== ($file = readdir($hd))) {
				if(preg_match('/([[:punct:]]?)(\d{4}).(\d{10})-(\d+)\.xml$/',$file,$capture)) {
					$coms[$capture[2]][] = $file;
					$src = $dir_coms . $file;
					if(copy($src, $dir_bkp . $file)) { #sauvegarde
						unlink($src); # suppression fichier original
					} else {
?>
<p class="error"><?= L_UPDATE_ERR_COMMENTS_MIGRATION ?></p>
<?php
						return false;
					}
				}
			}
			ksort($coms);
			if($coms) {
				foreach($coms as $com) {
					foreach($com as $idx => $filename) {
						$new_filename =  preg_replace('/(.*)-[0-9]+.xml$/', '$1-'.($idx+1).'.xml', $filename);
						if(!copy($dir_bkp.$filename, $dir_coms.$new_filename)) { # copie migration
?>
					<p class="error"><?= L_UPDATE_ERR_COMMENTS_MIGRATION ?></p>
<?php
							return false;
						}
					}
				}
			}
		}
		# fin de l'étape sans erreurs
		return true;
	}

	# suppression des fichiers obsolètes
	public function step2() {
		# fichier version
		if(is_writeable(PLX_ROOT.'version')) {
			unlink(PLX_ROOT.'version');
		}
		# fichier parametres_pluginhelp.php
		if(is_writeable(PLX_CORE.'admin/parametres_pluginhelp.php')) {
			unlink(PLX_CORE.'admin/parametres_pluginhelp.php');
		}
		return true;
	}
}

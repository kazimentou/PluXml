<?php

/**
 * Gestion des themes
 *
 * @package PLX
 * @author	Stephane F
 **/

include 'prepend.php';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# Control de l'accès à la page en fonction du profil de l'utilisateur connecté
$plxAdmin->checkProfil(PROFIL_ADMIN);

# On édite la configuration
if (!empty($_POST)) {
    $plxAdmin->editConfiguration($plxAdmin->aConf, $_POST);
    header('Location: parametres_themes.php');
    exit;
}

class plxThemes
{
    public $racineTheme;
    public $activeTheme;
    public $aThemes = array(); # liste des themes

    public function __construct($racineTheme, $activeTheme)
    {
        $this->racineTheme = $racineTheme;
        $this->activeTheme = $activeTheme;
        $this->getThemes();
    }

    public function getThemes()
    {
        # on mets le theme actif en début de liste
        if (is_dir($this->racineTheme . $this->activeTheme)) {
            $this->aThemes[$this->activeTheme] = $this->activeTheme;
        }
        # liste des autres themes dispos
        $files = plxGlob::getInstance($this->racineTheme, true);

        if ($styles = $files->query('#^(?!mobile\.)[\w\.\(\)-]+#i', '', 'sort')) {
            foreach ($styles as $k=>$v) {
                if (is_file($this->racineTheme . $v . '/infos.xml')) {
                    if ($v != $this->activeTheme) {
                        $this->aThemes[$v] = $v;
                    }
                }
            }
        }
    }

    public function getImgPreview($theme)
    {
		# Image par défaut
		$img = PLX_CORE . 'admin/theme/images/theme.png';

        foreach(array('png', 'jpg', 'jpeg', 'gif') as $ext) {
			$filename = $this->racineTheme . $theme . '/preview.' . $ext;
			if(file_exists($filename)) {
				$img = $filename;
				break;
			}
		}

        $current = ($theme == $this->activeTheme) ? ' current' : '';
        return '<img class="img-preview' . $current . '" src="' . $img . '" alt="" />';
    }

    public function getInfos($theme)
    {
        $aInfos = array();
        $filename = $this->racineTheme . $theme . '/infos.xml';
        if (is_file($filename)) {
            $data = file_get_contents($filename);
            $parser = xml_parser_create(PLX_CHARSET);
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
            xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
            xml_parse_into_struct($parser, $data, $values, $iTags);
            xml_parser_free($parser);
            foreach(array(
                'title',
                'author',
                'version',
                'date',
                'site',
                'description',
            ) as $k) {
                $aInfos[$k] = plxUtils::getTagValue($iTags[$k], $values);
			}
        }
        return $aInfos;
    }
    # >>>>>>> 2204da71 (Install PHP-CS-Fixer)
}

# On inclut le header
include 'top.php';

$plxThemes = new plxThemes(PLX_ROOT . $plxAdmin->aConf['racine_themes'], $plxAdmin->aConf['style']);

?>
<form action="parametres_themes.php" method="post" id="form_settings">

	<div class="inline-form action-bar">
		<h2><?php echo L_CONFIG_VIEW_SKIN_SELECT ?> </h2>
		<p><?php echo L_CONFIG_VIEW_PLUXML_RESSOURCES ?></p>
		<input type="submit" value="<?php echo L_CONFIG_THEME_UPDATE ?>" />
		<span class="sml-hide med-show">&nbsp;&nbsp;&nbsp;</span>
		<input onclick="window.location.assign('parametres_edittpl.php');return false" type="submit" value="<?php echo L_CONFIG_VIEW_FILES_EDIT_TITLE ?>" />
	</div>

	<?php eval($plxAdmin->plxPlugins->callHook('AdminThemesDisplayTop')) # Hook Plugins?>

	<div class="scrollable-table">
		<table id="themes-table" class="full-width">
			<thead>
				<tr>
					<th colspan="2"><?php echo L_THEMES ?></th>
					<th style="width: 100%">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php
                if ($plxThemes->aThemes) {
                    $num=0;
                    foreach ($plxThemes->aThemes as $i=>$theme) {
						$id = 'preview-' . $i;
                        echo '<tr>';
                        # radio
                        $checked = $theme==$plxAdmin->aConf['style'] ? ' checked="checked"' : '';
                        echo '<td><input id="' . $id . '"'.$checked.' type="radio" name="style" value="'.$theme.'" /></td>';
                        # img preview
                        echo '<td><label for="' . $id . '">' . $plxThemes->getImgPreview($theme) . '</label></td>';
                        # theme infos
                        echo '<td class="wrap" style="vertical-align:top; padding-top: 1rem;"><div>';
                        if ($aInfos = $plxThemes->getInfos($theme)) {
                            echo '<div><strong>'.$aInfos['title'].'</strong></div>';
                            echo '<div style="margin-top: 2rem;">Version : <strong>'.$aInfos['version'].'</strong>';
                            if(!empty($aInfos['date'])) {
								echo  ' (' . $aInfos['date'] . ')';
							}
                            echo '<br />' . L_PLUGINS_AUTHOR.' : ' . $aInfos['author'];
                            if(!empty($aInfos['site'])) {
								echo ' - <a href="' . $aInfos['site'] . '" target="_blank">' . $aInfos['site'] . '</a>';
							}
                            if(!empty($aInfos['description'])) {
								echo '<br />' . preg_replace('#(https?://[^\s]*)#', '<a href="$1" target="_blank">$1</a>', $aInfos['description']);
							}
							echo '</div>';
                        } else {
                            echo '<div><strong>' . $theme . '</strong></div>';
                        }
                        
                        # lien aide
                        if (is_file(PLX_ROOT . $plxAdmin->aConf['racine_themes'] . $theme . '/lang/' . $plxAdmin->aConf['default_lang'] . '-help.php')) {
                            echo '<a title="' . L_HELP_TITLE . '" href="parametres_help.php?help=theme&amp;page=' . urlencode($theme) . '">' . L_HELP . '</a>';

                        echo '</div></td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="2" class="center">' . L_NONE1 . '</td></tr>';
                }
                ?>
			</tbody>
		</table>
	</div>

	<?php eval($plxAdmin->plxPlugins->callHook('AdminThemesDisplay')) # Hook Plugins?>
	<?php echo plxToken::getTokenPostMethod() ?>

</form>

<?php
# Hook Plugins
eval($plxAdmin->plxPlugins->callHook('AdminThemesDisplayFoot'));
# On inclut le footer
include 'foot.php';
?>

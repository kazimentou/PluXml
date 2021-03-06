<?php

/**
 * Classe plxMotor responsable du traitement global du script
 *
 * @package PLX
 * @author  Anthony GUÉRIN, Florent MONTHEL, Stéphane F, Pedro "P3ter" CADETE
 **/

include_once PLX_CORE . 'lib/class.plx.template.php';

class plxMotor
{
    public const PLX_TEMPLATES = PLX_CORE . 'templates/';
    public const PLX_TEMPLATES_DATA = PLX_ROOT . 'data/templates/';

    # On gère la non régression en cas d'ajout de paramètres sur une version de pluxml déjà installée
    protected const DEFAULT_CONFIG = array(
        'version'           => PLX_VERSION,
        'title'             => 'PluXml',
        'description'       => '',
        'meta_description'  => '',
        'meta_keywords'     => 'cms,xml,pluxml,' . DEFAULT_LANG,
        'timezone'          => 'Europe/Paris',
        'allow_com'         => 1,
        'mod_com'           => 0,
        'mod_art'           => 0,
        'capcha'            => 1,
        'style'             => 'defaut',
        'clef'              => '', # A générer
        'bypage'            => 5,
        'bypage_archives'   => 5,
        'bypage_tags'       => 5,
        'bypage_admin'      => 10,
        'bypage_admin_coms' => 10,
        'bypage_feed'       => 8,
        'tri'               => 'desc',
        'tri_coms'          => 'asc',
        'images_l'          => 800,
        'images_h'          => 600,
        'miniatures_l'      => 200,
        'miniatures_h'      => 100,
        'thumbs'            => 1,
        'medias'            => 'data/medias/',
        'racine_articles'   => 'data/articles/',
        'racine_commentaires' => 'data/commentaires/',
        'racine_statiques'  => 'data/statiques/',
        'racine_themes'     => 'themes/',
        'racine_plugins'    => 'plugins/',
        'custom_admincss_file' => '',
        'homestatic'        => '',
        'urlrewriting'      => 0,
        'gzip'              => 0,
        'feed_chapo'        => 0,
        'feed_footer'       => '',
        'default_lang'      => DEFAULT_LANG,
        'userfolders'       => 0,
        'display_empty_cat' => 0,
        # PluXml 5.1.7 et plus
        'hometemplate'      => 'home.php',
        # PluXml 5.8 et plus
        'enable_rss'        => '1',
        'lostpassword'      => '1',
        'email_method'      => 'sendmail',
        'smtp_server'       => '',
        'smtp_username'     => '',
        'smtp_password'     => '',
        'smtp_port'         => 465,
        'smtp_security'     => 'ssl',
        'smtpOauth2_emailAdress' => '',
        'smtpOauth2_clientId' => '',
        'smtpOauth2_clientSecret' => '',
        'smtpOauth2_refreshToken' => '',
        # PluXml 5.8.3 et plus
        'cleanurl' => 0,
        'thumbnail' => '',
    );

    public $get = false; # Donnees variable GET
    public $racine = false; # Url de PluXml
    public $path_url = false; # chemin de l'url du site
    public $style = false; # Dossier contenant le thème
    public $tri; # Tri d'affichage des articles
    public $tri_coms; # Tri d'affichage des commentaires
    public $bypage = false; # Pagination des articles
    public $page = 1; # Numéro de la page
    public $motif = false; # Motif de recherche
    public $mode = false; # Mode de traitement
    public $template = false; # Template d'affichage
    public $cible = false; # Article, categorie ou page statique cible

    public $activeCats = false; # Liste des categories actives sous la forme 001|002|003 etc
    public $homepageCats = false; # Liste des categories à afficher sur la page d'accueil sous la forme 001|002|003 etc
    public $activeArts = array(); # Tableaux des articles appartenant aux catégories actives

    public $aConf = array(); # Tableau de configuration
    public $aCats = array(); # Tableau de toutes les catégories
    public $aStats = array(); # Tableau de toutes les pages statiques
    public $aTags = array(); # Tableau des tags
    public $aUsers = array(); # Tableau des utilisateurs
    public $aTemplates = null; # Tableau des templates

    public $plxGlob_arts = null; # Objet plxGlob des articles
    public $plxGlob_coms = null; # Objet plxGlob des commentaires
    public $plxRecord_arts = null; # Objet plxRecord des articles
    public $plxRecord_coms = null; # Objet plxRecord des commentaires
    public $plxCapcha = null; # Objet plxCapcha
    public $plxErreur = null; # Objet plxErreur
    public $plxPlugins = null; # Objet plxPlugins

    protected static $instance = null;

    /**
     * Méthode qui se charger de créer le Singleton plxMotor
     *
     * @return  self            return une instance de la classe plxMotor
     * @author  Stephane F
     **/
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new plxMotor(path('XMLFILE_PARAMETERS'));
        }
        return self::$instance;
    }

    /**
     * Constructeur qui initialise certaines variables de classe
     * et qui lance le traitement initial
     *
     * @param   filename    emplacement du fichier XML de configuration
     * @return  null
     * @author  Anthony GUÉRIN, Florent MONTHEL, Stéphane F
     **/
    protected function __construct($filename)
    {

        # On parse le fichier de configuration
        $this->getConfiguration($filename);
        define('PLX_SITE_LANG', $this->aConf['default_lang']);
        # récupération des paramètres dans l'url
        $this->get = plxUtils::getGets();
        # gestion du timezone
        date_default_timezone_set($this->aConf['timezone']);
        # On vérifie s'il faut faire une mise à jour
        if (
            empty($this->aConf['version']) or
            (version_compare($this->aConf['version'], PLX_VERSION, '<') and !defined('PLX_UPDATER'))
        ) {
            header('Location: ' . PLX_ROOT . 'update/index.php');
            exit;
        }

        # Chargement des variables
        $this->style = $this->aConf['style'];
        $this->racine = $this->aConf['racine'];
        $this->bypage = $this->aConf['bypage'];
        $this->tri = $this->aConf['tri'];
        $this->tri_coms = $this->aConf['tri_coms'];
        # On récupère le chemin de l'url
        $var = parse_url($this->racine);
        $this->path_url = str_replace(ltrim($var['path'], '\/'), '', ltrim($_SERVER['REQUEST_URI'], '\/'));

        # Traitement des plugins
        # Détermination du fichier de langue (nb: la langue peut être modifiée par plugins via $_SESSION['lang'])
        $context = defined('PLX_ADMIN') ? 'admin_lang' : 'lang';
        $lang = isset($_SESSION[$context]) ? $_SESSION[$context] : $this->aConf['default_lang'];
        #--
        $this->plxPlugins = new plxPlugins($lang);
        $this->plxPlugins->loadPlugins();
        # Hook plugins
        eval($this->plxPlugins->callHook('plxMotorConstructLoadPlugins'));
        # Traitement sur les répertoires des articles et des commentaires
        $this->plxGlob_arts = plxGlob::getInstance(PLX_ROOT . $this->aConf['racine_articles'], false, true, 'arts');
        $this->plxGlob_coms = plxGlob::getInstance(PLX_ROOT . $this->aConf['racine_commentaires']);
        # Récupération des données dans les autres fichiers xml
        $this->getCategories(path('XMLFILE_CATEGORIES'));
        $this->getStatiques(path('XMLFILE_STATICS'));
        $this->getTags(path('XMLFILE_TAGS'));
        $this->getUsers(path('XMLFILE_USERS'));
        # Récuperation des articles appartenant aux catégories actives
        $this->getActiveArts();
        # Hook plugins
        eval($this->plxPlugins->callHook('plxMotorConstruct'));
        # Get templates from core/templates and data/templates
        $this->getTemplates(self::PLX_TEMPLATES);
        $this->getTemplates(self::PLX_TEMPLATES_DATA);
    }

    /**
     * Méthode qui effectue une analyse de la situation et détermine
     * le mode à appliquer. Cette méthode alimente ensuite les variables
     * de classe adéquates
     *
     * @return  null
     * @author  Anthony GUÉRIN, Florent MONTHEL, Stéphane F
     **/
    public function prechauffage()
    {

        # Hook plugins
        if (eval($this->plxPlugins->callHook('plxMotorPreChauffageBegin'))) {
            return;
        }

        if (!empty($this->get) and !preg_match('#^(?:blog|article\d{1,4}/|static\d{1,3}/|categorie\d{1,3}/|user\d{1,3}|archives/\d{4}(?:/\d{2})?|tag/\w|page\d|preview|telechargement|download)#', $this->get)) {
            $this->get = '';
        }

        if (!$this->get and $this->aConf['homestatic']!='' and isset($this->aStats[$this->aConf['homestatic']]) and $this->aStats[$this->aConf['homestatic']]['active']) {
            $this->mode = 'static'; # Mode static
            $this->cible = $this->aConf['homestatic'];
            $this->template = $this->aStats[ $this->cible ]['template'];
        } elseif (empty($this->get)
                or preg_match('#^(blog|blog\/page\d*|\/?page\d*)$#', $this->get)
                and !preg_match('#^(?:article|static|categorie|archives|tag|preview|telechargement|download)[\b\d/]+#', $this->get)
            ) {
            $this->mode = 'home';
            $this->template = $this->aConf['hometemplate'];
            # On regarde si on a des articles en mode "home"
            if ($this->plxGlob_arts->query('#^\d{4}\.(home[0-9,]*)\.\d{3}\.\d{12}\.[\w-]+\.xml$#')) {
                $this->motif = '#^\d{4}.(home[0-9,]*).\d{3}.\d{12}.[\w-]+.xml$#';
            } else { # Sinon on recupere tous les articles
                $this->motif = '#^\d{4}.(?:\d|,)*(?:' . $this->homepageCats . ')(?:\d|,)*.\d{3}.\d{12}.[\w-]+.xml$#';
            }
        } elseif ($this->get and preg_match('#^article(\d+)\/?([\w-]+)?#', $this->get, $capture)) {
            $this->mode = 'article'; # Mode article
            $this->template = 'article.php';
            $this->cible = str_pad($capture[1], 4, '0', STR_PAD_LEFT); # On complete sur 4 caracteres
            $this->motif = '#^' . $this->cible . '.(?:\d|home|,)*(?:' . $this->activeCats . '|home)(?:\d|home|,)*.\d{3}.\d{12}.[\w-]+.xml$#'; # Motif de recherche
            if ($this->getArticles()) {
                # Redirection 301
                if (!isset($capture[2]) or $this->plxRecord_arts->f('url')!=$capture[2]) {
                    $this->redir301($this->urlRewrite('?article' . intval($this->cible) . '/' . $this->plxRecord_arts->f('url')));
                }
            } else {
                $this->error404(L_UNKNOWN_ARTICLE);
            }
        } elseif ($this->get and preg_match('#^static(\d+)\/?([\w-]+)?#', $this->get, $capture)) {
            $this->cible = str_pad($capture[1], 3, '0', STR_PAD_LEFT); # On complète sur 3 caractères
            if (!isset($this->aStats[$this->cible]) or !$this->aStats[$this->cible]['active']) {
                $this->error404(L_UNKNOWN_STATIC);
            } else {
                if (!empty($this->aConf['homestatic']) and $capture[1]) {
                    if ($this->aConf['homestatic']==$this->cible) {
                        $this->redir301($this->urlRewrite());
                    }
                }
                if ($this->aStats[$this->cible]['url']==$capture[2]) {
                    $this->mode = 'static'; # Mode static
                    $this->template = $this->aStats[$this->cible]['template'];
                } else {
                    $this->redir301($this->urlRewrite('?static' . intval($this->cible) . '/' . $this->aStats[$this->cible]['url']));
                }
            }
        } elseif ($this->get and preg_match('#^categorie(\d+)\/?([\w-]+)?#', $this->get, $capture)) {
            $this->cible = str_pad($capture[1], 3, '0', STR_PAD_LEFT); # On complete sur 3 caracteres
            if (!empty($this->aCats[$this->cible]) and $this->aCats[$this->cible]['active'] and $this->aCats[$this->cible]['url']==$capture[2]) {
                $this->mode = 'categorie'; # Mode categorie
                $this->motif = '#^\d{4}.((?:\d|home|,)*(?:' . $this->cible . ')(?:\d|home|,)*).\d{3}.\d{12}.[\w-]+.xml$#'; # Motif de recherche
                $this->template = $this->aCats[$this->cible]['template'];
                $this->tri = $this->aCats[$this->cible]['tri']; # Recuperation du tri des articles
                $this->bypage = $this->aCats[$this->cible]['bypage'];
            } elseif (isset($this->aCats[$this->cible])) { # Redirection 301
                if ($this->aCats[$this->cible]['url']!=$capture[2]) {
                    $this->redir301($this->urlRewrite('?categorie' . intval($this->cible) . '/' . $this->aCats[$this->cible]['url']));
                }
            } else {
                $this->error404(L_UNKNOWN_CATEGORY);
            }
        } elseif ($this->get and preg_match('#^user(\d+)\/?([\w-]+)?#', $this->get, $capture)) {
            $this->cible = str_pad($capture[1], 3, '0', STR_PAD_LEFT); # On complete sur 3 caracteres
            if (!empty($this->aUsers[$this->cible]) and $this->aUsers[$this->cible]['active'] and md5($this->aUsers[$this->cible]['name']) == $capture[2]) {
                $this->mode = 'user'; # Mode user
                $this->motif = '#^\d{4}\.(?:\d{3},)*(?:home|\d{3})(?:,\d{3})*\.' . $this->cible . '\.\d{12}\.[\w-]+\.xml$#'; # Motif de recherche
                $this->template = 'user.php';
            // $this->tri = $this->aCats[$this->cible]['tri']; # Recuperation du tri des articles
            } elseif (isset($this->aUser[$this->cible])) { # Redirection 301
                if ($this->aCats[$this->cible]['url']!=$capture[2]) {
                    $this->redir301($this->urlRewrite('?user' . intval($this->cible) . '/' . $this->aCats[$this->cible]['login']));
                }
            } else {
                $this->error404(L_UNKNOWN_USER);
            }
        } elseif ($this->get and preg_match('#^archives\/(\d{4})[\/]?(\d{2})?[\/]?(\d{2})?#', $this->get, $capture)) {
            $this->mode = 'archives';
            $this->template = 'archives.php';
            $this->bypage = $this->aConf['bypage_archives'];
            $this->cible = $search = $capture[1];
            if (!empty($capture[2])) {
                $this->cible = ($search .= $capture[2]);
            } else {
                $search .= '\d{2}';
            }
            if (!empty($capture[3])) {
                $search .= $capture[3];
            } else {
                $search .= '\d{2}';
            }
            $this->motif = '#^\d{4}.(?:\d|home|,)*(?:' . $this->activeCats . '|home)(?:\d|home|,)*.\d{3}.' . $search . '\d{4}.[\w-]+.xml$#';
        } elseif ($this->get and preg_match('#^tag\/([\w-]+)#', $this->get, $capture)) {
            $this->cible = $capture[1];
            $ids = array();
            $datetime = date('YmdHi');
            foreach ($this->aTags as $idart => $tag) {
                if ($tag['date']<=$datetime) {
                    $tags = array_map("trim", explode(',', $tag['tags']));
                    $tagUrls = array_map(array('plxUtils', 'urlify'), $tags);
                    if (in_array($this->cible, $tagUrls)) {
                        if (!isset($ids[$idart])) {
                            $ids[$idart] = $idart;
                        }
                        if (!isset($this->cibleName)) {
                            $key = array_search($this->cible, $tagUrls);
                            $this->cibleName=$tags[$key];
                        }
                    }
                }
            }
            if (sizeof($ids)>0) {
                $this->mode = 'tags'; # Affichage en mode home
                $this->template = 'tags.php';
                $this->motif = '#(' . implode('|', $ids) . ').(?:\d|home|,)*(?:' . $this->activeCats . '|home)(?:\d|home|,)*.\d{3}.\d{12}.[\w-]+.xml$#';
                $this->bypage = $this->aConf['bypage_tags']; # Nombre d'article par page
            } else {
                $this->error404(L_ARTICLE_NO_TAG);
            }
        } elseif ($this->get and preg_match('#^preview\/?#', $this->get) and isset($_SESSION['preview'])) {
            $this->mode = 'preview';
        } elseif ($this->get and preg_match('#^(telechargement|download)\/(.+)$#', $this->get, $capture)) {
            if ($this->sendTelechargement($capture[2])) {
                $this->mode = 'telechargement'; # Mode telechargement
                $this->cible = $capture[2];
            } else {
                $this->error404(L_DOCUMENT_NOT_FOUND);
            }
        } else {
            $this->error404(L_ERR_PAGE_NOT_FOUND);
        }

        # On vérifie l'existence du template
        $filename = $this->style . '/' . $this->template;
        if (!file_exists(PLX_ROOT . $this->aConf['racine_themes'] . $filename)) {
            $this->error404(L_ERR_FILE_NOTFOUND . ' ( <i>' . $filename . '</i> )');
        }

        # Hook plugins
        eval($this->plxPlugins->callHook('plxMotorPreChauffageEnd'));
    }

    /**
     * Méthode qui fait une redirection de type 301
     *
     * @return  null
     * @author  Stephane F
     **/
    public function redir301($url)
    {
        # Hook plugins
        eval($this->plxPlugins->callHook('plxMotorRedir301'));
        # Redirection 301
        header('Status: 301 Moved Permanently', false, 301);
        header('Location: ' . $url);
        exit();
    }

    /**
     * Méthode qui retourne une erreur 404 Document non trouvé
     *
     * @return  null
     * @author  Stephane F
     **/
    public function error404($msg)
    {
        header("Status: 404 Not Found");
        header("HTTP/1.0 404 Not Found");
        $this->plxErreur = new plxErreur($msg);
        $this->mode = 'erreur';
        $this->template = 'erreur.php';
    }

    /**
     * Méthode qui effectue le traitement selon le mode du moteur
     *
     * @return  null
     * @author  Florent MONTHEL, Stephane F
     **/
    public function demarrage()
    {

        # Hook plugins
        if (eval($this->plxPlugins->callHook('plxMotorDemarrageBegin'))) {
            return;
        }

        if (in_array($this->mode, array('home', 'categorie', 'tags', 'user', 'archives'))) {
            $this->getPage(); # Recuperation du numéro de la page courante
            if (!$this->getArticles()) { # Si aucun article
                $this->error404(L_NO_ARTICLE_PAGE);
            }
            $_SESSION['previous'] = array(
               'mode'    => preg_match('#^blog\b#', $this->get) ? 'blog' : $this->mode,
               'cible'  => $this->cible,
               'motif'  => $this->motif,
               'tri'    => $this->tri,
               'bypage' => $this->bypage,
               'artIds' => false, # sera actualisé en mode article
            );
        } elseif ($this->mode == 'article') {

            # On a validé le formulaire commentaire
            if (!empty($_POST) and $this->plxRecord_arts->f('allow_com') and $this->aConf['allow_com']) {
                # On récupère le retour de la création
                $retour = $this->newCommentaire($this->cible, plxUtils::unSlash($_POST));
                # Url de l'article
                $url = $this->urlRewrite('?article' . intval($this->plxRecord_arts->f('numero')) . '/' . $this->plxRecord_arts->f('url'));
                eval($this->plxPlugins->callHook('plxMotorDemarrageNewCommentaire')); # Hook Plugins
                if ($retour[0] == 'c') { # Le commentaire a été publié
                    $_SESSION['msgcom'] = L_COM_PUBLISHED;
                    header('Location: ' . $url . '#' . $retour);
                } elseif ($retour == 'mod') { # Le commentaire est en modération
                    $_SESSION['msgcom'] = L_COM_IN_MODERATION;
                    header('Location: ' . $url . '#form');
                } else {
                    $_SESSION['msgcom'] = $retour;
                    $_SESSION['msg'] = array(
                        'name'      => plxUtils::unSlash($_POST['name']),
                        'site'      => plxUtils::unSlash($_POST['site']),
                        'mail'      => plxUtils::unSlash($_POST['mail']),
                        'content'   => plxUtils::unSlash($_POST['content']),
                        'parent'    => plxUtils::unSlash($_POST['parent']),
                    );
                    eval($this->plxPlugins->callHook('plxMotorDemarrageCommentSessionMessage')); # Hook Plugins
                    header('Location: ' . $url . '#form');
                }
                exit;
            }

            # Récupération des commentaires
            $this->getCommentaires('#^' . $this->cible . '.\d{10}-\d+.xml$#', $this->tri_coms);
            $this->template=$this->plxRecord_arts->f('template');
            if ($this->aConf['capcha']) {
                $this->plxCapcha = new plxCapcha();
            } # Création objet captcha

            # Gestion des articles précédent, suivant, dans le mode précèdent (home, categorie, archives, tags)
            if (!empty($_SESSION['previous']) and isset($_SESSION['previous']['motif'])) {
                # On récupère un tableau indexé des articles
                if (empty($_SESSION['previous']['artIds'])) {
                    # On récupère tous les ids d'articles de la page précèdente
                    $aFiles = $this->plxGlob_arts->query($_SESSION['previous']['motif'], 'art', $_SESSION['previous']['tri'], 0, false, 'before');
                    $_SESSION['previous']['artIds'] = array_map(
                        function ($item) {
                            return preg_replace('#^_?(\d+).*#', '$1', $item);
                        },
                        $aFiles
                    );
                }

                $_SESSION['previous']['buttons'] = false;
                $key = array_search($this->cible, $_SESSION['previous']['artIds']);
                if (is_integer($key)) {
                    if ($key > 0) {
                        # les tris avec plxGlob::query() sont inutiles car on retourne un tableau avec un seul élément
                        if ($key > 1) {
                            $filenames = $this->plxGlob_arts->query('#^_?' . $_SESSION['previous']['artIds'][0] . '\.#', 'art');
                            $buttons['first'] = $filenames[0];
                        }

                        $filenames = $this->plxGlob_arts->query('#^_?' . $_SESSION['previous']['artIds'][$key - 1] . '\.#', 'art');
                        $buttons['prev'] = $filenames[0];
                    }

                    $lastKey = count($_SESSION['previous']['artIds']) - 1;
                    if ($key < $lastKey) {
                        $filenames = $this->plxGlob_arts->query('#^_?' . $_SESSION['previous']['artIds'][$key + 1] . '\.#', 'art');
                        $buttons['next'] = $filenames[0];

                        if ($key < $lastKey - 1) {
                            $filenames = $this->plxGlob_arts->query('#^_?' . $_SESSION['previous']['artIds'][$lastKey] . '\.#', 'art');
                            $buttons['last'] = $filenames[0];
                        }
                    }
                    $_SESSION['previous']['position'] = $key + 1;
                    $_SESSION['previous']['buttons'] = $buttons;
                } else {
                    $_SESSION['previous'] = false;
                }
            }
        } elseif ($this->mode == 'preview') {
            $this->mode='article';
            $this->plxRecord_arts = new plxRecord($_SESSION['preview']);
            $this->template=$this->plxRecord_arts->f('template');
            if ($this->aConf['capcha']) {
                $this->plxCapcha = new plxCapcha();
            } # Création objet captcha
        }

        # Hook plugins
        eval($this->plxPlugins->callHook('plxMotorDemarrageEnd'));
    }

    /**
     * Vérifie si le thème choisi existe.
     * Sinon, on se rabat sur un autre thème si possible
     *
     * @author J.P. Pourrez "bazooka07"
     * */
    private function _checkStyle()
    {
        $themesRoot = PLX_ROOT . $this->aConf['racine_themes'];
        if (
            isset($this->aConf['style']) and
            is_dir($themesRoot . $this->aConf['style'])
        ) {
            # Tout baigne
            return;
        }

        $oldStyle = plxUtils::getValue($this->aConf['style']);
        $this->aConf['style'] = 'defaut';
        if (is_dir($themesRoot . $this->aConf['style'])) {
            # On se replie sur le thème par défaut
            return;
        }

        $folders = glob($themesRoot . '*', GLOB_ONLYDIR);
        $this->aConf['style'] = !empty($folders) ? basename($folders[0]) : $oldStyle;
    }

    /**
     * Méthode qui parse le fichier de configuration et alimente
     * le tableau aConf
     *
     * @param   filename    emplacement du fichier XML de configuration
     * @return  null
     * @author  Anthony GUÉRIN, Florent MONTHEL, Stéphane F
     **/
    public function getConfiguration($filename)
    {

        # valeurs par défaut si paramètres absents dans le fichier de configuration.
        $this->aConf = self::DEFAULT_CONFIG;

        # Certaines options sont actives par défaut
        foreach (array(
            'userfolders',
            'thumbs',
            'enable_rss_comment',
            'allow_com',
            'enable_rss',
            'enable_rss_comment',
            'capcha',
            'lostpassword',
        ) as $k) {
            $this->aConf[$k] = 1;
        }

        # Certaines options sont désactivées ou nulles par défaut
        foreach (array(
            'meta_description',
            'meta_keywords',
            'custom_admincss_file',
            'description',
            'meta_description',
            'meta_keywords',
            'homestatic',
            'custom_admincss_file',
            'smtp_server',
            'smtp_username',
            'smtp_password',
            'smtpOauth2_emailAdress',
            'smtpOauth2_clientId',
            'smtpOauth2_clientSecret',
            'smtpOauth2_refreshToken',
            # en principe, valeur égale à 0 ou à false pour les champs ci-dessous
            'mod_art',
            'display_empty_cat',
            'mod_com',
            'mod_art',
            'thumbs',
            'urlrewriting',
            'gzip',
            'feed_chapo',
            'feed_footer',
            'userfolders',
            'display_empty_cat',
        ) as $k) {
            $this->aConf[$k] = '';
        }

        # Mise en place du parseur XML
        $data = file_get_contents($filename);
        $parser = xml_parser_create(PLX_CHARSET);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parse_into_struct($parser, $data, $values, $iTags);
        xml_parser_free($parser);
        # On verifie qu'il existe des tags "parametre"
        foreach ($values as $infos) {
            if ($infos['tag'] == 'parametre' and isset($infos['attributes']) and isset($infos['attributes']['name'])) {
                $param = $infos['attributes']['name'];
                $this->aConf[$param] = plxUtils::getValue($infos['value']);
            }
        }

        # détermination automatique de la racine du site
        $this->aConf['racine'] = plxUtils::getRacine();

        # On gère la non régression en cas d'ajout de paramètres sur une version de pluxml déjà installée
        $dataRoot = preg_replace('#^([^/]+)/.*#', '$1/', PLX_CONFIG_PATH);
        foreach (array(
            'tri_coms'              => $this->aConf['tri'],
            'timezone'              => @date_default_timezone_get(),
            'medias'                => $dataRoot . 'images/',
            'racine_articles'       => $dataRoot . 'articles/',
            'racine_commentaires'   => $dataRoot . 'commentaires/',
            'racine_statiques'      => $dataRoot . 'statiques/',
            'version'               => PLX_VERSION,
            'default_lang'          => DEFAULT_LANG,
            # 'clef'                    => plxUtils::charAleatoire(15),
        ) as $param=>$value) {
            if (!isset($this->aConf[$param])) {
                $this->aConf[$param] = $value;
            }
        }

        # On vérifie que le thème est valide
        $this->_checkStyle();

        # On vérifie qu'on a un fichier .htaccess si redirection du site et serveur Apache
        if (preg_match('#\bapache#i', $_SERVER['SERVER_SOFTWARE']) and !file_exists(PLX_ROOT . '.htaccess')) {
            $this->aConf['urlrewriting'] = false;
        }

        if (!defined('PLX_PLUGINS')) {
            define('PLX_PLUGINS', PLX_ROOT . $this->aConf['racine_plugins']);
        }

        if (!defined('PLX_PLUGINS_CSS_PATH')) {
            define('PLX_PLUGINS_CSS_PATH', preg_replace('@^([^/]+/).*@', '$1', $this->aConf['medias']));
        }
    }

    /**
     * Méthode qui parse le fichier des catégories et alimente le tableau aCats.
     *
     * N.B. : Les balises <statique> ont des enfants et des attributs.
     * @param   filename    emplacement du fichier XML des catégories
     * @return  null
     * @author  Stéphane F
     **/
    public function getCategories($filename)
    {
        if (!is_file($filename)) {
            return;
        }

        # Mise en place du parseur XML
        $data = file_get_contents($filename);
        $parser = xml_parser_create(PLX_CHARSET);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parse_into_struct($parser, $data, $values, $iTags);
        xml_parser_free($parser);
        if (!isset($iTags['categorie'])) {
            return;
        }

        # categorie pour articles non classés
        $activeCats = array('000');
        $homepageCats = array('000');

        $items = array_values(array_filter(
            $values,
            function ($value) {
                return (
                    $value['tag'] == 'categorie' and
                    $value['type'] == 'open' and
                    isset($value['attributes']) and
                    !empty($value['attributes']['number'])
                );
            }
        ));
        $children = array_keys($iTags);
        unset($children['document']);
        unset($children['categorie']);
        foreach ($items as $i=>$infos) {
            # number, active, homepage, tri, bypage, menu, url, template
            $item = array_merge(
                array(
                    'active'    => '1' ,
                    'homepage'  => '1' ,
                    'tri'       => 'desc' ,
                    'menu'      => 'oui' ,
                    'template'  => 'categorie.php',
                ),
                $infos['attributes']
            );

            $id = str_pad($item['number'], 3, '0', STR_PAD_LEFT);
            unset($item['number']);

            # Children for this tag
            foreach ($children as $child) {
                $item[$child] = plxUtils::getTagIndexValue($iTags[$child], $values, $i);
            }

            #for missing children. May have empty value
            foreach (array(
                'bypage',
                'description',
                'meta_description',
                'meta_keywords',
                'title_htmltag',
                'thumbnail',
                'thumbnail_alt',
                'thumbnail_title',
            ) as $f) {
                if (!isset($item[$f])) {
                    $item[$f] = '';
                }
            }

            # for these missing children, value is required
            foreach (array('name', 'url', ) as $f) {
                if (!isset($item[$f])) {
                    $item[$f] = 'category-' . $i;
                }
            }

            if (!empty($item['active'])) {
                $activeCats[] = $id;
                if (!empty($item['homepage'])) {
                    $homepageCats[] = $id;
                }
            }

            # Get count of articles for this category
            $motif = '#^\d{4}\.(?:home,|\d{3},)*' . $id . '(?:,\d{3})*\.\d{3}\.\d{12}\.[\w-]+\.xml$#';
            $arts = $this->plxGlob_arts->query($motif, 'art', '', 0, false, 'before');
            $item['articles'] = !empty($arts) ? sizeof($arts) : 0;

            $this->aCats[$id] = $item;
            # Hook plugins
            eval($this->plxPlugins->callHook('plxMotorGetCategories'));
        }

        $this->homepageCats = implode('|', $homepageCats);
        $this->activeCats = implode('|', $activeCats);
    }

    /**
     * Méthode qui parse le fichier des pages statiques et alimente le tableau aStats.
     *
     * N.B. : Les balises <statique> ont des enfants et des attributs.
     * @param   filename    emplacement du fichier XML des pages statiques
     * @return  null
     * @author  Stéphane F
     **/
    public function getStatiques($filename)
    {
        if (!is_file($filename)) {
            return;
        }

        # Mise en place du parseur XML
        $data = file_get_contents($filename);
        $parser = xml_parser_create(PLX_CHARSET);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parse_into_struct($parser, $data, $values, $iTags);
        xml_parser_free($parser);
        if (!isset($iTags['statique']) or !isset($iTags['name'])) {
            return;
        }

        $items = array_values(array_filter(
            $values,
            function ($value) {
                return (
                    $value['tag'] == 'statique' and
                    $value['type'] == 'open' and
                    isset($value['attributes']) and
                    !empty($value['attributes']['number'])
                );
            }
        ));
        $children = array_keys($iTags);
        unset($children['document']);
        unset($children['statique']);

        foreach ($items as $i=>$infos) {
            $item = array_merge(
                array(
                    'active'    => '1',
                    'menu'      => 'oui',
                    'template'  => 'static.php',
                ),
                $infos['attributes']
            );

            $id = str_pad($item['number'], 3, '0', STR_PAD_LEFT);
            unset($item['number']);

            # Children for this tag
            foreach ($children as $child) {
                $item[$child] = plxUtils::getTagIndexValue($iTags[$child], $values, $i);
            }

            #for missing children. May have empty value
            foreach (array(
                'group',
                'meta_description',
                'meta_keywords',
                'title_htmltag',
            ) as $f) {
                if (!isset($item[$f])) {
                    $item[$f] = '';
                }
            }

            # for these missing children, value is required
            foreach (array('name', 'url', ) as $f) {
                if (!isset($item[$f])) {
                    $item[$f] = 'statique-' . $i;
                }
            }
            if (empty($item['date_creation'])) {
                $item['date_creation'] = date('YmdHi');
            }
            if (empty($item['date_update'])) {
                $item['date_update'] = $item['date_creation'];
            }

            $filename = PLX_ROOT . $this->aConf['racine_statiques'] . $id . '.' . $item['url'] . '.php';
            $item['readable'] = is_readable($filename) ? 1 : 0;

            $this->aStats[$id] = $item;

            # Hook plugins
            eval($this->plxPlugins->callHook('plxMotorGetStatiques'));
        }
    }

    /**
     * Méthode qui parse le fichier des utilisateurs
     *
     * @param   filename    emplacement du fichier XML des passwd
     * @return  array       tableau des utilisateurs
     * @author  Stephane F
     **/
    public function getUsers($filename)
    {
        if (!is_file($filename)) {
            return;
        }

        # Mise en place du parseur XML
        $data = file_get_contents($filename);
        $parser = xml_parser_create(PLX_CHARSET);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parse_into_struct($parser, $data, $values, $iTags);
        xml_parser_free($parser);
        if (!isset($iTags['user']) or !isset($iTags['login'])) {
            return;
        }

        $children = array_keys($iTags);
        unset($children['document']);
        unset($children['user']);
        $nb = sizeof($iTags['login']);
        $step = ceil(sizeof($iTags['user'])/$nb);
        # On boucle sur $nb
        for ($i = 0; $i < $nb; $i++) {
            $node = $values[$iTags['user'][$i * $step]];
            $user = $node['attributes'];
            if (!isset($user['number'])) {
                # user w/o id
                continue;
            }
            $userId = $user['number'];
            unset($user['number']);

            foreach ($children as $child) {
                $user[$child] = plxUtils::getTagIndexValue($iTags[$child], $values, $i);
            }

            if (!isset($user['lang'])) {
                $user['lang'] = $this->aConf['default_lang'];
            }

            if (empty($user['delete']) and !empty($user['active'])) {
                # Recupération du nombre d'articles publiés de l'utilisateur et triés par date de publication
                $motif = '#^\d{4}\.(?:\d{3},)*(?:home|\d{3})(?:,\d{3})*\.' . $userId . '\.\d{12}\.[\w-]+\.xml$#';
                $arts = $this->plxGlob_arts->query($motif, 'art', 'desc', 0, false, 'before');
                $user['articles'] = $arts ? sizeof($arts) : 0;
                $user['last_art_published'] = $arts ? preg_replace('#.*\.(\d{12})\..*\.xml$#', '$1', $arts[0]) : '';
            }

            $this->aUsers[$userId] = $user;

            # Hook plugins
            eval($this->plxPlugins->callHook('plxMotorGetUsers'));
        }
    }

    /**
     * Méthode qui selon le paramètre tri retourne sort ou rsort (tri PHP)
     *
     * @param   tri asc ou desc
     * @return  string
     * @author  Stéphane F.
     **/
    protected function mapTri($tri)
    { /* obsolete ! 2017-12-03 */

        if ($tri=='desc') {
            return 'rsort';
        } elseif ($tri=='asc') {
            return 'sort';
        } elseif ($tri=='alpha') {
            return 'alpha';
        } elseif ($tri=='ralpha') {
            return 'ralpha';
        } elseif ($tri=='random') {
            return 'random';
        } else {
            return 'rsort';
        }
    }

    /**
     * Méthode qui récupère le numéro de la page active
     *
     * @return  null
     * @author  Anthony GUÉRIN, Florent MONTHEL, Stephane F
     **/
    protected function getPage()
    {

        # On check pour avoir le numero de page
        if (preg_match('#page(\d*)#', $this->get, $capture)) {
            $this->page = $capture[1];
        } else {
            $this->page = 1;
        }
    }

    /**
     * Méthode qui récupere la liste des  articles
     *
     * @param   publi   before, after ou all => on récupère tous les fichiers (date) ?
     * @return  boolean vrai si articles trouvés, sinon faux
     * @author  Stéphane F, J.P. Pourrez (bazooka07)
     **/
    public function getArticles($publi='before')
    {
        # valeurs par défaut
        if (empty($this->bypage)) {
            $this->bypage = $this->aConf['bypage'];
        }
        if (empty($this->tri)) {
            $this->tri = $this->aConf['tri'];
        }

        # On calcule la valeur start
        $start = $this->bypage * ($this->page-1);
        # On recupere nos fichiers (tries) selon le motif, la pagination, la date de publication
        if ($aFiles = $this->plxGlob_arts->query($this->motif, 'art', $this->tri, $start, $this->bypage, $publi)) {
            # On analyse tous les fichiers
            $artsList = array();
            foreach ($aFiles as $v) {
                $art = $this->parseArticle(PLX_ROOT . $this->aConf['racine_articles'] . $v);
                if (!empty($art)) {
                    $artsList[] = $art;
                }
            }
            # On stocke les enregistrements dans un objet plxRecord
            $this->plxRecord_arts = new plxRecord($artsList);
            return true;
        }

        $this->plxRecord_arts = false;
        return false;
    }

    /**
     * Méthode qui retourne les informations $output en analysant
     * le nom du fichier de l'article $filename
     *
     * @param   filename    fichier de l'article à traiter
     * @return  array       information à récupérer
     * @author  Stephane F, J.P. Pourrez "bazooka07"
     **/
    public function artInfoFromFilename($filename)
    {

        # On effectue notre capture d'informations
        if (preg_match('#^(_?\d{4})\.((?:\d{3},|draft,)*(?:home|\d{3})(?:,\d{3})*)\.(\d{3})\.(\d{12})\.(.*)\.xml$#', basename($filename), $capture)) {
            $ids = array_merge(array_keys($this->aCats), array('home', 'draft'));
            $artCats = array_filter(
                explode(',', $capture[2]),
                # on vérifie que les catégories de l'article existent
                function ($item) use ($ids) {
                    return in_array($item, $ids);
                }
            );
            # array_filter conserve les anciens index dans $artCats
            if (count($artCats) == 1 and array_values($artCats)[0] == 'draft') {
                $artCats[] = '000';
            }
            return array(
                'artId'     => $capture[1],
                'catId'     => !empty($artCats) ? implode(',', $artCats) : '000',
                'usrId'     => $capture[3],
                'artDate'   => $capture[4],
                'artUrl'    => $capture[5]
            );
        }
        return false;
    }

    /**
     * Méthode qui parse l'article du fichier $filename
     *
     * @param   filename    fichier de l'article à parser
     * @return  array
     * @author  Anthony GUÉRIN, Florent MONTHEL, Stéphane F, J.P. Pourrez (bazooka07)
     **/
    public function parseArticle($filename)
    {

        # Informations obtenues en analysant le nom du fichier
        $tmp = $this->artInfoFromFilename($filename);
        if (!empty($tmp)) {
            # Mise en place du parseur XML
            $data = file_get_contents($filename);
            $parser = xml_parser_create(PLX_CHARSET);
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
            xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
            xml_parse_into_struct($parser, $data, $values, $iTags);
            xml_parser_free($parser);

            $art = array(
                'filename'      => $filename,
                # Recuperation des valeurs de nos champs XML
                'title'             => plxUtils::getTagValue($iTags['title'], $values),
                'allow_com'         => plxUtils::getTagValue($iTags['allow_com'], $values, 0),
                'template'          => plxUtils::getTagValue($iTags['template'], $values, 'article.php'),
                'chapo'             => plxUtils::getTagValue($iTags['chapo'], $values),
                'content'           => plxUtils::getTagValue($iTags['content'], $values),
                'tags'              => plxUtils::getTagValue($iTags['tags'], $values),
                'meta_description'  => plxUtils::getTagValue($iTags['meta_description'], $values),
                'meta_keywords'     => plxUtils::getTagValue($iTags['meta_keywords'], $values),
                'title_htmltag'     => plxUtils::getTagValue($iTags['title_htmltag'], $values),
                'thumbnail'         => plxUtils::getTagValue($iTags['thumbnail'], $values),
                'thumbnail_title'   => plxUtils::getTagValue($iTags['thumbnail_title'], $values),
                'thumbnail_alt'     => plxUtils::getTagValue($iTags['thumbnail_alt'], $values),
                'numero'            => $tmp['artId'],
                'author'            => $tmp['usrId'],
                'categorie'         => $tmp['catId'],
                'url'               => $tmp['artUrl'],
                'date'              => $tmp['artDate'],
                'nb_com'            => $this->getNbCommentaires('#^' . $tmp['artId'] . '.\d{10}.\d+.xml$#'),
                'date_creation'     => plxUtils::getTagValue($iTags['date_creation'], $values, $tmp['artDate']),
                'date_update'       => plxUtils::getTagValue($iTags['date_update'], $values, $tmp['artDate']),
            );

            # Hook plugins
            eval($this->plxPlugins->callHook('plxMotorParseArticle'));

            # On retourne le tableau
            return $art;
        } else {
            # le nom du fichier article est incorrect !!
            if (defined('PLX_ADMIN') and class_exists('plxMsg')) {
                plxMsg::Error('Invalid filename "' . $filename . '" from plxMotor::parseArticle()');
            }
            return false;
        }
    }

    /**
     * Méthode qui retourne le nombre de commentaires respectants le motif $motif et le paramètre $publi
     *
     * @param   motif   motif de recherche des commentaires
     * @param   publi   before, after ou all => on récupère tous les fichiers (date) ?
     * @return  integer
     * @author  Florent MONTHEL
     **/
    public function getNbCommentaires($motif, $publi='before')
    {
        if ($coms = $this->plxGlob_coms->query($motif, 'com', '', 0, false, $publi)) {
            return sizeof($coms);
        } else {
            return 0;
        }
    }

    /**
     * Méthode qui retourne les informations $output en analysant
     * le nom du fichier du commentaire $filename
     *
     * @param   filename    fichier du commentaire à traiter
     * @return  array       information à récupérer
     * @author  Stephane F
     **/
    public function comInfoFromFilename($filename)
    {
        # On effectue notre capture d'informations
        if (preg_match('#([[:punct:]]?)(\d{4}).(\d{10})-(\d+).xml$#', $filename, $capture)) {
            return array(
                'comStatus' => $capture[1],
                'artId'     => $capture[2],
                'comDate'   => plxDate::timestamp2Date($capture[3]),
                'comId'     => $capture[3] . '-' . $capture[4],
                'comIdx'    => $capture[4],

            );
        }
        return false;
    }

    /**
     * Méthode qui parse le commentaire du fichier $filename
     *
     * @param   filename    fichier du commentaire à parser
     * @return  array
     * @author  Florent MONTHEL
     **/
    public function parseCommentaire($filename)
    {

        # Mise en place du parseur XML
        $data = file_get_contents($filename);
        $parser = xml_parser_create(PLX_CHARSET);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parse_into_struct($parser, $data, $values, $iTags);
        xml_parser_free($parser);
        # Recuperation des valeurs de nos champs XML
        $com['author'] = plxUtils::getTagValue($iTags['author'], $values);
        if (isset($iTags['type'])) {
            $com['type'] = plxUtils::getTagValue($iTags['type'], $values, 'normal');
        } else {
            $com['type'] = 'normal';
        }
        $com['ip'] = plxUtils::getTagValue($iTags['ip'], $values);
        $com['mail'] = plxUtils::getTagValue($iTags['mail'], $values);
        $com['site'] = plxUtils::getTagValue($iTags['site'], $values);
        $com['content'] = trim($values[ $iTags['content'][0] ]['value']);
        $com['parent'] = plxUtils::getTagValue($iTags['parent'], $values);
        # Informations obtenues en analysant le nom du fichier
        $tmp = $this->comInfoFromFilename(basename($filename));
        $com['status'] = $tmp['comStatus'];
        $com['numero'] = $tmp['comId'];
        $com['article'] = $tmp['artId'];
        $com['date'] = $tmp['comDate'];
        $com['index'] = $tmp['comIdx'];
        # Hook plugins
        eval($this->plxPlugins->callHook('plxMotorParseCommentaire'));
        # On retourne le tableau
        return $com;
    }

    /**
     * Méthode qui trie récursivement les commentaires d'un article en fonction des parents
     *
     * @return  array   liste des commentaires triés
     * @author  Stéphane F.
     **/
    public function parentChildSort_r($idField, $parentField, $els, $parentID = 0, &$result = array(), &$level = 0)
    {
        foreach ($els as $key => $value) {
            if (intval($value[$parentField]) == $parentID) {
                $value['level'] = $level;
                array_push($result, $value);
                unset($els[$key]);
                $oldParent = $parentID;
                $parentID = $value[$idField];
                $level++;
                $this->parentChildSort_r($idField, $parentField, $els, $parentID, $result, $level);
                $parentID = $oldParent;
                $level--;
            }
        }
        return $result;
    }

    /**
     * Méthode qui enregistre dans un objet plxRecord tous les commentaires
     * respectant le motif $motif et la limite $limite
     *
     * @param   motif   motif de recherche des commentaires
     * @param   ordre   ordre du tri : sort ou rsort
     * @param   start   commencement
     * @param   limite  nombre de commentaires à retourner
     * @param   publi   before, after ou all => on récupère tous les fichiers (date) ?
     * @return  bool    true if there is comments else false
     * @author  Florent MONTHEL, Stephane F, Pedro "P3ter" CADETE
     **/
    public function getCommentaires($motif, $ordre='sort', $start=0, $limite=false, $publi='before')
    {

        # On récupère les fichiers des commentaires
        $aFiles = $this->plxGlob_coms->query($motif, 'com', $ordre, $start, $limite, $publi);
        if ($aFiles) { # On a des fichiers
            foreach ($aFiles as $k=>$v) {
                $array[$k] = $this->parseCommentaire(PLX_ROOT . $this->aConf['racine_commentaires'] . $v);
            }

            # hiérarchisation et indentation des commentaires seulement sur les écrans requis
            if (!preg_match('#comments?\.php$#', basename($_SERVER['SCRIPT_NAME']))) {
                $array = $this->parentChildSort_r('index', 'parent', $array);
            }

            # On stocke les enregistrements dans un objet plxRecord
            $this->plxRecord_coms = new plxRecord($array);
        }

        return !empty($aFiles);
    }

    /**
     *  Méthode qui retourne le prochain id d'un commentaire pour un article précis
     *
     * @param   idArt       id de l'article
     * @return  string      id d'un nouveau commentaire
     * @author  Stephane F.
     **/
    public function nextIdArtComment($idArt)
    {
        $ret = '0';
        if ($dh = opendir(PLX_ROOT . $this->aConf['racine_commentaires'])) {
            $Idxs = array();
            while (false !== ($file = readdir($dh))) {
                if (preg_match("/_?" . $idArt . ".\d+-(\d+).xml/", $file, $capture)) {
                    if ($capture[1] > $ret) {
                        $ret = $capture[1];
                    }
                }
            }
            closedir($dh);
        }
        return $ret+1;
    }

    /**
     * Méthode qui crée un nouveau commentaire pour l'article $artId
     *
     * @param   artId   identifiant de l'article en question
     * @param   content tableau contenant les valeurs du nouveau commentaire
     * @return  string
     * @author  Florent MONTHEL, Stéphane F, J.P. Pourrez
     **/
    public function newCommentaire($artId, $content)
    {

        # Hook plugins
        if (eval($this->plxPlugins->callHook('plxMotorNewCommentaire'))) {
            return;
        }

        if (
            !empty($this->aConf['capcha']) and (
                empty($_SESSION['capcha_token']) or
                empty($_POST['capcha_token']) or
                ($_SESSION['capcha_token'] != $_POST['capcha_token'])
            )
        ) {
            return L_NEWCOMMENT_ERR_ANTISPAM;
        }

        # On vérifie que le capcha est correct
        if ($this->aConf['capcha'] == 0 or $_SESSION['capcha'] == sha1($content['rep'])) {
            if (!empty($content['name']) and !empty($content['content'])) { # Les champs obligatoires sont remplis
                $comment=array();
                $comment['type'] = 'normal';
                $comment['author'] = plxUtils::strCheck(trim($content['name']));
                $comment['content'] = plxUtils::strCheck(trim($content['content']));
                # On vérifie le mail
                $comment['mail'] = (plxUtils::checkMail(trim($content['mail']))) ? trim($content['mail']) : '';
                # On vérifie le site
                $comment['site'] = (plxUtils::checkSite($content['site']) ? $content['site'] : '');
                # On récupère l'adresse IP du posteur
                $comment['ip'] = plxUtils::getIp();
                # index du commentaire
                $idx = $this->nextIdArtComment($artId);
                # Commentaire parent en cas de réponse
                if (isset($content['parent']) and !empty($content['parent'])) {
                    $comment['parent'] = intval($content['parent']);
                } else {
                    $comment['parent'] = '';
                }
                # On génère le nom du fichier
                $time = time();
                if ($this->aConf['mod_com']) { # On modère le commentaire => underscore
                    $comment['filename'] = '_' . $artId . '.' . $time . '-' . $idx . '.xml';
                } else { # On publie le commentaire directement
                    $comment['filename'] = $artId . '.' . $time . '-' . $idx . '.xml';
                }
                # On peut créer le commentaire
                if ($this->addCommentaire($comment)) { # Commentaire OK
                    if ($this->aConf['mod_com']) { # En cours de modération
                        return 'mod';
                    } else { # Commentaire publie directement, on retourne son identifiant
                        return 'c' . $artId . '-' . $idx;
                    }
                } else { # Erreur lors de la création du commentaire
                    return L_NEWCOMMENT_ERR;
                }
            } else { # Erreur de remplissage des champs obligatoires
                return L_NEWCOMMENT_FIELDS_REQUIRED;
            }
        } else { # Erreur de vérification capcha
            return L_NEWCOMMENT_ERR_ANTISPAM;
        }
    }

    /**
     * Méthode qui crée physiquement le fichier XML du commentaire
     *
     * @param   comment array avec les données du commentaire à ajouter
     * @return  boolean
     * @author  Anthony GUÉRIN, Florent MONTHEL et Stéphane F
     **/
    public function addCommentaire($content)
    {
        # Hook plugins
        if (eval($this->plxPlugins->callHook('plxMotorAddCommentaire'))) {
            return;
        }
        # On genere le contenu de notre fichier XML
        $xml = "<?xml version='1.0' encoding='" . PLX_CHARSET . "'?>\n";
        $xml .= "<comment>\n";
        $xml .= "\t<author><![CDATA[" . plxUtils::cdataCheck($content['author']) . "]]></author>\n";
        $xml .= "\t<type>" . $content['type'] . "</type>\n";
        $xml .= "\t<ip>" . $content['ip'] . "</ip>\n";
        $xml .= "\t<mail><![CDATA[" . plxUtils::cdataCheck($content['mail']) . "]]></mail>\n";
        $xml .= "\t<site><![CDATA[" . plxUtils::cdataCheck($content['site']) . "]]></site>\n";
        $xml .= "\t<content><![CDATA[" . plxUtils::cdataCheck($content['content']) . "]]></content>\n";
        $xml .= "\t<parent><![CDATA[" . plxUtils::cdataCheck($content['parent']) . "]]></parent>\n";
        # Hook plugins
        eval($this->plxPlugins->callHook('plxMotorAddCommentaireXml'));
        $xml .= "</comment>\n";
        # On ecrit ce contenu dans notre fichier XML
        return plxUtils::write($xml, PLX_ROOT . $this->aConf['racine_commentaires'] . $content['filename']);
    }

    /**
     * Méthode qui parse le fichier des tags et alimente le tableau aTags.
     *
     * N.B. : Les balises <article> n'ont pas d'enfant.
     * @param   filename    emplacement du fichier XML contenant les tags
     * @return  null
     * @author  Stephane F.
     **/
    public function getTags($filename)
    {
        if (!is_file($filename)) {
            return;
        }

        # Mise en place du parseur XML
        $data = file_get_contents($filename);
        $parser = xml_parser_create(PLX_CHARSET);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parse_into_struct($parser, $data, $values, $iTags);
        xml_parser_free($parser);
        # On verifie qu'il existe des balises "article"
        if (!isset($iTags['article'])) {
            return;
        }

        foreach ($iTags['article'] as $k) {
            if (!empty($values[$k]['value'])) {
                $tag = $values[$k]['attributes'];
                if (!isset($tag['number'])) {
                    continue;
                }
                $artId = $tag['number'];
                unset($tag['number']);
                $tag['tags'] = trim($values[$k]['value']);
            }
            $this->aTags[$artId] = $tag;
        }
    }

    /**
     * Méthode qui alimente le tableau aTemplate
     *
     * @param   string  dossier contenant les templates
     * @return  null
     * @author  Pedro "P3ter" CADETE
     **/
    public function getTemplates($templateFolder)
    {
        if (is_dir($templateFolder)) {
            $files = array_diff(scandir($templateFolder), array('..', '.'));
            if (!empty($files)) {
                foreach ($files as $file) {
                    $this->aTemplates[$file] = new PlxTemplate($templateFolder, $file);
                }
            }
        }
    }

    /**
     * Méthode qui lance le téléchargement d'un document
     *
     * @param   cible   cible de téléchargement cryptée
     * @return  boolean
     * @author  Stephane F. et Florent MONTHEL
     **/
    public function sendTelechargement($cible)
    {

        # On décrypte le nom du fichier
        $file = PLX_ROOT . $this->aConf['medias'] . plxEncrypt::decryptId($cible);
        # Hook plugins
        if (eval($this->plxPlugins->callHook('plxMotorSendDownload'))) {
            return;
        }
        # On lance le téléchargement et on check le répertoire medias
        if (file_exists($file) and preg_match('#^' . str_replace('\\', '#', realpath(PLX_ROOT . $this->aConf['medias']) . '#'), str_replace('\\', '/', realpath($file)))) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/download');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } else { # On retourne false
            return false;
        }
    }

    /**
     * Méthode qui réécrit les urls pour supprimer le ?
     *
     * @param   url     url à réécrire
     * @return  string  url réécrite
     * @author  Stéphane F, J.P. Pourrez
     **/
    public function urlRewrite($url='')
    {

        # On teste si $url est une adresse absolue ou une image embarquée
        if (!empty(trim($url)) and preg_match('@^(?:https?|data):@', $url)) {
            return $url;
        }

        if ($url=='' or $url=='?') {
            return $this->racine;
        }

        $args = parse_url($url);

        if ($this->aConf['urlrewriting']) {
            $new_url = !empty($args['path']) ? strtr($args['path'], array(
                'index.php' => '',
                'feed.php' => 'feed/',
            )) : '';
            if (!empty($args['query'])) {
                $new_url .= $args['query'];
            }
            if (empty($new_url)) {
                $new_url = $this->path_url;
            }
            if (!empty($args['fragment'])) {
                $new_url .= '#' . $args['fragment'];
            }
        } else {
            if (empty($args['path']) and !empty($args['query'])) {
                $args['path'] = 'index.php';
            }
            $new_url  = !empty($args['path']) ? $args['path'] : $this->path_url;
            if (!empty($args['query'])) {
                $new_url .= '?' . $args['query'];
            }
            if (!empty($args['fragment'])) {
                $new_url .= '#' . $args['fragment'];
            }
        }

        return $this->racine . $new_url;
    }

    /**
     * Méthode qui comptabilise le nombre d'articles du site.
     *
     * @param   select  critere de recherche: draft, published, all, n° categories séparés par un |
     * @param   userid  filtre sur les articles d'un utilisateur donné
     * @param   mod     filtre sur les articles en attente de validation
     * @param   publi   selection en fonciton de la date du jour (all, before, after)
     * @return  integer nombre d'articles
     * @scope   global
     * @author  Stephane F
     **/
    public function nbArticles($select='all', $userId='\d{3}', $mod='_?', $publi='all')
    {
        $nb = 0;
        if ($select == 'all') {
            $motif = '[home|draft|0-9,]*';
        } elseif ($select=='published') {
            $motif = '[home|0-9,]*';
        } elseif ($select=='draft') {
            $motif = '[\w,]*[draft][\w,]*';
        } else {
            $motif = $select;
        }

        if ($arts = $this->plxGlob_arts->query('#^' . $mod . '\d{4}.(' . $motif . ').' . $userId . '.\d{12}.[\w-]+.xml$#', 'art', '', 0, false, $publi)) {
            $nb = sizeof($arts);
        }

        return $nb;
    }

    /**
     * Méthode qui comptabilise le nombre de commentaires du site
     *
     * @param   select  critere de recherche des commentaires: all, online, offline
     * @param   publi   type de sélection des commentaires: all, before, after
     * @return  integer nombre de commentaires
     * @scope   global
     * @author  Stephane F
     **/
    public function nbComments($select='online', $publi='all')
    {
        $nb = 0;
        if ($select == 'all') {
            $motif = '#[^[:punct:]?]\d{4}.(.*).xml$#';
        } elseif ($select=='offline') {
            $motif = '#^_\d{4}.(.*).xml$#';
        } elseif ($select=='online') {
            $motif = '#^\d{4}.(.*).xml$#';
        } else {
            $motif = $select;
        }

        if ($coms = $this->plxGlob_coms->query($motif, 'com', '', 0, false, $publi)) {
            $nb = sizeof($coms);
        }

        return $nb;
    }

    /**
     * Méthode qui recherche les articles appartenant aux catégories actives
     *
     * @return  null
     * @scope   global
     * @author  Stéphane F.
     **/
    public function getActiveArts()
    {
        if ($this->plxGlob_arts->aFiles) {
            $datetime=date('YmdHi');
            foreach ($this->plxGlob_arts->aFiles as $filename) {
                if (preg_match('#^(\d{4}).(?:\d|home|,)*(?:' . $this->activeCats . '|home)(?:\d|home|,)*.\d{3}.(\d{12}).[\w-]+.xml$#', $filename, $capture)) {
                    if ($capture[2]<=$datetime) { # on ne prends que les articles publiés
                        $this->activeArts[$capture[1]]=1;
                    }
                }
            }
        }
    }
}

<?php

/**
 * Classe plxGlob responsable de la récupération des fichiers à traiter
 *
 * @package PLX
 * @author	Anthony GUÉRIN, Florent MONTHEL, Amaury Graillat et Stéphane F.
 **/
class plxGlob
{
    public const PATTERNS = array(
        'arts'			=> '#^\D?(\d{4,})\.(?:\w+|\d{3})(?:,\w+|,\d{3})*\.\d{3}\.\d{12}\..*\.xml$#',
        'statiques'		=> '#^(\d{3,})\..*\.php$#',
        'commentaires'	=> '#^\d{4,}\.(?:\d{10,})(?:-\d+)?\.xml$#'
    );
    public $count = 0; # Le nombre de resultats
    public $aFiles = array(); # Tableau des fichiers

    private $dir = false; # Repertoire a checker
    private $onlyfilename = false; # Booleen indiquant si notre resultat sera relatif ou absolu
    private $rep = false; # Boolean pour ne lister que les dossiers

    private static $instance = array();

    /**
     * Constructeur qui initialise les variables de classe
     *
     * @param	dir				repertoire à lire
     * @param	rep				boolean pour ne prendre que les répertoires sans les fichiers
     * @param	onlyfilename	boolean pour ne récupérer que le nom des fichiers sans le chemin
     * @param	type			type de fichier lus (arts ou '')
     * @return	null
     * @author	Anthony GUÉRIN, Florent MONTHEL, Amaury Graillat et Stephane F
     **/
    private function __construct($dir, $rep=false, $onlyfilename=true, $type='')
    {

        # On initialise les variables de classe
        $this->dir = $dir;
        $this->rep = $rep;
        $this->onlyfilename = $onlyfilename;
        $this->initCache($type);
    }

    /**
     * Méthode qui se charger de créer le Singleton plxGlob
     *
     * @param	dir				répertoire à lire
     * @param	rep				boolean pour ne prendre que les répertoires sans les fichiers
     * @param	onlyfilename	boolean pour ne récupérer que le nom des fichiers sans le chemin
     * @param	type			type de fichier lus (arts ou '')
     * @return	objet			return une instance de la classe plxGlob
     * @author	Stephane F
     **/
    public static function getInstance($dir, $rep=false, $onlyfilename=true, $type='')
    {
        $basename = str_replace(PLX_ROOT, '', $dir);
        if (!isset(self::$instance[$basename])) {
            self::$instance[$basename] = new plxGlob($dir, $rep, $onlyfilename, $type);
        }
        return self::$instance[$basename];
    }

    /**
     * Méthode qui se charge de mémoriser le contenu d'un dossier
     *
     * @param	type			type de fichier lus (arts ou '')
     * @return	null
     * @author	Amaury Graillat et Stephane F
     **/
    private function initCache($type='')
    {
        if (is_dir($this->dir)) {
            # On ouvre le repertoire
            if ($dh = opendir($this->dir)) {
                # On recupere le nom du repertoire éventuellement
                $dirname = $this->onlyfilename ? '' : $this->dir;
                # Pour chaque entree du repertoire
                while (false !== ($file = readdir($dh))) {
                    if ($file[0] != '.') {
                        $dir = is_dir($this->dir . '/' . $file);
                        if ($this->rep and $dir) {
                            # On collecte uniquement les dossiers (plugins, themes, ...)
                            $this->aFiles[] = $dirname . $file;
                        } elseif (!$this->rep and !$dir) {
                            # On collecte uniquement les fichiers ( arts, statiques, commentaires, ...)
                            if (array_key_exists($type, self::PATTERNS)) {
                                if (preg_match(self::PATTERNS[$type], $file, $matches)) {
                                    if (!empty($matches[1])) {
                                        # On indexe
                                        $this->aFiles[$matches[1]] = $file;
                                    } else {
                                        # commentaires, ...
                                        $this->aFiles[] = $file;
                                    }
                                }
                            } elseif (!empty($type)) {
                                # $type est un motif de recherche
                                if (preg_match($type, $file, $matches)) {
                                    if (!empty($matches[1])) {
                                        # On indexe
                                        $this->aFiles[$matches[1]] = $file;
                                    } else {
                                        $this->aFiles[] = $file;
                                    }
                                }
                            } else {
                                $this->aFiles[] = $file;
                            }
                        }
                    }
                }
                # On ferme la ressource sur le repertoire
                closedir($dh);
            }
        }
    }

    /**
     * Méthode qui cherche les fichiers correspondants au motif $motif
     *
     * @param	motif			motif de recherche des fichiers sous forme d'expression réguliere
     * @param	type			type de recherche: article ('art'), commentaire ('com') ou autre (''))
     * @param	tri				type de tri (sort, rsort, alpha, ralpha)
     * @param	publi			recherche des fichiers avant ou après la date du jour
     * @return	array ou false
     * @author	Anthony GUÉRIN, Florent MONTHEL et Stephane F, Jean-Pierre Pourrez "bazooka07"
     **/
    private function search($motif, $type='', $tri='', $publi='')
    {
        if (empty($this->aFiles)) {
            $this->count = 0;
            return false;
        }

        # On filtre les fichiers du repertoire
        $resp = array_filter($this->aFiles, function ($item) use ($motif) {
            return preg_match($motif, $item);
        });

        if (empty($type) or $tri == 'random') {
            # Pas de tri
            $this->count = count($resp);
            return $resp;
        }

        # Il faut créer un tableau associatif pour trier
        $array = array();
        foreach ($resp as $file) {
            # On decoupe le nom du fichier
            $parts = explode('.', $file);

            switch ($type) {
                case 'art': # Tri selon les dates de publication (article)
                    # On cree un tableau associatif en choisissant bien nos cles et en verifiant la date de publication
                    $key = ($tri === 'alpha' or $tri === 'ralpha') ? $parts[4] . '~' . $parts[0] : $parts[3] . $parts[0];
                    if (
                        ($publi === 'before' and $parts[3] <= date('YmdHi')) or
                        ($publi === 'after' and $parts[3] >= date('YmdHi')) or
                        $publi === 'all'
                    ) {
                        $array[$key] = $file;
                    }
                    break;
                case 'com':  # Tri selon les dates de publications (commentaire)
                    # On cree un tableau associatif en choisissant bien nos cles et en verifiant la date de publication
                    if (
                        ($publi === 'before' and $parts[1] <= time()) or
                        ($publi === 'after' and $parts[1] >= time()) or
                        $publi === 'all'
                    ) {
                        $key = $parts[1] . $parts[0];
                        $array[$key] = $file;
                    }
                    break;
                default:  # Aucun tri
                    $array[] = $file;
            }
        }

        $this->count = count($array);
        # On retourne le tableau si celui-ci existe
        if ($this->count > 0) {
            return $array;
        } else {
            return false;
        }
    }

    /**
     * Méthode qui retourne un tableau trié, des fichiers correspondants
     * au motif $motif, respectant les différentes limites
     *
     * @param	motif			motif de recherche des fichiers sous forme d'expression régulière
     * @param	type			type de recherche: article ('art'), commentaire ('com') ou autre (''))
     * @param	tri				type de tri (sort, rsort, alpha, random)
     * @param	depart			indice de départ de la sélection
     * @param	limite			nombre d'éléments à sélectionner
     * @param	publi			recherche des fichiers avant ou après la date du jour
     * @return	array ou false
     * @author	Anthony GUÉRIN et Florent MONTHEL
     **/
    public function query($motif, $type='', $tri='', $depart='0', $limite=false, $publi='all')
    {

        # Si on a des résultats
        if ($rs = $this->search($motif, $type, $tri, $publi)) {

            # Ordre de tri du tableau
            if (count($rs) > 1) {
                if ($type != '' and $tri != 'random') {
                    # On trie selon les clés du tableau
                    switch ($tri) {
                        case 'alpha':
                        case 'asc':
                        case 'sort':
                            ksort($rs);
                            break;
                        case 'ralpha':
                        case 'desc':
                        case 'rsort':
                            krsort($rs);
                            break;
                        default:
                    }
                } else {
                    switch ($tri) {
                        case 'random':
                            shuffle($rs);
                            break;
                        case 'alpha':
                        case 'sort':
                            sort($rs);
                            break;
                        case 'ralpha':
                        case 'rsort':
                            rsort($rs);
                            break;
                        default:
                    }
                }
            }

            # On enlève les clés du tableau
            if (!empty($type)) {
                $rs = array_values($rs);
            }
            # On a une limite, on coupe le tableau
            if (is_integer($limite) and is_integer($depart)) {
                return array_slice($rs, $depart, $limite);
            } else {
                return $rs;
            }
        }

        # On retourne une valeur négative
        return false;
    }
}

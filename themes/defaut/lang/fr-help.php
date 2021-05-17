<?php if (!defined('PLX_ROOT')) {
    exit;
} ?>
<div>
	<h2>Préambule</h2>
	<p>Le thème <strong>Default-enhanced</strong> est une version améliorée du thème Defaut livré avec la version 5.8.6 de <a href="https://www.pluxml.org">PluXml</a>.</p>
	<p>Elle ajoute les fonctionnalités suivantes :</p>
	<ul>
		<li>Gestion des notes de bas de page au fil de la rédaction de l'article</li>
		<li>Génération d'une table de matières pour les articles divisés en plusieurs chapitres.</li>
		<li>Retour harmonieux vers le haut de la page</li>
		<li>Amélioration des règles CSS dans le cas d'impression d'un article. En particulier, les fonds de page ne sont pas imprimés.</li>
		<li>Ajout de la police personnalisée Fontello utilisée pour les icônes des flux RSS, des silhouettes utilisateurs dans les commentaires, pour le logo par défaut. Le fichier config.json permet de rajouter facilement des icônes sur le site de <a href="http://fontello.com">Fontello</a></li>
		<li>Factorisation de la couleur dominante</li>
	</ul>
</div>
<div>
	<h2>Notes de bas de page</h2>
	<p>
		Pour ajouter automatiquement des notes en bas de page ou d'article, insérer, au fur et à mesure dans le texte, des liens vers les notes de bas pages comme ceci avec un attribut <em>data-footnote</em>:</p>
	</p>
	<pre><code>&lt;a data-footnote="ma note à ajouter en bas de page">&lt;/a></code></pre>
	<p>La note peut contenir des balises HTML comme des liens vers d'autres sites. Dans ce cas remplacer les guillements par des apostrophes.</p>
	<pre><code>&lt;a data-footnote="Obtenir de l'aide sur le &lt;a href='https://forum.pluxml.org'>forum&lt;/a>.&lt;/a></code></pre>
	<p>Toutes les notes seront rassemblées en bas de page. Des liens réciproques seront générés entre le signet et sa note de bas de page.</p>
</div>
<div>
	<h2>Chapitres</h2>
	<p>Pour un long article, il est possible de le diviser en plusieurs chapitres pour faciliter la navigation pendant la lecture.</p>
	<p>Chaque chapitre sera affiché seul.</p>
	<p>Une table des matières sera généré avant le premier chapitre pour accéder directement à chaque chapitre</p>
	<p>Pour cela, tous les chapitres doivent se succéder et avoir la structure suivante</p>
	<pre><code>&lt;div class="new-page">
	&lt;h2>Titre du chapitre&lt;/h2>
	Contenu du chapitre
&lt;/div>
	</code></pre>
</div>
<div>
	<h2>Couleur dominante</h2>
	<p>Le thème original defaut a une couleur dominante bleue.</p>
	<p>Pour changer cette couleur, modifier la valeur de la variable CSS <strong>--color1</strong> pour la balise &lt;body&gt; au début le fichier <a id="edit-theme" href="<?= PLX_ROOT . $plxAdmin->aConf['racine_themes'] . $page ?>/css/theme.css" target="_blank" title="Afficher le fichier">css/theme.css</a>. Sa valeur par défaut est "<span style="color: #258fd6;">#258fd6</span>".</p>
	<p>Voici une liste de couleurs présentant un contraste supérieur à 4.5:1 avec un fond blanc, conformément au <a href="https://www.w3.org/Translations/WCAG20-fr/#visual-audio-contrast">WCAG AA</a> :</p>
	<ul class="unstyled-list">
<?php
    /*
     * https://webaim.org/resources/contrastchecker/
     * https://www.w3schools.com/colors/colors_2021.asp
     * https://contrast-finder.tanaguru.com/?lang=fr
     * */
    foreach (array(
        'Marigold'			=> 'B65F02', // #FDAC53
        'Cerulean'			=> '4778A9', // #9BB7D4
        'Rust'				=> 'B55A30', // #B55A30
        'Illuminating'		=> '847306', // #F5DF4D
        'French Blue'		=> '0072B5', // #0072B5
        'Green Ash'			=> '338440', // #A0DAA9
        'Burnt Coral'		=> 'DA3725', // #E9897E
        'Mint'				=> '00855D', // #00A170
        'Amethyst Orchid'	=> '8F66A3', // #926AA6
        'Raspberry Sorbet'	=> 'D2386C', // #D2386C
    ) as $name=>$color) {
        ?>
		<li style="color: #<?= $color ?>;"><?= $name ?> (<em>#<?= $color ?></em>)</li>
<?php
    }
?>
	</ul>
	<p>Dans le fichier css/theme.css, décommentez la couleur de votre choix.</p>
<?php
if ($page == $plxAdmin->aConf['style']) {
    ?>
	<form id="edit-tpl" action="parametres_edittpl.php" method="post" style="display: none;">
		<input type="hidden" name="template" value="css/theme.css" />
		<input type="hidden" name="load" value="1" />
		<?= plxToken::getTokenPostMethod() ?>
	</form>
	<script>
		(function() {
			document.getElementById('edit-theme').onclick = function(event) {
				event.preventDefault();
				document.getElementById('edit-tpl').submit();
			}
		})();
	</script>
<?php
}
?>
</div>

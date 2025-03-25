<?php
if(!defined('PLX_ROOT')) {
	exit;
}
?>
	<aside class="aside col med-3">
		<details open>
			<summary><?php $plxShow->lang('LATEST_ARTICLES'); ?></summary>
			<ul class="lastart-list unstyled-list">
				<?php $plxShow->lastArtList('<li><a class="#art_status" href="#art_url" title="#art_title">#art_title</a></li>'); ?>
			</ul>
		</details>
		<details <?= ($plxShow->mode() == 'categorie') ? 'open' : '' ?>>
			<summary><?php $plxShow->lang('CATEGORIES'); ?></summary>
			<ul class="cat-list unstyled-list spacer">
				<?php $plxShow->catList('','<li id="#cat_id"><a class="#cat_status" href="#cat_url" title="#cat_name">#cat_name</a> (#art_nb)</li>'); ?>
			</ul>
		</details>
<?php
if(defined('MULTI_USERS')) {
?>
		<details <?= ($plxShow->mode() == 'user') ? 'open' : '' ?>>
			<summary><?php $plxShow->lang('AUTHORS'); ?></summary>
			<ul class="author-list unstyled-list spacer">
<?php
# Par défaut, tri selon la date de l'article le plus récent de chaque auteur
$plxShow->authorList();
# Sinon tri par name, lastname ou hits
# $plxShow->authorList(plxShow::AUTHOR_PATTERN, false, false, 'hits');
?>
			</ul>
		</details>
<?php
}
?>
		<details open>
			<summary><?php $plxShow->lang('LATEST_COMMENTS'); ?></summary>
			<ul class="lastcom-list unstyled-list">
				<?php $plxShow->lastComList('<li><a href="#com_url">#com_author '.$plxShow->getLang('SAID').' : #com_content(34)</a></li>'); ?>
			</ul>
		</details>
		<details <?= ($plxShow->mode() == 'tags') ? 'open' : '' ?>>
			<summary><?php $plxShow->lang('TAGS'); ?></summary>
			<ul class="tag-list">
				<?php $plxShow->tagList('<li class="tag #tag_size"><a class="#tag_status" href="#tag_url" title="#tag_name">#tag_name</a></li>', 20); ?>
			</ul>
		</details>
<?php
if($plxShow->allowRSS()) {
?>
		<details>
			<summary>RSS</summary>
			<ul class="unstyled-list">
<?php
$plxShow->urlPostsRssFeed('', 'li');
?>
			</ul>
		</details>
<?php
}
?>
		<details <?= ($plxShow->mode() == 'archives') ? 'open' : '' ?>>
			<summary><?php $plxShow->lang('ARCHIVES'); ?></summary>
			<ul class="arch-list unstyled-list spacer">
				<?php $plxShow->archList('<li id="#archives_id"><a class="#archives_status" href="#archives_url" title="#archives_name">#archives_name</a> (#archives_nbart)</li>'); ?>
			</ul>
		</details>
	</aside>

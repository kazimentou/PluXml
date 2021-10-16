<?php if (!defined('PLX_ROOT')) {
    exit;
} ?>

<?php
if ($plxMotor->plxRecord_coms) {
    # On a des commentaires?>
		<h3 id="comments"><?php echo $plxShow->artNbCom(); ?> :</h3>
		<div class="comment">
<?php
    while ($plxShow->plxMotor->plxRecord_coms->loop()) {
        # On boucle sur les commentaires
?>
			<div id="<?php $plxShow->comId(); ?>" class="<?php $plxShow->comLevel(); ?>">
				<div>
				<small>
						<span class="nbcom">#<?= $plxShow->plxMotor->plxRecord_coms->i+1 ?></span>
					<time datetime="<?php $plxShow->comDate('#num_year(4)-#num_month-#num_day #hour:#minute'); ?>"><?php $plxShow->comDate('#day #num_day #month #num_year(4) - #hour:#minute'); ?></time> -
					<?php $plxShow->comAuthor('link'); ?>
					<?php $plxShow->lang('SAID'); ?> :
				</small>
				<blockquote>
					<p class="content_com type-<?php $plxShow->comType(); ?>"><?php $plxShow->comContent(); ?></p>
				</blockquote>
			</div>
<?php
        if ($allowedComs) {
            ?>
				<div>
					<a class="button blue" rel="nofollow" href="<?php $plxShow->artUrl(); ?>#form" data-parent="<?php $plxShow->comId(); ?>"><?php $plxShow->lang('REPLY'); ?></a>
		</div>
<?php
        } ?>
			</div>
<?php
    } ?>
		</div>
<?php
}

if ($allowedComs) {
    # Les commentaires sont autorisés?>
	<div id="comment-wrapper">
		<h3 class="no-print"><span><?php $plxShow->lang('WRITE_A_COMMENT') ?></span><span><?php $plxShow->lang('REPLY_TO'); ?></span> :</h3>
		<div id="id_answer"></div>
	<form id="form" action="<?php $plxShow->artUrl(); ?>#form" method="post">
		<fieldset>
			<div class="grid">
				<div class="col">
					<label for="id_name"><?php $plxShow->lang('NAME') ?>* :</label>
					<input id="id_name" name="name" type="text" size="20" value="<?php $plxShow->comGet('name', ''); ?>" maxlength="30" required="required" />
				</div>
			</div>
			<div class="grid">
				<div class="col lrg-6">
					<label for="id_mail"><?php $plxShow->lang('EMAIL') ?> :</label>
					<input id="id_mail" name="mail" type="text" size="20" value="<?php $plxShow->comGet('mail', ''); ?>" />
				</div>
				<div class="col lrg-6">
					<label for="id_site"><?php $plxShow->lang('WEBSITE') ?> :</label>
					<input id="id_site" name="site" type="text" size="20" value="<?php $plxShow->comGet('site', ''); ?>" />
				</div>
			</div>
			<div class="grid">
				<div class="col">
					<label for="id_content" class="lab_com"><?php $plxShow->lang('COMMENT') ?>* :</label>
					<textarea id="id_content" name="content" cols="35" rows="6" required="required"><?php $plxShow->comGet('content', ''); ?></textarea>
				</div>
			</div>
			<?php $plxShow->comMessage('<p id="com_message" class="#com_class"><strong>#com_message</strong></p>'); ?>
	<?php
    if ($plxShow->plxMotor->aConf['capcha']) {
        ?>
			<div class="grid">
				<div class="col">
						<label for="id_rep"><strong><?php $plxShow->lang('ANTISPAM_WARNING') ?></strong>*</label>
					<?php $plxShow->capchaQ(); ?>
					<input id="id_rep" name="rep" type="text" size="2" maxlength="1" style="width: auto; display: inline;" required="required" />
				</div>
			</div>

	<?php
    } ?>

			<div class="grid">
				<div class="col sml-12">
					<input type="hidden" id="id_parent" name="parent" value="<?php $plxShow->comGet('parent', ''); ?>" />
					<input class="blue" type="submit" value="<?php $plxShow->lang('SEND') ?>" />
				</div>
			</div>

		</fieldset>

	</form>
	</div>
<script>
	(function() {
		const container = document.querySelector('.mode-article .comment');
		if(container == null) {
			return;
		}

		const resetBtn = document.querySelector('input[type="reset"]');
		if(resetBtn == null) {
			return;
		}

		const wrapper = document.getElementById('comment-wrapper');
		const idAnswer = document.getElementById('id_answer');
		const idParent = document.getElementById('id_parent');
		const idContent = document.getElementById('id_content');

		function replyCom(idCom) {
			const el = document.querySelector('#' + idCom + ' > div');
			idAnswer.innerHTML = el.innerHTML;
			wrapper.classList.add('active');
			idParent.value = idCom;
			idContent.focus();
		}
		
		function cancelCom() {
	document.getElementById('id_answer').style.display='none';
	document.getElementById('id_parent').value='';
	document.getElementById('com_message').innerHTML='';
		}
		
		container.addEventListener('click', function(event) {
			if(event.target.hasAttribute('data-parent')) {
				event.preventDefault();
				replyCom(event.target.dataset.parent);
			}
		});
		
		resetBtn.onclick = function() {
			wrapper.classList.remove('active');
			idAnswer.textContent = '';
			idParent.value = '';
		}
		
	})();
</script>

	<?php $plxShow->comFeed('rss', $plxShow->artId(), '<p><a class="rss" href="#feedUrl" title="#feedTitle">#feedName</a></p>'); ?>
<?php
} else {
        ?>
	<p><?php $plxShow->lang('COMMENTS_CLOSED') ?>.</p>
<?php
    } # Fin du if sur l'autorisation des commentaires?>

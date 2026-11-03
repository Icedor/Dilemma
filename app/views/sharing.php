<?php use Dilemma\{Views, Config}; ?>
<?php if ($member && !$debate['hidden'] && in_array($debate['status'], ['draft', 'invited'], true)): ?>
<section class="participant-panel share-panel">
	<h2><?= $t('share_opinion') ?></h2><p><?= $t('share_intro') ?></p>
	<?= Views::form('/api/debates/' . $id . '/invite', 'invitation') ?><?= Views::submit('share_link') ?></form>
	<div class="invitation-result" hidden></div>
	<details class="disclosure"><summary><?= $t('email_invitation') ?></summary>
		<?= Views::form('/api/debates/' . $id . '/invite', 'invitation') ?><?= Views::field('email', 'email', '', 'email', true, 254) ?><?= Views::submit('email_invitation', 'secondary') ?></form>
	</details>
</section>
<?php elseif ($debate['is_public']): ?>
<div class="actions debate-sharing"><button type="button" class="button secondary" data-share="<?= $e(Config::url('/debate/' . $id)) ?>"><?= $t('share') ?></button><button type="button" class="text-button" data-copy="<?= $e(Config::url('/debate/' . $id)) ?>"><?= $t('copy_link') ?></button></div>
<?php endif; ?>

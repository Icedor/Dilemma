<?php use Dilemma\{Views, Config}; ?>
<?php if ($member && $member['left_at'] === null && !$debate['hidden'] && in_array($debate['status'], ['draft', 'invited'], true)): ?>
<section class="participant-panel share-panel">
	<h2><?= $t('share_opinion') ?></h2><p><?= $t('share_intro') ?></p>
	<?= Views::form('/api/debates/' . $id . '/invite', 'invitation', 'class="share-link-form"') ?><?= Views::submit('share_link') ?></form>
	<div class="invitation-result" hidden></div>
	<div class="disclosure-group">
	<details class="disclosure"><summary class="disclosure-control"><span><?= $t('email_invitation') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary>
		<?= Views::form('/api/debates/' . $id . '/invite', 'invitation') ?><?= Views::field('email', 'email', '', 'email', true, 254) ?><?= Views::submit('email_invitation', 'secondary') ?></form>
	</details>
	<details class="disclosure"><summary class="disclosure-control"><span><?= $t('open_mode') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><p><?= $t('open_existing_help') ?></p>
	<?= Views::form('/api/debates/' . $id . '/action') ?><?= Views::hidden('revision', $debate['revision']) ?><?= Views::hidden('action', 'open_listing') ?><?= Views::check('public_consent', 'public_consent', false, true) ?><?= Views::submit('open_listing') ?></form></details>
	</div>
</section>
<?php elseif ($debate['is_public']): ?>
<div class="actions debate-sharing"><button type="button" class="button secondary" data-share="<?= $e(Config::url('/debate/' . $id)) ?>"><?= \Dilemma\Views::icon('share-2') ?><span data-control-label><?= $t('share') ?></span></button><button type="button" class="text-button" data-copy="<?= $e(Config::url('/debate/' . $id)) ?>"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy_link') ?></span></button></div>
<?php if ($member && $member['left_at'] === null && $debate['status'] === 'open'): ?>
<section class="open-listing-controls"><p><?= $t('open_waiting') ?></p>
<details class="disclosure"><summary class="disclosure-control"><span><?= $t('email_invitation') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><?= Views::form('/api/debates/' . $id . '/invite', 'invitation') ?><?= Views::field('email', 'email', '', 'email', true, 254) ?><?= Views::submit('email_invitation', 'secondary') ?></form><div class="invitation-result" hidden></div></details>
<?= Views::form('/api/debates/' . $id . '/action') ?><?= Views::hidden('revision', $debate['revision']) ?><?= Views::hidden('action', 'close_listing') ?><?= Views::submit('close_listing', 'secondary') ?></form></section>
<?php endif; ?>
<?php endif; ?>

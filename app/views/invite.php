<?php use Dilemma\Views; ?>
<section class="invitation-page" id="invitation" data-signed-in="<?= $user ? 'true' : 'false' ?>">
	<header class="invitation-hero">
		<p class="eyebrow"><?= $t('invitation') ?></p>
		<h1 id="invitation-title"><?= $t('invite_headline') ?></h1>
		<p class="invitation-tagline"><?= $t('invite_tagline') ?></p>
	</header>
	<p id="invitation-status" class="invitation-notice" role="status"><?= $t('loading') ?></p>
	<div class="invitation-layout" id="invitation-content" hidden>
		<section id="invitation-context" aria-label="<?= $t('invite_premise') ?>">
			<div class="invitation-premise" hidden><h2><?= $t('invite_premise') ?></h2><p class="user-text" data-invite-thesis></p><p class="user-text muted" data-invite-introduction></p></div>
			<div class="invitation-opinion"><p class="eyebrow"><?= $t('invite_creator_view') ?></p><h2 data-invite-author></h2><blockquote class="user-text" data-invite-stance></blockquote></div>
			<p class="muted" data-invite-visibility></p>
		</section>
		<section class="invitation-response" aria-labelledby="invite-response-title">
			<h2 id="invite-response-title"><?= $t('invite_response_title') ?></h2>
			<p><?= $t('invite_response_intro') ?></p>
			<?php if (!$user): ?>
			<div class="actions" id="invitation-login"><a class="button" href="/login?register=1" data-invitation-login><?= $t('invite_join') ?></a><a class="text-link" href="/login" data-invitation-login><?= $t('login') ?></a></div>
			<?php else: ?>
			<?= Views::form('/api/accept', 'accepted', 'id="invitation-form" hidden') ?>
				<?= Views::hidden('token', '') ?><?= Views::area('stance', 'your_counter_position', '', true) ?>
				<details class="disclosure"><summary><?= $t('optional_details') ?></summary><?= Views::scale() ?></details>
				<?= Views::consent() ?><?= Views::check('notify', 'notify_debate', true) ?><p class="muted"><?= $t('notify_debate_help') ?></p><?= Views::check('accept_terms', 'accept_terms', false, true) ?>
				<p class="muted"><a href="/terms" target="_blank" rel="noopener"><?= $t('terms') ?></a> · <a href="/privacy" target="_blank" rel="noopener"><?= $t('privacy') ?></a></p>
				<div class="actions"><?= Views::submit('accept_invitation') ?><button type="button" class="text-button" data-decline><?= $t('decline') ?></button></div>
			</form>
			<?php endif; ?>
			<ol class="invitation-steps"><li><?= $t('invite_step_opinion') ?></li><li><?= $t('invite_step_connect') ?></li><li><?= $t('invite_step_decide') ?></li></ol>
		</section>
	</div>
	<div id="invitation-locked" class="invitation-empty" hidden><h2><?= $t('invite_private_title') ?></h2><p data-invitation-account-help><?= $t('invite_private_help') ?></p><a class="button" href="/login" data-invitation-login><?= $t('login') ?></a></div>
	<div id="invitation-empty" class="invitation-empty" hidden><h2><?= $t('invite_empty_title') ?></h2><p><?= $t('invite_empty_help') ?></p><a class="button secondary" href="/explore"><?= $t('explore') ?></a></div>
</section>

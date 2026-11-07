<?php use Dilemma\Views; $flow = $debate['workflow']; $current_member = $member && $member['left_at'] === null; ?>
<section class="debate-progress" aria-labelledby="debate-progress-title" data-flow-revision="<?= (int) $debate['revision'] ?>">
	<div class="section-heading"><h2 id="debate-progress-title"><?= $t(!$member && $flow['next_action'] === 'wait_for_decision' ? 'flow_public_decision' : 'flow_' . $flow['next_action']) ?></h2><a href="#arguments"><?= (int) $debate['message_count'] ?> <?= $t('arguments_count') ?></a></div>
	<p data-flow-hint <?= !in_array($flow['next_action'], ['publish_opening', 'publish_argument', 'ask_human'], true) ? 'hidden' : '' ?>><?= $t($flow['next_action'] === 'ask_human' ? 'flow_ask_help' : 'flow_publish_help') ?></p>
	<details class="assessment-disclosure" data-live-assessment <?= !$debate['latest_assessment'] ? 'hidden' : '' ?> <?= $flow['next_action'] === 'ask_human' ? 'open' : '' ?>><summary class="disclosure-control"><span><?= $t('flow_assessment') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><blockquote class="current-assessment"><p><?= $e($debate['latest_assessment']['excerpt'] ?? '') ?></p><a href="/debate/<?= $e($debate['id']) ?>?page=<?= max(1, (int) ceil($debate['message_count'] / 20)) ?>#argument-<?= $e($debate['latest_assessment']['id'] ?? '') ?>"><?= $t('full_argument') ?></a></blockquote></details>
	<ul class="connection-statuses"><?php foreach ($flow['active_participants'] as $person): ?><li><strong><?= $e($person['alias']) ?></strong><span><?= $t($person['ai_connected'] ? 'flow_connected' : ($person['ai_ready'] ? 'flow_ai_ready' : 'flow_not_connected')) ?></span></li><?php endforeach; ?></ul>
	<?php if (!$user): ?><a href="/login" data-auth-return="/debate/<?= $e($debate['id']) ?>#your-decision"><?= $t('login') ?></a><?php endif; ?>
	<div class="debate-update" hidden role="status"><p><?= $t('live_paused') ?></p><button type="button" class="button secondary" data-retry-live><?= $t('live_retry') ?></button></div>
</section>
<?php if ($current_member): ?>
<details class="decision-panel disclosure" id="your-decision" data-live-decision <?= $debate['hidden'] || !in_array($debate['status'], ['active', 'paused', 'awaiting_human'], true) ? 'hidden' : '' ?> <?= $flow['next_action'] === 'ask_human' ? 'open' : '' ?>>
	<summary class="disclosure-control"><span><?= $t('your_decision') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary>
	<div class="decision-options">
		<section><h3><?= $t('decision_continue') ?></h3><p><?= $t('decision_continue_help') ?></p><a href="#connect-ai"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy_setup') ?></span></a></section>
		<section><h3><?= $t('decision_leave') ?></h3><p><?= $t('decision_leave_help') ?></p><?php if ($debate['is_public']): ?><?= $action_form('leave_seat', 'decision_leave_button', 'secondary', 'leave_seat_confirm') ?><?php else: ?><p class="muted"><?= $t('continuation_consent_required') ?></p><?php endif; ?></section>
		<section><h3><?= $t('decision_end') ?></h3><p><?= $t('decision_end_help') ?></p><div class="actions"><?= $action_form('flag', 'decision_convinced', '', 'flag_confirm') ?><?= $action_form('withdraw', 'decision_end_button', 'secondary', 'stop_confirm') ?></div></section>
	</div>
</details>
<?php endif; ?>

<?php
use Dilemma\Views;
use Dilemma\I18n;
use Dilemma\Support;
$member = $debate['viewer_member'];
$id = $debate['id'];
$participants = array_column($debate['participants'], null, 'user_id');
$active = $member && $member['left_at'] === null && in_array($debate['status'], ['active', 'paused', 'awaiting_human'], true);
$current_participants = array_filter($participants, static fn ($p) => $p['left_at'] === null);
$turn = null;
foreach ($current_participants as $participant) if ((int) $participant['side'] === (int) $debate['turn_side']) $turn = $participant;
$action_form = static function (string $action, string $label, string $class = 'secondary', string $confirm = '') use ($id, $debate): string {
	return Views::form('/api/debates/' . $id . '/action', $action === 'flag' ? 'flag' : 'reload', $confirm !== '' ? 'data-confirm="' . $confirm . '"' : '') . Views::hidden('revision', $debate['revision']) . Views::hidden('action', $action) . Views::submit($label, $class) . '</form>';
};
?>
<article class="debate-page" data-debate="<?= $e($id) ?>">
<header class="debate-heading"><div class="debate-heading-meta"><a href="/explore?topic=<?= rawurlencode($debate['topic']) ?>"><?= $e($debate['topic']) ?></a><span class="status status-<?= $e($debate['status']) ?>"><?= $t($debate['status'] === 'open' ? 'looking_for_opponent' : $debate['status']) ?></span><span><?= $t($debate['is_public'] ? 'public' : 'private') ?></span></div><h1><?= $e($debate['title']) ?></h1><?php if ($debate['thesis'] !== $debate['title']): ?><p class="thesis user-text"><?= $e($debate['thesis']) ?></p><?php endif; ?><?php if ($debate['introduction'] !== ''): ?><details class="debate-background"><summary><?= $t('debate_background') ?></summary><p class="user-text introduction"><?= $e($debate['introduction']) ?></p></details><?php endif; ?><?php if ($debate['hidden']): ?><p class="notice"><?= $t('hidden') ?></p><?php endif; ?></header>

<?php if ($debate['flag_user_id']): ?><section class="flag-result"><span class="mini-flag" aria-hidden="true"></span><div><h2><?= $t('flag_title') ?></h2><p><?= $e($participants[$debate['flag_user_id']]['alias'] ?? '') ?></p></div><button class="button secondary" type="button" data-replay-flag><?= $t('replay_flag') ?></button></section><?php elseif ($debate['end_reason'] && !$debate['end_proposed_by']): ?><p class="notice"><?= $t($debate['end_reason']) ?></p><?php endif; ?>
<?= Views::render('debate-flow', compact('debate', 'member', 'action_form', 'user')) ?>
<div class="debate-workspace">
	<div class="debate-main">
		<details class="viewpoints disclosure" <?= (int) $debate['message_count'] === 0 ? 'open' : '' ?>><summary><?= $t('debate_viewpoints') ?></summary><div class="stance-pair"><?php foreach ($current_participants as $participant): ?><section class="stance side-<?= (int) $participant['side'] ?>"><div class="participant-mark" aria-hidden="true"><?= (int) $participant['side'] === 0 ? 'A' : 'B' ?></div><div><?= Views::avatar($participant['avatar']) ?><h2><?php if ($participant['account_status'] !== 'deleted'): ?><a href="/profile/<?= $e($participant['user_id']) ?>"><?= $e($participant['alias']) ?></a><?php else: ?><?= $t('deleted_participant') ?><?php endif; ?></h2><p class="user-text"><?= $e($participant['stance']) ?></p></div></section><?php endforeach; ?></div></details>
		<?= Views::render('debate-record', compact('debate', 'member', 'id', 'user')) ?>
		<?php if ($user): ?><div class="debate-management">
			<?php if ($member): ?><details class="participant-settings"><summary><?= $t('debate_settings') ?></summary>
			<?php if ($active): ?><div class="human-actions"><?php if ($debate['status'] !== 'awaiting_human') echo $action_form($debate['status'] === 'paused' ? 'resume' : 'pause', $debate['status'] === 'paused' ? 'resume' : 'pause'); ?><?= $action_form('stand', 'stand') ?></div>
			<?= Views::form('/api/debates/' . $id . '/action') ?><?= Views::hidden('revision', $debate['revision']) ?><?= Views::hidden('action', 'position') ?><?= Views::scale($debate['left_label'], $debate['right_label']) ?><?= Views::submit('save_position') ?></form>
			<div class="ending-panel"><?php if ($debate['end_proposed_by']): ?><p><?= $t('end_waiting') ?> <strong><?= $t($debate['end_reason']) ?></strong></p><?php if ($debate['end_proposed_by'] !== $user['id']) echo $action_form('accept_end', 'accept_end', '', 'end_confirm'); ?><?= $action_form('cancel_end', 'cancel_end') ?><?php else: ?><?= Views::form('/api/debates/' . $id . '/action') ?><?= Views::hidden('revision', $debate['revision']) ?><?= Views::hidden('action', 'propose_end') ?><label class="field"><span><?= $t('propose_end') ?></span><select name="reason"><?php foreach (['agreement', 'partial_agreement', 'draw', 'disagreement'] as $reason): ?><option value="<?= $reason ?>"><?= $t($reason) ?></option><?php endforeach; ?></select></label><?= Views::submit('propose_end', 'secondary') ?></form><?php endif; ?></div>
			<?php elseif ($member['left_at'] === null && in_array($debate['status'], ['flagged', 'ended'], true)): ?><?= $action_form('archive', 'archive_action') ?><?php endif; ?>
			<?= Views::form('/api/debates/' . $id . '/action') ?><?= Views::hidden('revision', $debate['revision']) ?><?= Views::hidden('action', 'notifications') ?><?= Views::check('notify', 'notify_debate', (bool) $member['notify']) ?><p class="muted"><?= $t('notify_debate_help') ?></p><?= Views::submit('save', 'secondary') ?></form>
			<?= Views::form('/api/debates/' . $id . '/action') ?><?= Views::hidden('revision', $debate['revision']) ?><?= Views::hidden('action', 'consent') ?><?= Views::consent($member) ?><?= Views::submit('save_consent') ?></form></details><?php endif; ?>
			<?php if ($user): ?><details class="report-panel"><summary><?= $t('report') ?></summary><?= Views::form('/api/debates/' . $id . '/report', 'report') ?><?= Views::area('reason', 'reason', '', true, 2000) ?><?= Views::submit('send_report', 'secondary') ?></form></details><?php endif; ?>
		</div><?php endif; ?>
	</div>
	<aside class="debate-sidebar" aria-label="<?= $t('debate_tools') ?>">
	<?php if ($member): ?>
		<?= Views::render('debate-ai', compact('debate', 'user', 'actor')) ?>
	<?php endif; ?>
		<?= Views::render('sharing', compact('member', 'debate', 'id')) ?>
		<?php if ($member): ?><?= Views::render('pwa', []) ?><?php endif; ?>
	</aside>
</div>

</article>
<dialog id="flag-dialog" aria-labelledby="flag-title"><button class="dialog-close" type="button" data-close-dialog aria-label="<?= $t('close') ?>">×</button><div class="flag-scene" aria-hidden="true"><span class="mini-flag"></span></div><h2 id="flag-title"><?= $t('flag_title') ?></h2><p><?= $e($participants[$debate['flag_user_id']]['alias'] ?? $user['alias'] ?? '') ?></p><button type="button" class="button" data-close-dialog><?= $t('close') ?></button></dialog>

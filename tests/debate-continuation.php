<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Dilemma\{Accounts, Arguments, Connections, Db, DebateActions, DebateFlow, Debates, I18n, Moderation, ReadModel, Support, ToolRegistry, Views};

$a = user_fixture('ContA'); $b = user_fixture('ContB'); $c = user_fixture('ContC');
$d = debate_fixture($a['actor'], $b['actor'], false); $id = $d['id'];
$attempts = 0;
Db::transaction(static function () use (&$attempts, $id, $a): void {
	Debates::event($id, $a['actor']['user_id'], 'snapshot_retry_fixture');
	if (++$attempts === 1) { $error = new PDOException('Synthetic snapshot conflict'); $error->errorInfo = ['HY000', 1020, 'Record changed']; throw $error; }
});
check($attempts === 2 && (int) Db::one("SELECT COUNT(*) AS n FROM debate_events WHERE debate_id = ? AND kind = 'snapshot_retry_fixture'", [$id])['n'] === 1, 'Snapshot retry rolls back all partial writes before re-reading state');
$action = static fn ($actor, $name, $extra = []) => DebateActions::apply($actor, $id, $extra + ['action' => $name, 'revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token()]);
rejects(fn () => $action($a['actor'], 'leave_seat', ['confirm' => true]), 422, 'Private discussion cannot become public through a seat change', 'continuation_consent_required');
check(Debates::member($id, $a['actor']['user_id'])['left_at'] === null, 'Rejected seat change leaves participation intact');
$action($b['actor'], 'consent', ['public_consent' => true]);
$ta = actor_token($a['actor'], true, $id); $tb = actor_token($b['actor'], true, $id);
$read = actor_token($a['actor'], false, $id);
check(ReadModel::debate($id, $read['actor'])['workflow']['next_action'] === 'read_only' && !ReadModel::debate($id, $read['actor'])['workflow']['can_publish'], 'Read-only grant never receives a publishing instruction');
$input = ['debate_id' => $id, 'revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token(), 'body' => 'The opposing position warrants a decision.', 'outcome' => 'needs_human'];
rejects(fn () => ToolRegistry::invoke($ta['actor'], 'dilemma_submit_argument', (object) array_replace($input, ['outcome' => 'flag'])), 422, 'Unknown outcome cannot masquerade as a human decision');
Arguments::submit($ta['actor'], $id, $input);
$own_wait = ReadModel::debate($id, $ta['actor'])['workflow'];
$other_wait = ReadModel::debate($id, $tb['actor'])['workflow'];
check($own_wait['next_action'] === 'ask_human' && $own_wait['can_reconsider'] && !$own_wait['keep_waiting'] && !$own_wait['can_publish'], 'Own AI waits for fresh human input without self-resuming or publishing');
check($other_wait['next_action'] === 'wait_for_decision' && $other_wait['keep_waiting'] && !$other_wait['can_reconsider'] && !$other_wait['can_publish'], 'Opposing AI stays waiting during human consideration without gaining publication or resume rights');
$read_wait = ReadModel::debate($id, $read['actor'])['workflow'];
check(!$read_wait['can_reconsider'] && !$read_wait['keep_waiting'], 'Read-only access cannot resume or enter an automatic publishing run');
rejects(fn () => Arguments::submit($tb['actor'], $id, array_replace($input, ['revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token(), 'outcome' => 'argument'])), 409, 'Human decision pause blocks further opposing posts');
rejects(fn () => $action($a['actor'], 'pause'), 409, 'Human decision cannot be bypassed through pause/resume');
$resume = ['revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token()];
$first = DebateFlow::reconsider($ta['actor'], $id, $resume);
$again = DebateFlow::reconsider($ta['actor'], $id, $resume);
check($first['revision'] === $again['revision'] && $again['idempotent_replay'], 'Reconsideration retry is idempotent');
check(Debates::row($id)['turn_side'] === Debates::member($id, $a['actor']['user_id'])['side'], 'New human arguments preserve own turn');
$resumed = ReadModel::debate($id, $ta['actor']);
check($resumed['workflow']['can_publish'] && !$resumed['workflow']['can_reconsider'] && !$resumed['workflow']['keep_waiting'], 'Fresh human reasons restore only own publishing turn');
check(ReadModel::debate($id, $tb['actor'])['workflow']['keep_waiting'], 'Opponent remains waiting while resumed author prepares the new case');
$new_post = Arguments::submit($ta['actor'], $id, ['revision' => (int) $resumed['revision'], 'idempotency_key' => Support::token(), 'body' => 'My human adds a new argument about an inclusive, time-limited trial.', 'reply_to' => []]);
check((bool) $new_post['message_id'] && ReadModel::debate($id, $tb['actor'])['workflow']['can_publish'], 'New human case persists on the site and gives the opponent its next turn');
check(Debates::row($id)['flag_user_id'] === null && (int) Db::one('SELECT COUNT(*) AS n FROM debate_positions WHERE debate_id = ?', [$id])['n'] === 2, 'Resume and new argument never change human opinions or raise a flag');
rejects(fn () => DebateFlow::reconsider($tb['actor'], $id, ['revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token()]), 409, 'Cannot reconsider an active or unrelated side');
$leave = ['action' => 'leave_seat', 'confirm' => true, 'revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token()];
$one = DebateActions::apply($b['actor'], $id, $leave);
// A repeated confirmed request is a receipt lookup, not a second departure.
$two = DebateActions::apply($b['actor'], $id, $leave);
check($two['idempotent_replay'] && $one['revision'] === $two['revision'], 'Confirmed seat departure can be safely retried');
rejects(fn () => $action($b['actor'], 'flag', ['confirm' => true]), 403, 'Former human cannot end the replacement discussion');
rejects(fn () => Debates::claim($b['actor'], $id, ['idempotency_key' => Support::token()]), 403, 'Historical participant cannot duplicate their seat');
$action($a['actor'], 'close_listing');
check(!ReadModel::debate($id, $a['actor'])['is_public'], 'Remaining participant can close replacement listing');
$action($a['actor'], 'open_listing', ['public_consent' => true]);
$action($b['actor'], 'consent', ['public_consent' => false]);
check(Debates::row($id)['status'] === 'draft', 'Former participant can withdraw public consent while replacement is pending');
rejects(fn () => $action($a['actor'], 'open_listing', ['public_consent' => true]), 422, 'Remaining participant cannot override historical publication consent');
$action($b['actor'], 'consent', ['public_consent' => true]);
$inv = Debates::invite($a['actor'], $id, ['idempotency_key' => Support::token()]);
Debates::accept($c['actor'], ['token' => parse_url($inv['invitation_url'], PHP_URL_FRAGMENT), 'idempotency_key' => Support::token(), 'stance' => 'A third human takes the opposing seat.', 'accept_terms' => true, 'processing_consent' => true, 'public_consent' => true]);
check(Debates::member($id, $c['actor']['user_id'])['side'] === 1 && count(ReadModel::participants($id)) === 3, 'A private friend invitation can fill the reopened seat without deleting history');
foreach (['sv', 'en'] as $language) {
	I18n::locale($language); $_GET = [];
	$page = Views::page('/debate/' . $id, null, null);
	check(str_contains($page, 'argument-timeline') && !str_contains($page, 'Authorization: Bearer'), 'Historical participant debate renders publicly in ' . $language);
}
Db::run('UPDATE debates SET hidden = 1 WHERE id = ?', [$id]);
rejects(fn () => $action($a['actor'], 'leave_seat', ['confirm' => true]), 409, 'Moderated debate cannot be reopened via departure');
check(ReadModel::debate($id, $a['actor'])['workflow']['next_action'] === 'unavailable', 'Hidden discussion tells AI to stop');
echo 'SUCCESS: ' . $checks . " continuation checks.\n";

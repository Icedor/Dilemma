<?php
declare(strict_types=1);
namespace Dilemma;

final class DebateFlow
{
	public static function opening(array $row): bool
	{
		return in_array($row['status'], ['draft', 'invited', 'open'], true) && (int) $row['message_count'] === 0;
	}

	public static function state(array $row, array $participants, ?array $actor = null): array
	{
		$member = $row['viewer_member'];
		$write_allowed = !isset($actor['connection_id']) || in_array(Connections::WRITE, explode(' ', $actor['scopes'] ?? ''), true);
		$current = array_values(array_filter($participants, static fn ($p) => $p['left_at'] === null));
		$can = $write_allowed && $member && $member['left_at'] === null && !$row['hidden'] && ($row['status'] === 'active' || self::opening($row)) && (int) $row['turn_side'] === (int) $member['side'];
		$next = match (true) {
			(bool) $row['hidden'] => 'unavailable',
			$member !== null && $member['left_at'] !== null => 'observe',
			!$write_allowed => 'read_only',
			$row['status'] === 'awaiting_human' => $member && $member['agent_state'] === 'needs_human' ? 'ask_human' : 'wait_for_decision',
			(bool) $can => self::opening($row) ? 'publish_opening' : 'publish_argument',
			in_array($row['status'], ['draft', 'invited', 'open'], true) => 'wait_for_opponent',
			$row['status'] === 'active' => 'wait_for_turn',
			$row['status'] === 'paused' => 'paused',
			default => 'finished',
		};
		return ['can_publish' => (bool) $can, 'next_action' => $next, 'decision_url' => Config::url('/debate/' . $row['id'] . '#your-decision'), 'active_participants' => array_map(static fn ($p) => ['user_id' => $p['user_id'], 'alias' => $p['alias'], 'side' => (int) $p['side'], 'ai_connected' => (bool) $p['ai_connected'], 'agent_state' => $p['agent_state']], $current), 'poll_after_seconds' => 20, 'instructions' => AiGuide::RULES];
	}

	public static function reconsider(array $actor, string $id, array $input): array
	{
		$input['debate_id'] = $id;
		return Db::transaction(static function () use ($actor, $id, $input): array {
			Debates::actor($actor, true, $id);
			$row = Debates::row($id, true);
			$member = Debates::member($id, $actor['user_id']);
			if ($receipt = Idempotency::replay($actor['user_id'], 'reconsider', $input)) return $receipt;
			Debates::revision($row, $input);
			if ($row['hidden'] || $member['left_at'] !== null || $row['status'] !== 'awaiting_human' || $member['agent_state'] !== 'needs_human') throw new Failure(409, 'invalid_transition');
			Db::run("UPDATE debate_participants SET agent_state = 'arguing' WHERE debate_id = ? AND user_id = ?", [$id, $actor['user_id']]);
			Db::run("UPDATE debates SET status = 'active', turn_side = ?, revision = revision + 1, updated_at = ? WHERE id = ?", [$member['side'], Support::now(), $id]);
			$event = Debates::event($id, $actor['user_id'], 'agent_reconsidering');
			return Idempotency::save($actor['user_id'], 'reconsider', $input, ['debate_id' => $id, 'status' => 'active', 'revision' => (int) $row['revision'] + 1, 'event_sequence' => $event, 'stored_at' => Support::now()]);
		});
	}
}

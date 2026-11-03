<?php
declare(strict_types=1);
namespace Dilemma;

final class AiWaiting
{
	public static function policy(?array $member): array
	{
		$until = $member['ai_wait_until'] ?? null;
		$enabled = $until !== null && strtotime($until . ' UTC') > time() && $member['left_at'] === null;
		return ['enabled' => $enabled, 'until' => $enabled ? str_replace(' ', 'T', $until) . 'Z' : null, 'poll_after_seconds' => 900];
	}

	public static function update(array $actor, array $input): array
	{
		Debates::human($actor);
		$id = Support::uuid($input['debate_id'] ?? '');
		if (!is_bool($input['enabled'] ?? null)) throw new Failure(422, 'invalid_field');
		return Db::transaction(static function () use ($actor, $input, $id): array {
			Debates::actor($actor, true, $id);
			$row = Debates::row($id, true);
			$member = Debates::member($id, $actor['user_id']);
			if ($input['enabled'] && ($row['hidden'] || $member['left_at'] !== null || !in_array($row['status'], ['draft', 'invited', 'open', 'active', 'awaiting_human'], true))) throw new Failure(409, 'invalid_transition');
			$setup = Connections::current($actor, $id);
			if (!$setup || $setup['revoked']) throw new Failure(409, 'connection_required');
			// Repeated clicks/retries never extend an existing window.
			$until = !$input['enabled'] ? null : (self::policy($member)['enabled'] ? $member['ai_wait_until'] : gmdate('Y-m-d H:i:s', time() + 7200));
			Db::run('UPDATE debate_participants SET ai_wait_until = ? WHERE debate_id = ? AND user_id = ?', [$until, $id, $actor['user_id']]);
			return Connections::current($actor, $id);
		});
	}

	public static function status(array $actor, string $id): array
	{
		$row = Debates::readable($id, $actor);
		$flow = DebateFlow::state($row, [], $actor);
		// No repeated arguments, long guide, identities or credentials in idle checks.
		return ['revision' => (int) $row['revision'], 'message_count' => (int) $row['message_count'], 'next_action' => $flow['next_action'], 'can_publish' => $flow['can_publish'], 'keep_waiting' => $flow['keep_waiting'], 'waiting' => $flow['waiting'], 'poll_after_seconds' => 900];
	}
}

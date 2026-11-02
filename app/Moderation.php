<?php

declare(strict_types=1);

namespace Dilemma;

final class Moderation
{
	public static function report(array $actor, string $id, array $input): array
	{
		Debates::human($actor);
		Debates::readable($id, $actor);
		Support::rate('report:' . $actor['user_id'], 10, 3600);
		$message_id = ($input['message_id'] ?? '') ?: null;
		if ($message_id !== null) Support::uuid($message_id);
		if ($message_id !== null && !Db::one('SELECT id FROM debate_messages WHERE id = ? AND debate_id = ?', [$message_id, $id])) throw new Failure(422, 'invalid_reference');
		$report_id = Support::id();
		Db::run('INSERT INTO moderation_reports (id, reporter_id, debate_id, message_id, reason, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$report_id, $actor['user_id'], $id, $message_id, Support::text($input, 'reason', 10, 2000), Support::now()]);
		return ['report_id' => $report_id];
	}

	public static function administrator(array $actor): array
	{
		Debates::human($actor);
		$user = Debates::actor($actor);
		if (!(bool) $user['is_admin']) throw new Failure(403, 'access_denied');
		return $user;
	}

	public static function apply(array $actor, array $input): array
	{
		self::administrator($actor);
		return Db::transaction(static function () use ($actor, $input): array {
			$user = Auth::active($actor['user_id'], true);
			if (!(bool) $user['is_admin']) throw new Failure(403, 'access_denied');
			$action = Support::text($input, 'action', 1, 40);
			$id = Support::uuid(Support::text($input, 'target_id', 36, 36));
			$reason = Support::text($input, 'reason', 5, 500);
			if (in_array($action, ['hide_debate', 'restore_debate'], true)) {
				Debates::row($id, true);
				Db::run('UPDATE debates SET hidden = ?, revision = revision + 1 WHERE id = ?', [(int) ($action === 'hide_debate'), $id]);
			} elseif (in_array($action, ['block_user', 'restore_user'], true)) {
				$target = Db::one('SELECT * FROM users WHERE id = ? FOR UPDATE', [$id]);
				if (!$target || $target['status'] === 'deleted' || $id === $actor['user_id'] || (bool) $target['is_admin']) throw new Failure(422, 'invalid_target');
				Db::run('UPDATE users SET status = ? WHERE id = ?', [$action === 'block_user' ? 'blocked' : 'active', $id]);
				if ($action === 'block_user') Db::run('DELETE FROM auth_sessions WHERE user_id = ?', [$id]);
			} elseif ($action === 'resolve_report') {
				if (!Db::one('SELECT id FROM moderation_reports WHERE id = ?', [$id])) throw new Failure(404, 'not_found');
				Db::run("UPDATE moderation_reports SET status = 'resolved', resolved_at = ? WHERE id = ?", [Support::now(), $id]);
			} elseif ($action === 'retry_mail') {
				Db::run("UPDATE notification_outbox SET status = 'pending', attempts = 0, available_at = ?, last_error = NULL WHERE id = ? AND status = 'failed' AND encrypted_payload <> ''", [Support::now(), $id]);
			} else throw new Failure(422, 'invalid_action');
			Db::run('INSERT INTO audit_events (actor_id, action, target_id, data, created_at) VALUES (?, ?, ?, ?, ?)', [$actor['user_id'], $action, $id, Support::json(['reason' => $reason]), Support::now()]);
			return ['saved' => true];
		});
	}
}

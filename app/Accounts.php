<?php

declare(strict_types=1);

namespace Dilemma;

final class Accounts
{
	public static function update(array $actor, array $input): array
	{
		Debates::human($actor);
		return Db::transaction(static function () use ($actor, $input): array {
			$user = Debates::actor($actor, true);
			$avatar = Support::text($input, 'avatar', 0, 16);
			if (!in_array($avatar, ['', 'circle', 'leaf', 'spark', 'wave'], true)) throw new Failure(422, 'invalid_field', ['field' => 'avatar']);
			$link = Support::url(Support::text($input, 'chat_link', 0, 2000), true);
			Db::run('UPDATE users SET alias = ?, bio = ?, locale = ?, notify = ?, chat_link = ? WHERE id = ?', [Support::text($input, 'alias', 2, 60), Support::text($input, 'bio', 0, 500), ($input['locale'] ?? '') === 'en' ? 'en' : 'sv', (int) (($input['notify'] ?? false) === true), $link !== '' ? Support::seal($link) : null, $user['id']]);
			Db::run('UPDATE users SET avatar = ? WHERE id = ?', [$avatar, $user['id']]);
			return ['saved' => true];
		});
	}

	public static function export(array $actor): array
	{
		Debates::human($actor);
		$user = Debates::actor($actor);
		if ($user['chat_link']) $user['chat_link'] = Support::unseal($user['chat_link']);
		$id = $user['id'];
		return [
			'exported_at' => Support::now(), 'profile' => $user,
			'created_debates' => Db::all('SELECT * FROM debates WHERE creator_id = ?', [$id]),
			'participation' => Db::all('SELECT * FROM debate_participants WHERE user_id = ?', [$id]),
			'arguments' => Db::all('SELECT * FROM debate_messages WHERE author_id = ?', [$id]),
			'sources' => Db::all('SELECT s.* FROM argument_sources s JOIN debate_messages m ON m.id = s.message_id WHERE m.author_id = ?', [$id]),
			'relations' => Db::all('SELECT r.* FROM argument_relations r JOIN debate_messages m ON m.id = r.message_id WHERE m.author_id = ?', [$id]),
			'amendments' => Db::all('SELECT * FROM argument_amendments WHERE author_id = ?', [$id]),
			'positions' => Db::all('SELECT * FROM debate_positions WHERE user_id = ? ORDER BY sequence', [$id]),
			'consents' => Db::all('SELECT * FROM publication_consents WHERE user_id = ?', [$id]),
			'events' => Db::all('SELECT * FROM debate_events WHERE actor_id = ? ORDER BY sequence', [$id]),
			'reports' => Db::all('SELECT * FROM moderation_reports WHERE reporter_id = ?', [$id]),
			'connections' => Db::all('SELECT id, label, scopes, debate_id, created_at, revoked_at, write_test_at FROM mcp_connections WHERE user_id = ?', [$id]),
			'push_devices' => Db::all('SELECT id, locale, created_at FROM push_subscriptions WHERE user_id = ?', [$id]),
		];
	}

	public static function delete(array $actor, array $input): array
	{
		Debates::human($actor);
		return Db::transaction(static function () use ($actor, $input): array {
			$user = Debates::actor($actor, true);
			if (($input['confirm'] ?? false) !== true || ($input['email'] ?? '') !== $user['email']) throw new Failure(422, 'confirmation_required');
			foreach (Db::all('SELECT debate_id FROM debate_participants WHERE user_id = ? ORDER BY debate_id', [$user['id']]) as $item) {
				$row = Debates::row($item['debate_id'], true);
				Db::run("UPDATE debate_participants SET stance = '', processing_consent_at = NULL WHERE debate_id = ? AND user_id = ?", [$row['id'], $user['id']]);
				Debates::consent($row['id'], $user['id'], false, false);
				Db::run("UPDATE debates SET end_reason = CASE WHEN status IN ('draft','invited','active','paused') THEN 'account_deleted' ELSE end_reason END, status = CASE WHEN status IN ('draft','invited','active','paused') THEN 'ended' ELSE status END, end_proposed_by = NULL, revision = revision + 1, updated_at = ? WHERE id = ?", [Support::now(), $row['id']]);
				if ($row['creator_id'] === $user['id']) Db::run("UPDATE debates SET title = '[deleted]', thesis = '', introduction = '', left_label = '', right_label = '' WHERE id = ?", [$row['id']]);
				Db::run('DELETE FROM debate_invitations WHERE debate_id = ?', [$row['id']]);
				Debates::event($row['id'], $user['id'], 'account_deleted');
			}
			Db::run('DELETE s FROM argument_sources s JOIN debate_messages m ON m.id = s.message_id WHERE m.author_id = ?', [$user['id']]);
			Db::run('DELETE FROM argument_amendments WHERE author_id = ?', [$user['id']]);
			Db::run("UPDATE debate_messages SET body = '', client_label = '', subarguments = '[]', objections = '[]', rationale = '', conclusion = '', agent_assessment = '' WHERE author_id = ?", [$user['id']]);
			Db::run('DELETE FROM debate_positions WHERE user_id = ?', [$user['id']]);
			Db::run('DELETE FROM idempotency_keys WHERE user_id = ?', [$user['id']]);
			Db::run('DELETE FROM auth_sessions WHERE user_id = ?', [$user['id']]);
			Db::run('DELETE FROM email_verifications WHERE user_id = ?', [$user['id']]);
			Db::run("UPDATE moderation_reports SET reason = '[deleted]' WHERE reporter_id = ?", [$user['id']]);
			Db::run("UPDATE notification_outbox SET encrypted_payload = '', status = CASE WHEN status = 'sent' THEN 'sent' ELSE 'cancelled' END WHERE user_id = ?", [$user['id']]);
			Db::run('UPDATE mcp_connections SET revoked_at = COALESCE(revoked_at, ?), label = ? WHERE user_id = ?', [Support::now(), '[deleted]', $user['id']]);
			Db::run('DELETE FROM push_subscriptions WHERE user_id = ?', [$user['id']]);
			Db::run("UPDATE users SET email = NULL, alias = '[deleted]', bio = '', avatar = '', chat_link = NULL, status = 'deleted', notify = 0, push_enabled = 0, is_admin = 0 WHERE id = ?", [$user['id']]);
			return ['deleted' => true];
		});
	}
}

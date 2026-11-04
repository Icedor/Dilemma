<?php

declare(strict_types=1);

namespace Dilemma;

final class Debates
{
	public const CONSENT_SQL = "NOT EXISTS (SELECT 1 FROM debate_participants pc WHERE pc.debate_id = d.id AND pc.public_consent = 0)";
	public const OPEN_SQL = "d.status = 'open' AND d.opponent_mode = 'open' AND (SELECT COUNT(*) FROM debate_participants po WHERE po.debate_id = d.id AND po.left_at IS NULL) = 1 AND " . self::CONSENT_SQL . " AND EXISTS (SELECT 1 FROM debate_participants po JOIN users uo ON uo.id = po.user_id WHERE po.debate_id = d.id AND po.left_at IS NULL AND uo.status = 'active')";
	public const PUBLIC_SQL = "d.hidden = 0 AND " . self::CONSENT_SQL . " AND ((" . self::OPEN_SQL . ") OR (SELECT COUNT(*) FROM debate_participants pc WHERE pc.debate_id = d.id) >= 2)";

	public static function actor(array $actor, bool $write = false, ?string $debate_id = null): array
	{
		$user = Auth::active($actor['user_id'], $write);
		if (isset($actor['connection_id'])) {
			$connection = Db::one('SELECT * FROM mcp_connections WHERE id = ? AND user_id = ?' . ($write ? ' FOR UPDATE' : ''), [$actor['connection_id'], $user['id']]);
			if (!$connection || $connection['revoked_at'] !== null) throw new Failure(401, 'connection_revoked');
			$scopes = explode(' ', $connection['scopes']);
			if (!in_array($write ? 'arguments:write' : 'debates:read', $scopes, true)) throw new Failure(403, 'insufficient_scope');
			if ($connection['debate_id'] !== null && $debate_id !== null && $connection['debate_id'] !== $debate_id) throw new Failure(403, 'connection_debate_mismatch', ['connected_debate_id' => $connection['debate_id'], 'recovery' => 'This connection belongs to a different debate. Stop using it for this request. Use the requested debate\'s own setup after verifying its identity; do not retry this connection or broaden its permissions.']);
		}
		return $user;
	}

	public static function human(array $actor): void
	{
		if (isset($actor['connection_id']) || ($actor['channel'] ?? '') !== 'human') throw new Failure(403, 'human_only');
	}

	public static function row(string $id, bool $lock = false): array
	{
		Support::uuid($id);
		$row = Db::one('SELECT * FROM debates WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
		if (!$row) throw new Failure(404, 'not_found');
		return $row;
	}

	public static function member(string $id, string $user_id): array
	{
		$member = Db::one('SELECT * FROM debate_participants WHERE debate_id = ? AND user_id = ?', [$id, $user_id]);
		if (!$member) throw new Failure(404, 'not_found');
		return $member;
	}

	public static function readable(string $id, ?array $actor = null): array
	{
		$row = self::row($id);
		$member = null;
		if ($actor) {
			self::actor($actor, false, $id);
			$member = Db::one('SELECT * FROM debate_participants WHERE debate_id = ? AND user_id = ?', [$id, $actor['user_id']]);
			if (isset($actor['connection_id']) && !$member) throw new Failure(404, 'not_found');
		}
		$public = Db::one('SELECT d.id FROM debates d WHERE d.id = ? AND ' . self::PUBLIC_SQL, [$id]) !== null;
		if (!$member && !$public) throw new Failure(404, 'not_found');
		return $row + ['is_public' => $public, 'viewer_member' => $member];
	}

	public static function event(string $id, ?string $actor_id, string $kind, array $data = []): int
	{
		Db::run('INSERT INTO debate_events (debate_id, actor_id, kind, data, created_at) VALUES (?, ?, ?, ?, ?)', [$id, $actor_id, $kind, Support::json($data === [] ? (object) [] : $data), Support::now()]);
		return (int) Db::connection()->lastInsertId();
	}

	public static function consent(string $id, string $user_id, bool $public, bool $positions): void
	{
		Db::run('UPDATE debate_participants SET public_consent = ?, position_consent = ? WHERE debate_id = ? AND user_id = ?', [(int) $public, (int) $positions, $id, $user_id]);
		Db::run('INSERT INTO publication_consents (id, debate_id, user_id, public_consent, position_consent, policy_version, created_at, processing_consent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [Support::id(), $id, $user_id, (int) $public, (int) $positions, '2026-11-02', Support::now(), (int) (self::member($id, $user_id)['processing_consent_at'] !== null)]);
	}

	public static function position(string $id, string $user_id, array $input): void
	{
		$value = $input['position'] ?? null;
		if ($value !== null && $value !== '') $value = Support::integer($value, 0, 100, 'position');
		else $value = null;
		$position_id = Support::id();
		Db::run('INSERT INTO debate_positions (id, debate_id, user_id, value, note, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$position_id, $id, $user_id, $value, Support::text($input, 'position_note', 0, 500), Support::now()]);
		self::event($id, $user_id, $value === null ? 'position_skipped' : 'position_recorded', ['position_id' => $position_id]);
	}

	public static function create(array $actor, array $input): array
	{
		self::human($actor);
		if (($input['processing_consent'] ?? false) !== true) throw new Failure(422, 'consent_required');
		$mode = $input['opponent_mode'] ?? 'invite';
		if (!in_array($mode, ['invite', 'open'], true)) throw new Failure(422, 'invalid_field', ['field' => 'opponent_mode']);
		if ($mode === 'open' && ($input['public_consent'] ?? false) !== true) throw new Failure(422, 'open_consent_required');
		$english = ($input['locale'] ?? '') === 'en';
		$input['thesis'] = $input['thesis'] ?? $input['title'] ?? '';
		if (($input['topic'] ?? '') === '') $input['topic'] = $english ? 'Other' : 'Övrigt';
		if (($input['left_label'] ?? '') === '') $input['left_label'] = $english ? 'Disagree' : 'Håller inte med';
		if (($input['right_label'] ?? '') === '') $input['right_label'] = $english ? 'Agree' : 'Håller med';
		return Db::transaction(static function () use ($actor, $input): array {
			self::actor($actor, true);
			if ($receipt = Idempotency::replay($actor['user_id'], 'create', $input)) return $receipt;
			Support::rate('create:' . $actor['user_id'], 10, 3600);
			$id = Support::id();
			$locale = ($input['locale'] ?? '') === 'en' ? 'en' : 'sv';
			Db::run('INSERT INTO debates (id, creator_id, title, topic, thesis, introduction, left_label, right_label, locale, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id, $actor['user_id'], Support::text($input, 'title', 5, 160), Support::text($input, 'topic', 2, 60), Support::text($input, 'thesis', 5, 4000), Support::text($input, 'introduction', 0, 4000), Support::text($input, 'left_label', 1, 140), Support::text($input, 'right_label', 1, 140), $locale, Support::now(), Support::now()]);
			Db::run('INSERT INTO debate_participants (debate_id, user_id, side, active_side, stance, joined_at) VALUES (?, ?, 0, 0, ?, ?)', [$id, $actor['user_id'], Support::text($input, 'stance', 5, 4000), Support::now()]);
			if (($input['opponent_mode'] ?? 'invite') === 'open') Db::run("UPDATE debates SET opponent_mode = 'open', status = 'open' WHERE id = ?", [$id]);
			Db::run('UPDATE debate_participants SET processing_consent_at = ?, notify = ? WHERE debate_id = ? AND user_id = ?', [Support::now(), (int) (($input['notify'] ?? true) === true), $id, $actor['user_id']]);
			self::consent($id, $actor['user_id'], ($input['public_consent'] ?? false) === true, ($input['position_consent'] ?? false) === true);
			self::event($id, $actor['user_id'], 'created');
			self::position($id, $actor['user_id'], $input);
			Connections::setup($actor, ['debate_id' => $id]);
			return Idempotency::save($actor['user_id'], 'create', $input, ['debate_id' => $id, 'revision' => 1, 'stored_at' => Support::now()]);
		});
	}

	public static function invite(array $actor, string $id, array $input): array
	{
		self::human($actor);
		$input['debate_id'] = $id;
		return Db::transaction(static function () use ($actor, $id, $input): array {
			$sender = self::actor($actor, true, $id);
			$row = self::row($id, true);
			if (self::member($id, $actor['user_id'])['left_at'] !== null) throw new Failure(403, 'access_denied');
			if ($receipt = Idempotency::replay($actor['user_id'], 'invite', $input)) return $receipt;
			if ($row['hidden'] || !in_array($row['status'], ['draft', 'invited', 'open'], true)) throw new Failure(409, 'invalid_transition');
			$email = Support::text($input, 'email', 0, 254);
			if ($email !== '') $email = Support::email($email);
			if ($row['status'] === 'open') {
				if (!self::readable($id)['is_public']) throw new Failure(409, 'invalid_transition');
				$url = Config::url('/debate/' . $id);
				if ($email !== '') {
					Support::rate('invite:' . $actor['user_id'], 8, 3600);
					Mail::enqueue('open-invitation:' . Support::id(), 'invitation', $email, $row['locale'], ['url' => $url, 'title' => $row['title'], 'sender_name' => $sender['alias']]);
				}
				return Idempotency::save($actor['user_id'], 'invite', $input, ['invitation_url' => $url, 'email_sent' => $email !== '', 'expires_in' => null]);
			}
			if ($email === '' && ($existing = Db::one("SELECT encrypted_token, expires_at FROM debate_invitations WHERE debate_id = ? AND email IS NULL AND status = 'pending' AND encrypted_token IS NOT NULL AND expires_at > UTC_TIMESTAMP() ORDER BY created_at DESC LIMIT 1", [$id]))) {
				return Idempotency::save($actor['user_id'], 'invite', $input, ['invitation_url' => Config::url('/invite#' . Support::unseal($existing['encrypted_token'])), 'expires_in' => max(0, strtotime($existing['expires_at'] . ' UTC') - time()), 'email_sent' => false]);
			}
			Support::rate('invite:' . $actor['user_id'], 8, 3600);
			$token = Support::token();
			$invitation_id = Support::id();
			Db::run('INSERT INTO debate_invitations (id, debate_id, token_hash, email, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$invitation_id, $id, Support::hash($token), $email ?: null, gmdate('Y-m-d H:i:s', time() + 604800), Support::now()]);
			Db::run('UPDATE debate_invitations SET encrypted_token = ? WHERE id = ?', [Support::seal($token), $invitation_id]);
			Db::run("UPDATE debates SET status = 'invited', revision = revision + 1, updated_at = ? WHERE id = ?", [Support::now(), $id]);
			self::event($id, $actor['user_id'], 'invited');
			$url = Config::url('/invite#' . $token);
			if ($email !== '') Mail::enqueue('invitation:' . $invitation_id, 'invitation', $email, $row['locale'], ['url' => $url, 'title' => $row['title'], 'sender_name' => $sender['alias']]);
			return Idempotency::save($actor['user_id'], 'invite', $input, ['invitation_url' => $url, 'expires_in' => 604800, 'email_sent' => $email !== '']);
		});
	}

	public static function invitation(?array $actor, string $token): array
	{
		$user = $actor ? self::actor($actor) : null;
		$invitation = Db::one("SELECT * FROM debate_invitations WHERE token_hash = ? AND status = 'pending' AND expires_at > UTC_TIMESTAMP()", [Support::hash($token)]);
		if (!$invitation) throw new Failure(404, 'expired_link');
		$row = self::row($invitation['debate_id']);
		if ($row['hidden'] || $row['status'] !== 'invited') throw new Failure(409, 'invalid_transition');
		// A shared invitation grants a limited preview, not access to the debate history.
		// Invitations addressed to an email remain private until that recipient signs in.
		if ($invitation['email'] !== null) {
			if (!$user) return ['sign_in_required' => true];
			if ($invitation['email'] !== $user['email']) return ['sign_in_required' => true, 'different_account' => true];
		}
		$creator = Db::one('SELECT u.alias, p.stance, p.public_consent FROM debate_participants p JOIN users u ON u.id = p.user_id WHERE p.debate_id = ? AND p.left_at IS NULL AND u.status = \'active\'', [$row['id']]);
		if (!$creator) throw new Failure(404, 'expired_link');
		return ['debate' => array_intersect_key($row, array_flip(['id', 'title', 'topic', 'thesis', 'introduction', 'left_label', 'right_label', 'locale'])), 'creator' => $creator, 'sign_in_required' => $user === null, 'already_member' => $user !== null && Db::one('SELECT user_id FROM debate_participants WHERE debate_id = ? AND user_id = ?', [$row['id'], $user['id']]) !== null];
	}

	public static function accept(array $actor, array $input): array
	{
		self::human($actor);
		return Db::transaction(static function () use ($actor, $input): array {
			$user = self::actor($actor, true);
			if ($receipt = Idempotency::replay($user['id'], 'accept', $input)) return $receipt;
			$token = Support::text($input, 'token', 43, 43);
			$invitation = Db::one('SELECT * FROM debate_invitations WHERE token_hash = ?', [Support::hash($token)]);
			if (!$invitation) throw new Failure(404, 'expired_link');
			$row = self::row($invitation['debate_id'], true);
			$invitation = Db::one('SELECT * FROM debate_invitations WHERE id = ? FOR UPDATE', [$invitation['id']]);
			if ($invitation['status'] !== 'pending' || $invitation['expires_at'] < Support::now() || ($invitation['email'] !== null && $invitation['email'] !== $user['email'])) throw new Failure(404, 'expired_link');
			if ($row['hidden'] || $row['status'] !== 'invited' || Db::one('SELECT user_id FROM debate_participants WHERE debate_id = ? AND user_id = ?', [$row['id'], $user['id']])) throw new Failure(409, 'invalid_transition');
			if (($input['accept'] ?? true) === false) {
				// A shared link stays available when one reader declines it.
				if ($invitation['email'] !== null) Db::run("UPDATE debate_invitations SET status = 'declined', encrypted_token = NULL WHERE id = ?", [$invitation['id']]);
				$pending = Db::one("SELECT id FROM debate_invitations WHERE debate_id = ? AND status = 'pending' AND expires_at > UTC_TIMESTAMP() LIMIT 1", [$row['id']]);
				Db::run('UPDATE debates SET status = ?, revision = revision + 1, updated_at = ? WHERE id = ?', [$pending ? 'invited' : 'draft', Support::now(), $row['id']]);
				self::event($row['id'], null, 'invitation_declined');
				return Idempotency::save($user['id'], 'accept', $input, ['declined' => true]);
			}
			if (($input['accept_terms'] ?? false) !== true || ($input['processing_consent'] ?? false) !== true) throw new Failure(422, 'consent_required');
			$side = 1 - (int) Db::one('SELECT side FROM debate_participants WHERE debate_id = ? AND left_at IS NULL', [$row['id']])['side'];
			Db::run('INSERT INTO debate_participants (debate_id, user_id, side, active_side, stance, joined_at) VALUES (?, ?, ?, ?, ?, ?)', [$row['id'], $user['id'], $side, $side, Support::text($input, 'stance', 5, 4000), Support::now()]);
			Db::run('UPDATE debate_participants SET processing_consent_at = ?, notify = ? WHERE debate_id = ? AND user_id = ?', [Support::now(), (int) (($input['notify'] ?? true) === true), $row['id'], $user['id']]);
			self::consent($row['id'], $user['id'], ($input['public_consent'] ?? false) === true, ($input['position_consent'] ?? false) === true);
			Db::run("UPDATE debate_invitations SET status = 'accepted' WHERE id = ?", [$invitation['id']]);
			Db::run("UPDATE debate_invitations SET encrypted_token = NULL, status = CASE WHEN status = 'pending' THEN 'superseded' ELSE status END WHERE debate_id = ?", [$row['id']]);
			Db::run("UPDATE debates SET status = 'active', revision = revision + 1, updated_at = ? WHERE id = ?", [Support::now(), $row['id']]);
			$event = self::event($row['id'], $user['id'], 'joined');
			self::position($row['id'], $user['id'], $input);
			Connections::setup($actor, ['debate_id' => $row['id']]);
			Mail::notify($row['id'], $user['id'], 'joined', $event);
			return Idempotency::save($user['id'], 'accept', $input, ['debate_id' => $row['id'], 'revision' => (int) $row['revision'] + 1, 'stored_at' => Support::now()]);
		});
	}

	public static function claim(array $actor, string $id, array $input): array
	{
		self::human($actor);
		$input['debate_id'] = $id;
		return Db::transaction(static function () use ($actor, $id, $input): array {
			$user = self::actor($actor, true);
			if ($receipt = Idempotency::replay($user['id'], 'claim', $input)) return $receipt;
			$row = self::row($id, true);
			if (Db::one('SELECT user_id FROM debate_participants WHERE debate_id = ? AND user_id = ?', [$id, $user['id']])) throw new Failure(403, 'access_denied');
			if ($row['hidden'] || $row['status'] !== 'open' || $row['opponent_mode'] !== 'open') throw new Failure(409, 'opponent_taken');
			if (!Db::one('SELECT d.id FROM debates d WHERE d.id = ? AND d.hidden = 0 AND ' . self::OPEN_SQL, [$id])) throw new Failure(409, 'opponent_taken');
			if (($input['accept_terms'] ?? false) !== true || ($input['processing_consent'] ?? false) !== true || ($input['public_consent'] ?? false) !== true) throw new Failure(422, 'consent_required');
			$side = 1 - (int) Db::one('SELECT side FROM debate_participants WHERE debate_id = ? AND left_at IS NULL', [$id])['side'];
			Db::run('INSERT INTO debate_participants (debate_id, user_id, side, active_side, stance, joined_at) VALUES (?, ?, ?, ?, ?, ?)', [$id, $user['id'], $side, $side, Support::text($input, 'stance', 5, 4000), Support::now()]);
			Db::run('UPDATE debate_participants SET processing_consent_at = ?, notify = ? WHERE debate_id = ? AND user_id = ?', [Support::now(), (int) (($input['notify'] ?? true) === true), $id, $user['id']]);
			self::consent($id, $user['id'], true, ($input['position_consent'] ?? false) === true);
			Db::run("UPDATE debates SET status = 'active', revision = revision + 1, updated_at = ? WHERE id = ?", [Support::now(), $id]);
			$event = self::event($id, $user['id'], 'joined');
			self::position($id, $user['id'], $input);
			Connections::setup($actor, ['debate_id' => $id]);
			Mail::notify($id, $user['id'], 'joined', $event);
			return Idempotency::save($user['id'], 'claim', $input, ['debate_id' => $id, 'revision' => (int) $row['revision'] + 1, 'stored_at' => Support::now()]);
		});
	}

	public static function revision(array $row, array $input): void
	{
		if (!isset($input['revision']) || Support::integer($input['revision'], 1, PHP_INT_MAX, 'revision') !== (int) $row['revision']) throw new Failure(409, 'revision_conflict', ['revision' => (int) $row['revision']]);
	}
}

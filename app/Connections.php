<?php

declare(strict_types=1);

namespace Dilemma;

final class Connections
{
	public const READ = 'debates:read';
	public const WRITE = 'arguments:write';

	public const CLAUDE_CALLBACKS = ['https://claude.ai/api/mcp/auth_callback', 'https://claude.com/api/mcp/auth_callback'];

	// One Claude connector per account: each debate is a separate, revocable connection inside that grant.
	public static function native_status(array $actor, string $debate_id): array
	{
		Debates::human($actor);
		Debates::actor($actor);
		$debate_id = Support::uuid($debate_id);
		$member = Debates::member($debate_id, $actor['user_id']);
		$roots = self::claude_roots($actor['user_id']);
		$ready = false; $verified = false;
		if ($roots) {
			$marks = implode(',', array_fill(0, count($roots), '?'));
			foreach (Db::all("SELECT write_test_at FROM mcp_connections WHERE user_id=? AND debate_id=? AND revoked_at IS NULL AND scopes LIKE '%arguments:write%' AND COALESCE(grant_id, id) IN ($marks)", [$actor['user_id'], $debate_id, ...$roots]) as $row) {
				$ready = true;
				$verified = $verified || $row['write_test_at'] !== null;
			}
		}
		$open = $member['left_at'] === null && !Debates::row($debate_id)['hidden'];
		if (!$open) $ready = $verified = false;
		$state = $ready ? 'ready' : ($roots && $open ? 'enable' : 'connect');
		return ['ready' => $ready, 'verified' => $verified, 'state' => $state, 'native_setup' => AiSetup::connector($debate_id, I18n::locale(), $actor['user_id'])];
	}

	// Claude's documented install link only prefills the form; the human still confirms it in Claude.
	public static function install_url(): string
	{
		return 'https://claude.ai/customize/connectors?' . http_build_query(['modal' => 'add-custom-connector', 'connectorName' => 'Dilemma', 'connectorUrl' => Config::url('/mcp')], '', '&', PHP_QUERY_RFC3986);
	}

	// Live Claude grants for a user, most recently refreshed first.
	public static function claude_roots(string $user_id): array
	{
		$rows = Db::all("SELECT r.id, r.revoked_at, o.redirect_uris, t.expires_at FROM mcp_connections r JOIN mcp_tokens t ON t.connection_id=r.id JOIN oauth_clients o ON o.id=t.client_id WHERE r.user_id=? AND r.grant_id IS NULL AND r.scopes LIKE '%arguments:write%' AND ((t.kind='access' AND t.expires_at>UTC_TIMESTAMP()) OR (t.kind='refresh' AND t.used_at IS NULL AND (t.expires_at IS NULL OR t.expires_at>UTC_TIMESTAMP()))) ORDER BY r.created_at DESC", [$user_id]);
		$recent = [];
		foreach ($rows as $row) {
			if (!array_intersect(self::CLAUDE_CALLBACKS, json_decode($row['redirect_uris'], true, 16, JSON_THROW_ON_ERROR))) continue;
			if (!self::members($row['id'])) continue;
			$recent[$row['id']] = max($recent[$row['id']] ?? '', (string) $row['expires_at']);
		}
		arsort($recent, SORT_STRING);
		return array_keys($recent);
	}

	// Active per-debate connections that share one OAuth grant (the root plus enabled debates).
	public static function members(string $root_id): array
	{
		return Db::all('SELECT id, debate_id FROM mcp_connections WHERE (id = ? OR grant_id = ?) AND revoked_at IS NULL ORDER BY grant_id IS NOT NULL, created_at, id', [$root_id, $root_id]);
	}

	public static function enable_claude(array $actor, string $debate_id): array
	{
		Debates::human($actor);
		$debate_id = Support::uuid($debate_id);
		Db::transaction(static function () use ($actor, $debate_id): void {
			Debates::actor($actor, true);
			Support::rate('connection:' . $actor['user_id'], 20, 3600);
			$member = Db::one('SELECT left_at FROM debate_participants WHERE debate_id = ? AND user_id = ? FOR UPDATE', [$debate_id, $actor['user_id']]);
			if (!$member) throw new Failure(404, 'not_found');
			if ($member['left_at'] !== null || Debates::row($debate_id)['hidden']) throw new Failure(409, 'invalid_transition');
			$roots = self::claude_roots($actor['user_id']);
			if (!$roots) throw new Failure(409, 'claude_not_connected');
			$root = Db::one('SELECT * FROM mcp_connections WHERE id = ? FOR UPDATE', [$roots[0]]);
			foreach (self::members($root['id']) as $existing) if ($existing['debate_id'] === $debate_id) return;
			Db::run('INSERT INTO mcp_connections (id, user_id, label, scopes, debate_id, created_at, grant_id) VALUES (?, ?, ?, ?, ?, ?, ?)', [Support::id(), $actor['user_id'], $root['label'], $root['scopes'], $debate_id, Support::now(), $root['id']]);
		});
		return self::native_status($actor, $debate_id);
	}

	// Resolve a grant-wide MCP actor to the one connection for the requested debate.
	public static function scoped(array $actor, ?string $debate_id, bool $required = false): array
	{
		$debates = $actor['debates'] ?? [];
		if (count($debates) < 2 && !($debate_id !== null && isset($debates[$debate_id]))) return $actor;
		if ($debate_id === null) {
			if ($required) throw new Failure(422, 'debate_id_required', ['enabled_debate_ids' => array_keys($debates)]);
			return $actor;
		}
		if (!isset($debates[$debate_id])) throw new Failure(403, 'connection_debate_mismatch', ['enabled_debate_ids' => array_keys($debates), 'recovery' => 'This debate is not enabled for this connection. Use only the debate your human named. The human can enable another debate on its Dilemma page.']);
		return ['connection_id' => $debates[$debate_id], 'debate_id' => $debate_id] + $actor;
	}

	public static function scopes(mixed $value): string
	{
		if (!is_string($value) || strlen($value) > 100) throw new Failure(400, 'invalid_scope');
		$scopes = array_values(array_unique(explode(' ', trim($value))));
		if (!in_array(self::READ, $scopes, true) || array_diff($scopes, [self::READ, self::WRITE])) throw new Failure(400, 'invalid_scope');
		sort($scopes);
		return implode(' ', $scopes);
	}

	public static function current(array $actor, string $debate_id): ?array
	{
		Debates::human($actor);
		Debates::actor($actor);
		Debates::member(Support::uuid($debate_id), $actor['user_id']);
		$row = Db::one('SELECT a.*, c.revoked_at FROM debate_ai_access a JOIN mcp_connections c ON c.id = a.connection_id WHERE a.debate_id = ? AND a.user_id = ?', [$debate_id, $actor['user_id']]);
		return $row ? self::setup_result($row) : null;
	}

	private static function setup_result(array $row): array
	{
		$revoked = $row['revoked_at'] !== null;
		$waiting = AiWaiting::policy(Debates::member($row['debate_id'], $row['user_id']));
		$binding = ['connection_id' => $row['connection_id'], 'user_id' => $row['user_id']];
		return ['connection_id' => $row['connection_id'], 'waiting' => $waiting, 'persistent' => true, 'expires_at' => null, 'revoked' => $revoked, 'native_setup' => AiSetup::connector($row['debate_id'], I18n::locale(), $row['user_id']), 'setup_text' => $revoked ? '' : AiSetup::text(Support::unseal($row['encrypted_token']), $row['debate_id'], null, I18n::locale(), $waiting, $binding)];
	}

	public static function setup(array $actor, array $input): array
	{
		Debates::human($actor);
		$debate_id = Support::uuid($input['debate_id'] ?? '');
		return Db::transaction(static function () use ($actor, $input, $debate_id): array {
			Debates::actor($actor, true);
			// Serialize first creation as well as recopy/renew for this participant.
			if (!Db::one('SELECT user_id FROM debate_participants WHERE debate_id = ? AND user_id = ? FOR UPDATE', [$debate_id, $actor['user_id']])) throw new Failure(404, 'not_found');
			$row = Db::one('SELECT a.*, c.revoked_at FROM debate_ai_access a JOIN mcp_connections c ON c.id = a.connection_id WHERE a.debate_id = ? AND a.user_id = ? FOR UPDATE', [$debate_id, $actor['user_id']]);
			if ($row && ($row['revoked_at'] === null || ($input['renew'] ?? false) !== true)) return self::setup_result($row);
			// Upgrade the previous setup connection without breaking its still-valid copied key.
			$legacy = !$row ? Db::one("SELECT c.id FROM mcp_connections c WHERE c.user_id = ? AND c.debate_id = ? AND c.label = 'Dilemma AI' AND c.scopes = 'arguments:write debates:read' AND c.revoked_at IS NULL AND EXISTS (SELECT 1 FROM mcp_tokens t WHERE t.connection_id = c.id AND t.kind = 'access' AND t.client_id IS NULL AND t.expires_at > UTC_TIMESTAMP()) ORDER BY c.created_at DESC LIMIT 1 FOR UPDATE", [$actor['user_id'], $debate_id]) : null;
			$connection_id = $legacy['id'] ?? self::create($actor, ['label' => 'Dilemma AI', 'scopes' => self::READ . ' ' . self::WRITE, 'debate_id' => $debate_id], false)['connection_id'];
			$token = Support::token();
			Db::run("INSERT INTO mcp_tokens (token_hash, connection_id, kind, resource, expires_at) VALUES (?, ?, 'access', ?, NULL)", [Support::hash($token), $connection_id, Config::url('/mcp')]);
			if ($legacy) Db::run("UPDATE mcp_tokens SET expires_at = NULL WHERE connection_id = ? AND kind = 'access' AND client_id IS NULL AND expires_at > UTC_TIMESTAMP()", [$connection_id]);
			Db::run('INSERT INTO debate_ai_access (debate_id, user_id, connection_id, encrypted_token, created_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE connection_id = VALUES(connection_id), encrypted_token = VALUES(encrypted_token)', [$debate_id, $actor['user_id'], $connection_id, Support::seal($token), Support::now()]);
			return self::setup_result(['user_id' => $actor['user_id'], 'connection_id' => $connection_id, 'debate_id' => $debate_id, 'encrypted_token' => Support::seal($token), 'revoked_at' => null]);
		});
	}

	public static function create(array $actor, array $input, bool $manual = true, int $lifetime = 2592000): array
	{
		Debates::human($actor);
		if ($lifetime < 3600 || $lifetime > 2592000) throw new Failure(422, 'invalid_field');
		return Db::transaction(static function () use ($actor, $input, $manual, $lifetime): array {
			Debates::actor($actor, true);
			Support::rate('connection:' . $actor['user_id'], 20, 3600);
			$debate_id = ($input['debate_id'] ?? '') ?: null;
			if ($debate_id !== null) Debates::member(Support::uuid($debate_id), $actor['user_id']);
			$id = Support::id();
			$scopes = self::scopes($input['scopes'] ?? self::READ);
			Db::run('INSERT INTO mcp_connections (id, user_id, label, scopes, debate_id, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$id, $actor['user_id'], Support::text($input, 'label', 1, 100), $scopes, $debate_id, Support::now()]);
			$result = ['connection_id' => $id, 'scopes' => $scopes];
			if ($manual) {
				$token = Support::token();
				$result['expires_at'] = gmdate('Y-m-d H:i:s', time() + $lifetime);
				Db::run('INSERT INTO mcp_tokens (token_hash, connection_id, kind, resource, expires_at) VALUES (?, ?, ?, ?, ?)', [Support::hash($token), $id, 'access', Config::url('/mcp'), $result['expires_at']]);
				$result['access_token'] = $token;
				$result['expires_in'] = $lifetime;
			}
			return $result;
		});
	}

	public static function revoke(array $actor, string $id): array
	{
		Debates::human($actor);
		return Db::transaction(static function () use ($actor, $id): array {
			Debates::actor($actor, true);
			$connection = Db::one('SELECT id FROM mcp_connections WHERE id = ? AND user_id = ? FOR UPDATE', [$id, $actor['user_id']]);
			if (!$connection) throw new Failure(404, 'not_found');
			Db::run('UPDATE mcp_connections SET revoked_at = COALESCE(revoked_at, ?) WHERE id = ?', [Support::now(), $id]);
			Db::run('UPDATE debate_participants p JOIN debate_ai_access a ON a.debate_id = p.debate_id AND a.user_id = p.user_id SET p.ai_wait_until = NULL WHERE a.connection_id = ?', [$id]);
			Db::run('UPDATE debate_ai_access SET encrypted_token = NULL WHERE connection_id = ?', [$id]);
			return ['revoked' => true];
		});
	}

	public static function authenticate(string $authorization): array
	{
		if (!preg_match('/^Bearer ([A-Za-z0-9_-]{43})$/D', $authorization, $match)) throw new Failure(401, 'invalid_token');
		$row = Db::one("SELECT c.*, t.expires_at, t.resource FROM mcp_tokens t JOIN mcp_connections c ON c.id = t.connection_id JOIN users u ON u.id = c.user_id WHERE t.token_hash = ? AND t.kind = 'access' AND (t.expires_at > UTC_TIMESTAMP() OR (t.expires_at IS NULL AND EXISTS (SELECT 1 FROM debate_ai_access a WHERE a.connection_id = c.id AND a.user_id = c.user_id AND a.debate_id = c.debate_id))) AND u.status = 'active' AND u.verified_at IS NOT NULL", [Support::hash($match[1])]);
		if (!$row || $row['resource'] !== Config::url('/mcp')) throw new Failure(401, 'invalid_token');
		// A grant stays usable while any of its debate connections is active.
		$members = self::members($row['id']);
		if (!$members) throw new Failure(401, 'invalid_token');
		Support::rate('mcp:' . $row['id'], 120, 60);
		$debates = [];
		foreach ($members as $member) if ($member['debate_id'] !== null) $debates[$member['debate_id']] = $member['id'];
		return ['user_id' => $row['user_id'], 'connection_id' => $members[0]['id'], 'channel' => 'mcp', 'scopes' => $row['scopes'], 'debate_id' => $members[0]['debate_id'], 'grant_id' => $row['id'], 'debates' => $debates];
	}

	public static function identity(array $actor): array
	{
		if (!isset($actor['connection_id'])) throw new Failure(403, 'connection_required');
		$user = Debates::actor($actor);
		$connection = Db::one('SELECT id, debate_id, scopes FROM mcp_connections WHERE id = ? AND user_id = ?', [$actor['connection_id'], $actor['user_id']]);
		$identity = ['user_id' => $actor['user_id'], 'alias' => $user['alias'], 'scopes' => explode(' ', $connection['scopes']), 'connection_id' => $connection['id'], 'debate_id' => $connection['debate_id']];
		if (count($actor['debates'] ?? []) > 1) $identity['enabled_debates'] = array_map(static fn (string $debate, string $id): array => ['debate_id' => $debate, 'connection_id' => $id], array_keys($actor['debates']), array_values($actor['debates']));
		return $identity;
	}

	public static function test(array $actor, array $input, bool $write): array
	{
		if (!isset($actor['connection_id'])) throw new Failure(403, 'connection_required');
		return Db::transaction(static function () use ($actor, $input, $write): array {
			Debates::actor($actor, $write);
			$identity = self::identity($actor);
			if ($write) {
				$input['connection_id'] = $actor['connection_id'];
				if ($receipt = Idempotency::replay($actor['user_id'], 'connection_test', $input)) return $receipt;
				Db::run('UPDATE mcp_connections SET write_test_at = ? WHERE id = ?', [Support::now(), $actor['connection_id']]);
				return Idempotency::save($actor['user_id'], 'connection_test', $input, ['connection_id' => $identity['connection_id'], 'debate_id' => $identity['debate_id'], 'user_id' => $identity['user_id'], 'write_verified' => true, 'stored_at' => Support::now()]);
			}
			// Read tools never persist a test result; successful write_test records proof.
			return ['connection_id' => $identity['connection_id'], 'debate_id' => $identity['debate_id'], 'user_id' => $identity['user_id'], 'read_verified' => true, 'scopes' => implode(' ', $identity['scopes'])];
		});
	}
}

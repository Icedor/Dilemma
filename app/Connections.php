<?php

declare(strict_types=1);

namespace Dilemma;

final class Connections
{
	public const READ = 'debates:read';
	public const WRITE = 'arguments:write';

	public static function scopes(mixed $value): string
	{
		if (!is_string($value) || strlen($value) > 100) throw new Failure(400, 'invalid_scope');
		$scopes = array_values(array_unique(explode(' ', trim($value))));
		if (!in_array(self::READ, $scopes, true) || array_diff($scopes, [self::READ, self::WRITE])) throw new Failure(400, 'invalid_scope');
		sort($scopes);
		return implode(' ', $scopes);
	}

	public static function setup(array $actor, array $input): array
	{
		$debate_id = Support::uuid($input['debate_id'] ?? '');
		$result = self::create($actor, ['label' => 'Dilemma AI', 'scopes' => self::READ . ' ' . self::WRITE, 'debate_id' => $debate_id], true, 86400);
		return ['connection_id' => $result['connection_id'], 'expires_at' => $result['expires_at'], 'setup_text' => AiSetup::text($result['access_token'], $debate_id, $result['expires_at'], I18n::locale())];
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
			return ['revoked' => true];
		});
	}

	public static function authenticate(string $authorization): array
	{
		if (!preg_match('/^Bearer ([A-Za-z0-9_-]{43})$/D', $authorization, $match)) throw new Failure(401, 'invalid_token');
		$row = Db::one("SELECT c.*, t.expires_at, t.resource FROM mcp_tokens t JOIN mcp_connections c ON c.id = t.connection_id JOIN users u ON u.id = c.user_id WHERE t.token_hash = ? AND t.kind = 'access' AND t.expires_at > UTC_TIMESTAMP() AND c.revoked_at IS NULL AND u.status = 'active' AND u.verified_at IS NOT NULL", [Support::hash($match[1])]);
		if (!$row || $row['resource'] !== Config::url('/mcp')) throw new Failure(401, 'invalid_token');
		Support::rate('mcp:' . $row['id'], 120, 60);
		return ['user_id' => $row['user_id'], 'connection_id' => $row['id'], 'channel' => 'mcp', 'scopes' => $row['scopes'], 'debate_id' => $row['debate_id']];
	}

	public static function test(array $actor, array $input, bool $write): array
	{
		if (!isset($actor['connection_id'])) throw new Failure(403, 'connection_required');
		return Db::transaction(static function () use ($actor, $input, $write): array {
			Debates::actor($actor, $write);
			if ($write) {
				$input['connection_id'] = $actor['connection_id'];
				if ($receipt = Idempotency::replay($actor['user_id'], 'connection_test', $input)) return $receipt;
				Db::run('UPDATE mcp_connections SET write_test_at = ? WHERE id = ?', [Support::now(), $actor['connection_id']]);
				return Idempotency::save($actor['user_id'], 'connection_test', $input, ['connection_id' => $actor['connection_id'], 'write_verified' => true, 'stored_at' => Support::now()]);
			}
			// Read tools never persist a test result; successful write_test records proof.
			return ['connection_id' => $actor['connection_id'], 'read_verified' => true, 'scopes' => $actor['scopes']];
		});
	}
}

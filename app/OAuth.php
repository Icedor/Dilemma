<?php

declare(strict_types=1);

namespace Dilemma;

final class OAuth
{
	public static function metadata(): array
	{
		return ['issuer' => Config::url(), 'authorization_endpoint' => Config::url('/oauth/authorize'), 'token_endpoint' => Config::url('/oauth/token'), 'registration_endpoint' => Config::url('/oauth/register'), 'revocation_endpoint' => Config::url('/oauth/revoke'), 'response_types_supported' => ['code'], 'grant_types_supported' => ['authorization_code', 'refresh_token'], 'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'], 'code_challenge_methods_supported' => ['S256'], 'scopes_supported' => [Connections::READ, Connections::WRITE]];
	}

	public static function register(array $input, string $ip): array
	{
		Support::rate('oauth-register:' . $ip, 20, 3600);
		Support::rate('oauth-register-global', 300, 86400);
		$uris = $input['redirect_uris'] ?? null;
		if (!is_array($uris) || !array_is_list($uris) || count($uris) < 1 || count($uris) > 5) throw new Failure(400, 'invalid_redirect_uri');
		foreach ($uris as $uri) {
			if (!is_string($uri) || isset(parse_url($uri)['fragment'])) throw new Failure(400, 'invalid_redirect_uri');
			Support::url($uri);
		}
		foreach (['grant_types' => ['authorization_code', 'refresh_token'], 'response_types' => ['code']] as $key => $allowed) {
			$values = $input[$key] ?? $allowed;
			if (!is_array($values) || !array_is_list($values) || !$values || count($values) > count($allowed)) throw new Failure(400, 'invalid_client_metadata');
			foreach ($values as $value) if (!is_string($value) || !in_array($value, $allowed, true)) throw new Failure(400, 'invalid_client_metadata');
		}
		$method = $input['token_endpoint_auth_method'] ?? 'client_secret_basic';
		if (!in_array($method, ['none', 'client_secret_post', 'client_secret_basic'], true)) throw new Failure(400, 'invalid_client_metadata');
		$secret = $method === 'none' ? null : Support::token();
		$id = Support::id();
		$name = Support::text($input, 'client_name', 0, 100) ?: 'AI app';
		Db::run('INSERT INTO oauth_clients (id, name, redirect_uris, created_at, auth_method, secret_hash) VALUES (?, ?, ?, ?, ?, ?)', [$id, $name, Support::json(array_values(array_unique($uris))), Support::now(), $method, $secret === null ? null : Support::hash($secret)]);
		return ($secret === null ? [] : ['client_secret' => $secret, 'client_secret_expires_at' => 0]) + ['client_id_issued_at' => time(), 'client_id' => $id, 'client_name' => $name, 'redirect_uris' => $uris, 'token_endpoint_auth_method' => $method, 'grant_types' => $input['grant_types'] ?? ['authorization_code', 'refresh_token'], 'response_types' => $input['response_types'] ?? ['code']];
	}

	public static function request(array $input): array
	{
		if (!is_string($input['client_id'] ?? null)) throw new Failure(400, 'invalid_client');
		$client = Db::one('SELECT * FROM oauth_clients WHERE id = ?', [$input['client_id']]);
		if (!$client) throw new Failure(400, 'invalid_client');
		$redirect = $input['redirect_uri'] ?? '';
		if (!is_string($redirect) || !in_array($redirect, json_decode($client['redirect_uris'], true, 16, JSON_THROW_ON_ERROR), true)) throw new Failure(400, 'invalid_redirect_uri');
		if (($input['response_type'] ?? '') !== 'code' || ($input['code_challenge_method'] ?? '') !== 'S256' || !is_string($input['code_challenge'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{43}$/D', $input['code_challenge'])) throw new Failure(400, 'invalid_request');
		if (($input['resource'] ?? Config::url('/mcp')) !== Config::url('/mcp')) throw new Failure(400, 'invalid_target');
		$state = Support::text($input, 'state', 0, 1024);
		$scope = Connections::scopes($input['scope'] ?? (Connections::READ . ' ' . Connections::WRITE));
		return ['client' => $client, 'redirect_uri' => $redirect, 'code_challenge' => $input['code_challenge'], 'resource' => $input['resource'] ?? Config::url('/mcp'), 'state' => $state, 'scope' => $scope];
	}

	public static function authorize(array $actor, array $input): array
	{
		Debates::human($actor);
		$request = self::request($input);
		if (($input['approve'] ?? false) !== true) return ['redirect' => self::redirect($request, ['error' => 'access_denied'])];
		return Db::transaction(static function () use ($actor, $input, $request): array {
			$debate_id = Support::uuid($input['debate_id'] ?? '');
			$member = Debates::member($debate_id, $actor['user_id']);
			if ($member['left_at'] !== null || Debates::row($debate_id)['hidden']) throw new Failure(409, 'invalid_transition');
			$connection = Connections::create($actor, ['label' => $request['client']['name'], 'scopes' => $request['scope'], 'debate_id' => $debate_id], false);
			$code = Support::token();
			Db::run('INSERT INTO oauth_codes (token_hash, client_id, connection_id, redirect_uri, challenge, resource, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [Support::hash($code), $request['client']['id'], $connection['connection_id'], $request['redirect_uri'], $request['code_challenge'], $request['resource'], gmdate('Y-m-d H:i:s', time() + 120)]);
			return ['redirect' => self::redirect($request, ['code' => $code])];
		});
	}

	private static function redirect(array $request, array $parameters): string
	{
		if ($request['state'] !== '') $parameters['state'] = $request['state'];
		return $request['redirect_uri'] . (str_contains($request['redirect_uri'], '?') ? '&' : '?') . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
	}

	public static function token(array $input, string $ip, string $authorization = ''): array
	{
		Support::rate('oauth-token:' . $ip, 60, 60);
		$input = self::client_auth($input, $authorization);
		$input['resource'] ??= Config::url('/mcp');
		if (($input['resource'] ?? Config::url('/mcp')) !== Config::url('/mcp')) throw new Failure(400, 'invalid_target');
		$kind = $input['grant_type'] ?? '';
		if (!in_array($kind, ['authorization_code', 'refresh_token'], true)) throw new Failure(400, 'unsupported_grant_type');
		$result = Db::transaction(static function () use ($input, $kind): array {
			$table = $kind === 'authorization_code' ? 'oauth_codes' : 'mcp_tokens';
			$token = $input[$kind === 'authorization_code' ? 'code' : 'refresh_token'] ?? '';
			if (!is_string($token) || strlen($token) !== 43) throw new Failure(400, 'invalid_grant');
			$hash = Support::hash($token);
			$record = Db::one('SELECT * FROM ' . $table . ' WHERE token_hash = ?', [$hash]);
			if (!$record || $record['client_id'] !== ($input['client_id'] ?? '') || $record['resource'] !== $input['resource']) throw new Failure(400, 'invalid_grant');
			$connection = Db::one('SELECT * FROM mcp_connections WHERE id = ?', [$record['connection_id']]);
			Auth::active($connection['user_id'], true);
			$connection = Db::one('SELECT * FROM mcp_connections WHERE id = ? FOR UPDATE', [$connection['id']]);
			$record = Db::one('SELECT * FROM ' . $table . ' WHERE token_hash = ? FOR UPDATE', [$hash]);
			// The grant remains valid while any debate connection inside it is active.
			if (!Connections::members($connection['id']) || ($record['expires_at'] !== null && $record['expires_at'] < Support::now())) throw new Failure(400, 'invalid_grant');
			if ($kind === 'authorization_code') {
				$verifier = $input['code_verifier'] ?? '';
				if (!is_string($verifier) || !preg_match('/^[A-Za-z0-9._~-]{43,128}$/D', $verifier) || !hash_equals($record['challenge'], rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')) || $record['redirect_uri'] !== ($input['redirect_uri'] ?? '') || $record['used_at'] !== null) throw new Failure(400, 'invalid_grant');
			} elseif ($record['kind'] !== 'refresh') throw new Failure(400, 'invalid_grant');
			elseif ($record['used_at'] !== null) {
				Db::run('UPDATE mcp_connections SET revoked_at = COALESCE(revoked_at, ?) WHERE id = ? OR grant_id = ?', [Support::now(), $connection['id'], $connection['id']]);
				return ['error' => 'invalid_grant'];
			}
			if (isset($input['scope']) && Connections::scopes($input['scope']) !== $connection['scopes']) throw new Failure(400, 'invalid_scope');
			Db::run('UPDATE ' . $table . ' SET used_at = ? WHERE token_hash = ?', [Support::now(), $hash]);
			$access = Support::token(); $refresh = Support::token();
			foreach ([[$access, 'access', 3600], [$refresh, 'refresh', null]] as [$value, $token_kind, $lifetime]) Db::run('INSERT INTO mcp_tokens (token_hash, connection_id, client_id, kind, resource, expires_at) VALUES (?, ?, ?, ?, ?, ?)', [Support::hash($value), $connection['id'], $record['client_id'], $token_kind, $record['resource'], $lifetime === null ? null : gmdate('Y-m-d H:i:s', time() + $lifetime)]);
			return ['access_token' => $access, 'refresh_token' => $refresh, 'token_type' => 'Bearer', 'expires_in' => 3600, 'scope' => $connection['scopes']];
		});
		if (isset($result['error'])) throw new Failure(400, $result['error']);
		return $result;
	}

	private static function client_auth(array $input, string $authorization): array
	{
		$method = isset($input['client_secret']) ? 'client_secret_post' : 'none';
		if ($authorization !== '') {
			if (!preg_match('/^Basic ([A-Za-z0-9+\/=]+)$/D', $authorization, $match) || isset($input['client_secret'])) throw new Failure(400, 'invalid_client');
			$decoded = base64_decode($match[1], true);
			if ($decoded === false || !str_contains($decoded, ':')) throw new Failure(400, 'invalid_client');
			[$id, $secret] = array_map('urldecode', explode(':', $decoded, 2));
			if (isset($input['client_id']) && $input['client_id'] !== $id) throw new Failure(400, 'invalid_client');
			$input['client_id'] = $id; $input['client_secret'] = $secret; $method = 'client_secret_basic';
		}
		if (!is_string($input['client_id'] ?? null)) throw new Failure(400, 'invalid_client');
		$client = Db::one('SELECT auth_method, secret_hash FROM oauth_clients WHERE id = ?', [$input['client_id']]);
		if (!$client || $client['auth_method'] !== $method) throw new Failure(400, 'invalid_client');
		if ($method !== 'none' && (!is_string($input['client_secret'] ?? null) || !hash_equals($client['secret_hash'], Support::hash($input['client_secret'])))) throw new Failure(400, 'invalid_client');
		return $input;
	}

	public static function revoke(array $input, string $authorization = ''): void
	{
		$input = self::client_auth($input, $authorization);
		$token = is_string($input['token'] ?? null) ? $input['token'] : '';
		if (strlen($token) !== 43) return;
		Db::transaction(static function () use ($input, $token): void {
			$row = Db::one('SELECT t.connection_id, c.user_id FROM mcp_tokens t JOIN mcp_connections c ON c.id = t.connection_id WHERE t.token_hash = ? AND t.client_id = ?', [Support::hash($token), $input['client_id'] ?? '']);
			if (!$row) return;
			Db::one('SELECT id FROM users WHERE id = ? FOR UPDATE', [$row['user_id']]);
			// Disconnecting in the AI app ends every debate connection in that grant.
			Db::run('UPDATE mcp_connections SET revoked_at = COALESCE(revoked_at, ?) WHERE id = ? OR grant_id = ?', [Support::now(), $row['connection_id'], $row['connection_id']]);
		});
	}
}

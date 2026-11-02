<?php

declare(strict_types=1);

namespace Dilemma;

final class Auth
{
	public static function request_link(array $input, string $ip): void
	{
		if (!Config::participation_open()) throw new Failure(503, 'opening_soon');
		Support::rate('login:ip:' . $ip, 12, 900);
		$email = Support::email(Support::text($input, 'email', 3, 254));
		Support::rate('login:email:' . $email, 4, 900);
		$alias = Support::text($input, 'alias', 0, 60);
		$locale = ($input['locale'] ?? '') === 'en' ? 'en' : 'sv';
		$return = self::return_target(Support::text($input, 'return_to', 0, 4096));
		Db::transaction(static function () use ($email, $alias, $locale, $input, $return): void {
			$user = Db::one('SELECT * FROM users WHERE email = ? FOR UPDATE', [$email]);
			if (!$user) {
				if ($alias === '' || ($input['accept_terms'] ?? false) !== true) {
					// The same outward response protects account enumeration.
					return;
				}
				$id = Support::id();
				Db::run('INSERT INTO users (id, email, alias, locale, created_at) VALUES (?, ?, ?, ?, ?)', [$id, $email, $alias, $locale, Support::now()]);
				$user = Db::one('SELECT * FROM users WHERE id = ?', [$id]);
			}
			if ($user['status'] !== 'active') return;
			$token = Support::token();
			Db::run('INSERT INTO email_verifications (token_hash, user_id, expires_at, return_target) VALUES (?, ?, ?, ?)', [Support::hash($token), $user['id'], gmdate('Y-m-d H:i:s', time() + 900), $return !== '' ? Support::seal($return) : null]);
			Mail::enqueue('auth:' . Support::hash($token), 'login', $user['email'], $user['locale'], ['url' => Config::url('/verify#' . $token)], $user['id']);
		});
	}

	public static function verify(string $token): array
	{
		if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $token)) throw new Failure(400, 'expired_link');
		return Db::transaction(static function () use ($token): array {
			$link = Db::one('SELECT * FROM email_verifications WHERE token_hash = ? FOR UPDATE', [Support::hash($token)]);
			if (!$link || $link['used_at'] !== null || $link['expires_at'] < Support::now()) throw new Failure(400, 'expired_link');
			$user = Db::one('SELECT * FROM users WHERE id = ? FOR UPDATE', [$link['user_id']]);
			if (!$user || $user['status'] !== 'active') throw new Failure(403, 'access_denied');
			Db::run('UPDATE email_verifications SET used_at = ? WHERE token_hash = ?', [Support::now(), Support::hash($token)]);
			Db::run('UPDATE users SET verified_at = COALESCE(verified_at, ?) WHERE id = ?', [Support::now(), $user['id']]);
			$session = Support::token();
			$csrf = Support::token();
			Db::run('INSERT INTO auth_sessions (token_hash, user_id, csrf, expires_at, created_at) VALUES (?, ?, ?, ?, ?)', [Support::hash($session), $user['id'], $csrf, gmdate('Y-m-d H:i:s', time() + 604800), Support::now()]);
			return ['token' => $session, 'csrf' => $csrf, 'user_id' => $user['id'], 'redirect' => $link['return_target'] ? self::return_target(Support::unseal($link['return_target'])) : '/app'];
		});
	}

	private static function return_target(string $path): string
	{
		if ($path === '' || $path === '/app') return $path;
		if (preg_match('~^/invite#[A-Za-z0-9_-]{43}$~D', $path)) return $path;
		if (str_starts_with($path, '/oauth/authorize?') && !str_contains($path, '#') && !strpbrk($path, "\r\n")) {
			parse_str(substr($path, strlen('/oauth/authorize?')), $query);
			OAuth::request($query);
			return $path;
		}
		throw new Failure(422, 'invalid_field', ['field' => 'return_to']);
	}

	public static function session(?string $token): ?array
	{
		if (!$token || !preg_match('/^[A-Za-z0-9_-]{43}$/D', $token)) return null;
		return Db::one("SELECT u.*, s.csrf, s.token_hash AS session_hash FROM auth_sessions s JOIN users u ON u.id = s.user_id WHERE s.token_hash = ? AND s.expires_at > UTC_TIMESTAMP() AND u.status = 'active' AND u.verified_at IS NOT NULL", [Support::hash($token)]);
	}

	public static function require_user(?array $user): array
	{
		if (!$user) throw new Failure(401, 'login_required');
		return $user;
	}

	public static function csrf(array $user, string $provided): void
	{
		if ($provided === '' || !hash_equals($user['csrf'], $provided)) throw new Failure(403, 'csrf_failed');
	}

	public static function active(string $id, bool $lock = false): array
	{
		$user = Db::one('SELECT * FROM users WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
		if (!$user || $user['status'] !== 'active' || $user['verified_at'] === null) throw new Failure(403, 'access_denied');
		return $user;
	}

	public static function cookie(string $token): void
	{
		setcookie('dilemma_session', $token, ['expires' => $token === '' ? time() - 3600 : time() + 604800, 'path' => '/', 'secure' => Config::production(), 'httponly' => true, 'samesite' => 'Lax']);
	}

	public static function logout(array $user): void
	{
		Db::run('DELETE FROM auth_sessions WHERE token_hash = ?', [$user['session_hash']]);
		self::cookie('');
	}
}

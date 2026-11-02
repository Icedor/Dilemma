<?php

declare(strict_types=1);

namespace Dilemma;

final class Http
{
	public static function run(): void
	{
		ini_set('display_errors', '0');
		ini_set('log_errors', '1');
		ini_set('error_log', Config::storage('application.log'));
		header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; worker-src 'self'; manifest-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
		header('X-Content-Type-Options: nosniff');
		header('Referrer-Policy: no-referrer');
		header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
		header('Cache-Control: no-store');
		if (Config::production()) header('Strict-Transport-Security: max-age=31536000');
		$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		$user = null;
		try {
			if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 196608) throw new Failure(413, 'request_too_large');
			$host = parse_url(Config::url(), PHP_URL_HOST) . (parse_url(Config::url(), PHP_URL_PORT) ? ':' . parse_url(Config::url(), PHP_URL_PORT) : '');
			if (strtolower($_SERVER['HTTP_HOST'] ?? '') !== strtolower($host)) throw new Failure(400, 'invalid_host');
			if (Config::production() && ($_SERVER['HTTPS'] ?? '') !== 'on') throw new Failure(400, 'https_required');
			if ($path === '/mcp') {
				$response = Mcp::handle($method, getallheaders(), file_get_contents('php://input', false, null, 0, 196609));
				foreach ($response['headers'] as $name => $value) header($name . ': ' . $value);
				self::json($response['body'], $response['status']);
			}
			if ($method === 'GET' && $path === '/.well-known/oauth-authorization-server') self::json(OAuth::metadata());
			if ($method === 'GET' && in_array($path, ['/.well-known/oauth-protected-resource', '/.well-known/oauth-protected-resource/mcp'], true)) self::json(['resource' => Config::url('/mcp'), 'authorization_servers' => [Config::url()], 'scopes_supported' => [Connections::READ, Connections::WRITE], 'bearer_methods_supported' => ['header']]);
			if ($method === 'POST' && in_array($path, ['/oauth/register', '/oauth/token', '/oauth/revoke'], true)) {
				$input = $path === '/oauth/register' || str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? self::input() : $_POST;
				if ($path === '/oauth/register') self::json(OAuth::register($input, $ip), 201);
				if ($path === '/oauth/token') self::json(OAuth::token($input, $ip));
				Support::rate('oauth-revoke:' . $ip, 60, 60);
				OAuth::revoke($input); self::json((object) []);
			}
			if ($method === 'GET' && $path === '/health') { Db::one('SELECT 1'); self::json(['status' => 'ok', 'version' => '0.1.0']); }
			$user = Auth::session(is_string($_COOKIE['dilemma_session'] ?? null) ? $_COOKIE['dilemma_session'] : null);
			$actor = $user ? ['user_id' => $user['id'], 'channel' => 'human'] : null;
			$explicit_locale = in_array($_GET['lang'] ?? null, ['sv', 'en'], true) ? $_GET['lang'] : null;
			$cookie_locale = in_array($_COOKIE['dilemma_locale'] ?? null, ['sv', 'en'], true) ? $_COOKIE['dilemma_locale'] : null;
			$locale = $explicit_locale ?? $cookie_locale ?? I18n::negotiate($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
			if ($explicit_locale !== null) setcookie('dilemma_locale', $locale, ['expires' => time() + 31536000, 'path' => '/', 'secure' => Config::production(), 'httponly' => true, 'samesite' => 'Lax']);
			I18n::locale($locale);
			if (str_starts_with($path, '/api/')) self::api($method, $path, $user, $actor, $ip);
			if ($method !== 'GET') throw new Failure(405, 'method_not_allowed');
			if ($path === '/connect') {
				Auth::require_user($user);
				$target = '/app';
				if (isset($_GET['debate'])) { $id = Support::uuid($_GET['debate']); Debates::member($id, $user['id']); $target = '/debate/' . $id . '#connect-ai'; }
				header('Location: ' . $target, true, 303); exit;
			}
			if ($path === '/robots.txt') { header('Content-Type: text/plain'); echo Config::production() ? "User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /app\nDisallow: /admin\nDisallow: /oauth/\nDisallow: /connect\nDisallow: /settings\nSitemap: " . Config::url('/sitemap.xml') . "\n" : "User-agent: *\nDisallow: /\n"; exit; }
			if (in_array($path, ['/sitemap.xml', '/sitemap-pages.xml'], true)) { header('Content-Type: application/xml; charset=UTF-8'); echo Sitemap::render($path === '/sitemap.xml', Support::integer($_GET['page'] ?? 1, 1, 100000, 'page')); exit; }
			header('Content-Type: text/html; charset=UTF-8');
			echo Views::page($path, $user, $actor);
		} catch (Failure $error) {
			if (str_starts_with($path, '/api/') || (str_starts_with($path, '/oauth/') && $path !== '/oauth/authorize')) self::json(['error' => $error->error, 'message' => I18n::t($error->error), 'details' => (object) $error->details], $error->status);
			http_response_code($error->status);
			echo Views::error($error->status, $error->error, $user);
		} catch (\Throwable $error) {
			$incident = bin2hex(random_bytes(8));
			error_log('Dilemma incident ' . $incident . ': ' . get_class($error));
			if (str_starts_with($path, '/api/') || $path === '/mcp' || (str_starts_with($path, '/oauth/') && $path !== '/oauth/authorize')) self::json(['error' => 'unavailable', 'incident' => $incident], 503);
			http_response_code(503);
			echo '<!doctype html><html lang="sv"><meta charset="utf-8"><title>Dilemma</title><h1>Dilemma</h1><p>Tjänsten är tillfälligt otillgänglig. / Temporarily unavailable.</p><p>' . $incident . '</p></html>';
		}
	}

	private static function api(string $method, string $path, ?array $user, ?array $actor, string $ip): never
	{
		if ($method === 'GET') {
			if ($path === '/api/push') { Auth::require_user($user); self::json(Push::status($user)); }
			if ($path === '/api/i18n') self::json(I18n::all());
			if ($path === '/api/debates') self::json(ReadModel::list($_GET));
			if ($path === '/api/mine') { Auth::require_user($user); self::json(ReadModel::list($_GET, $actor)); }
			if ($path === '/api/export') { Auth::require_user($user); Support::rate('export:' . $user['id'], 4, 3600); header('Content-Disposition: attachment; filename="dilemma-export.json"'); self::json(Accounts::export($actor)); }
			if (preg_match('~^/api/arguments/([a-f0-9-]{36})$~D', $path, $match)) self::json(ReadModel::argument($match[1], $actor));
			if (preg_match('~^/api/debates/([a-f0-9-]{36})/events$~D', $path, $match)) self::json(ReadModel::updates($match[1], $actor, Support::integer($_GET['after'] ?? 0, 0, PHP_INT_MAX, 'after'), Support::text($_GET, 'kind', 0, 40)));
			throw new Failure(404, 'not_found');
		}
		if ($method !== 'POST') throw new Failure(405, 'method_not_allowed');
		if (($_SERVER['HTTP_ORIGIN'] ?? '') !== Config::url()) throw new Failure(403, 'csrf_failed');
		$input = self::input();
		if ($path === '/api/login') { Auth::request_link($input, $ip); self::json(['sent' => true]); }
		if ($path === '/api/verify') {
			Support::rate('verify:' . $ip, 20, 900);
			$session = Auth::verify(Support::text($input, 'token', 43, 43));
			Auth::cookie($session['token']); self::json(['redirect' => $session['redirect']]);
		}
		Auth::require_user($user);
		Auth::csrf($user, $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
		Support::rate('api:' . $user['id'], 120, 60);
		if ($path === '/api/push/subscribe') self::json(Push::subscribe($user, $input));
		if ($path === '/api/push/disable') self::json(Push::disable($user));
		if ($path === '/api/logout') { Auth::logout($user); self::json(['redirect' => '/']); }
		if ($path === '/api/debates') self::json(Debates::create($actor, $input), 201);
		if ($path === '/api/invitation') self::json(Debates::invitation($actor, Support::text($input, 'token', 43, 43)));
		if ($path === '/api/accept') self::json(Debates::accept($actor, $input));
		if ($path === '/api/profile') self::json(Accounts::update($actor, $input));
		if ($path === '/api/delete') { $result = Accounts::delete($actor, $input); Auth::cookie(''); self::json($result); }
		if ($path === '/api/connections') self::json(Connections::create($actor, $input), 201);
		if ($path === '/api/connections/setup') self::json(Connections::setup($actor, $input), 201);
		if ($path === '/api/oauth/authorize') self::json(OAuth::authorize($actor, $input));
		if ($path === '/api/admin') self::json(Moderation::apply($actor, $input));
		if (preg_match('~^/api/connections/([a-f0-9-]{36})/revoke$~D', $path, $match)) self::json(Connections::revoke($actor, $match[1]));
		if (preg_match('~^/api/arguments/([a-f0-9-]{36})/amend$~D', $path, $match)) self::json(Arguments::amend($actor, $match[1], $input));
		if (preg_match('~^/api/debates/([a-f0-9-]{36})/(argument|action|invite|report)$~D', $path, $match)) {
			$result = match ($match[2]) { 'argument' => Arguments::submit($actor, $match[1], $input), 'action' => DebateActions::apply($actor, $match[1], $input), 'invite' => Debates::invite($actor, $match[1], $input), 'report' => Moderation::report($actor, $match[1], $input) };
			self::json($result);
		}
		throw new Failure(404, 'not_found');
	}

	private static function input(): array
	{
		if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) throw new Failure(415, 'json_required');
		$body = file_get_contents('php://input', false, null, 0, 196609);
		if (strlen($body) > 196608) throw new Failure(413, 'request_too_large');
		try { $value = json_decode($body, false, 32, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new Failure(400, 'invalid_json'); }
		if (!is_object($value)) throw new Failure(400, 'invalid_json');
		return json_decode($body, true, 32, JSON_THROW_ON_ERROR);
	}

	public static function json(mixed $data, int $status = 200): never
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=UTF-8');
		if ($data !== null) echo Support::json($data);
		exit;
	}
}

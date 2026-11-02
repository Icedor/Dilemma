<?php

declare(strict_types=1);

putenv('APP_URL=http://127.0.0.1:8788');
require __DIR__ . '/bootstrap.php';

use Dilemma\{Auth, Config, Db, OAuth, Support};

function http_request(string $path, ?array $input = null, array $headers = [], string $cookie = ''): array {
	$curl = curl_init(Config::url($path));
	$response_headers = [];
	curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIE => $cookie, CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $input !== null ? ['Content-Type: application/json', 'Origin: ' . Config::url()] : [], $headers), CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$response_headers): int {
		if (str_contains($line, ':')) { [$name, $value] = explode(':', $line, 2); $response_headers[strtolower($name)][] = trim($value); }
		return strlen($line);
	}]);
	if ($input !== null) { curl_setopt($curl, CURLOPT_POST, true); curl_setopt($curl, CURLOPT_POSTFIELDS, Support::json((object) $input)); }
	$body = curl_exec($curl);
	if ($body === false) throw new RuntimeException('Loopback HTTP request failed.');
	$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
	return ['status' => $status, 'body' => $body, 'headers' => $response_headers, 'json' => json_decode($body, true)];
}

$directory = sys_get_temp_dir() . '/dilemma-http-' . Support::id();
mkdir($directory, 0700);
mkdir($directory . '/empty', 0700);
$pipes = [];
$process = proc_open([PHP_BINARY, '-S', '127.0.0.1:8788', '-t', $directory . '/empty', dirname(__DIR__) . '/bin/preview.php'], [0 => ['pipe', 'r'], 1 => ['file', $directory . '/stdout.log', 'a'], 2 => ['file', $directory . '/stderr.log', 'a']], $pipes, dirname(__DIR__), null, ['bypass_shell' => true, 'create_no_window' => true]);
if (!is_resource($process)) throw new RuntimeException('Cannot start test HTTP server.');
fclose($pipes[0]);
try {
	$ready = false;
	for ($i = 0; $i < 30; $i++) { $socket = @fsockopen('127.0.0.1', 8788, $number, $message, 0.1); if ($socket) { fclose($socket); $ready = true; break; } usleep(50000); }
	check($ready && proc_get_status($process)['running'], 'Isolated loopback HTTP server starts');
	$home = http_request('/');
	check($home['status'] === 200 && str_contains($home['body'], '<html lang="sv">'), 'Swedish home is server rendered');
	check(str_contains($home['headers']['content-security-policy'][0], "frame-ancestors 'none'") && $home['headers']['cache-control'][0] === 'no-store', 'HTTP responses enforce CSP and no-store caching');
	check(http_request('/?lang=en')['status'] === 200 && str_contains(http_request('/?lang=en')['body'], '<html lang="en">'), 'English language selection changes server rendering');
	foreach (['en-US,en;q=0.9' => 'en', 'en;q=0.5,sv-SE;q=0.9' => 'sv', 'fr-FR' => 'en', 'sv;q=0,en;q=0.2' => 'en'] as $accept => $expected) check(str_contains(http_request('/', null, ['Accept-Language: ' . $accept])['body'], '<html lang="' . $expected . '">'), 'Browser language negotiation: ' . $accept);
	check(str_contains(http_request('/', null, ['Accept-Language: en'], 'dilemma_locale=sv')['body'], '<html lang="sv">'), 'Manual language cookie wins over browser language');
	check(str_contains(http_request('/?lang=en', null, [], 'dilemma_locale=sv')['body'], '<html lang="en">'), 'New manual language selection wins over saved cookie');
	check(str_contains(http_request('/?lang%5B%5D=en', null, ['Accept-Language: sv'])['body'], '<html lang="sv">'), 'Malformed language parameter safely falls back');
	check(http_request('/manifest.webmanifest')['json']['display'] === 'standalone', 'Install manifest is served as JSON');
	check(http_request('/sw.js')['status'] === 200 && http_request('/offline.html')['status'] === 200, 'Worker and offline fallback are accessible');
	check(http_request('/api/push')['status'] === 401, 'Push settings require a human session');
	check(http_request('/health')['json']['status'] === 'ok', 'Health route checks the database');
	foreach (['/.env', '/app/bootstrap.php', '/database/migrations/001_initial.sql', '/tests/run.php', '/var/mail', '/.git/config'] as $path) check(http_request($path)['status'] === 404, 'Private path is inaccessible: ' . $path);
	check(http_request('/assets/app.js')['status'] === 200, 'Only allowlisted preview assets are served');
	check(http_request('/', null, ['Host: attacker.test'])['status'] === 400, 'Unrecognized Host is rejected');
	check(http_request('/api/mine')['status'] === 401, 'Private HTTP API requires a session');
	check(http_request('/app')['status'] === 401, 'Participant UI requires a session');
	check(http_request('/api/login', ['email' => 'unused@example.test'], ['Origin: https://attacker.test'])['status'] === 403, 'Cross-origin login is rejected');
	$a = user_fixture('HTTP'); $cookie = 'dilemma_session=' . $a['session']['token'];
	$session_headers = ['X-CSRF-Token: ' . $a['session']['csrf']];
	check(http_request('/api/connections/setup', ['debate_id' => Support::id()])['status'] === 401, 'Copyable AI setup requires a human login');
	check(http_request('/api/connections/setup', ['debate_id' => Support::id()], [], $cookie)['status'] === 403, 'Copyable AI setup requires CSRF protection');
	$setup_debate = debate_fixture($a['actor'], user_fixture('HTTPB')['actor']);
	$setup = http_request('/api/connections/setup', ['debate_id' => $setup_debate['id']], $session_headers, $cookie);
	check($setup['status'] === 201 && str_contains($setup['json']['setup_text'], 'Authorization: Bearer ') && str_contains($setup['json']['setup_text'], $setup_debate['id']) && $setup['headers']['cache-control'][0] === 'no-store', 'Authenticated HTTP setup returns complete instructions with no-store');
	$connect = http_request('/connect?debate=' . $setup_debate['id'], null, [], $cookie);
	check($connect['status'] === 200 && str_contains($connect['body'], 'connection-setup-form') && str_contains($connect['body'], 'data-private-setup') && !str_contains($connect['body'], 'Authorization: Bearer '), 'Connection page preselects the debate without exposing a previously issued credential');
	check(http_request('/api/push/subscribe', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/invalid', 'consent' => true], [], $cookie)['status'] === 403, 'Push subscription requires CSRF protection');
	check(http_request('/api/profile', ['alias' => 'New alias'], [], $cookie)['status'] === 403, 'Session alone cannot bypass CSRF');
	check(http_request('/settings', null, [], $cookie)['status'] === 200, 'Authenticated settings render through HTTP');
	check(http_request('/api/profile', ['alias' => 'New alias', 'locale' => 'en'], $session_headers, $cookie)['status'] === 200, 'Valid session and CSRF can update a profile');
	check(http_request('/api/connections', ['label' => 'Invalid input', 'scopes' => ['bad']], $session_headers, $cookie)['status'] === 400, 'Malformed scope is a client error');
	check(http_request('/api/debates?q%5B%5D=bad')['status'] === 422, 'Malformed query is rejected without server failure');
	check(http_request('/oauth/register', ['client_name' => 'Invalid client', 'redirect_uris' => ['https://client.example.test/callback'], 'grant_types' => 'bad'])['status'] === 400, 'Malformed OAuth metadata is rejected');
	$client = OAuth::register(['client_name' => 'HTTP test', 'redirect_uris' => ['https://client.example.test/callback']], 'http-' . $run);
	$params = ['client_id' => $client['client_id'], 'redirect_uri' => $client['redirect_uris'][0], 'response_type' => 'code', 'code_challenge_method' => 'S256', 'code_challenge' => Support::token(), 'resource' => Config::url('/mcp'), 'scope' => 'debates:read', 'state' => 'http-fixture'];
	$return = '/oauth/authorize?' . http_build_query($params);
	$authorize = http_request($return);
	check($authorize['status'] === 401 && str_contains($authorize['body'], 'data-auth-return='), 'OAuth login offers a preserved authorization return');
	$email = 'qa-http-' . $run . '@example.test';
	check(http_request('/api/login', ['email' => $email, 'alias' => 'HTTP login', 'accept_terms' => true, 'return_to' => $return])['status'] === 200, 'Magic-link request accepts a validated OAuth destination');
	$account = Db::one('SELECT id FROM users WHERE email = ?', [$email]);
	$outbox = Db::one("SELECT encrypted_payload FROM notification_outbox WHERE user_id = ? AND kind = 'login'", [$account['id']]);
	$payload = json_decode(Support::unseal($outbox['encrypted_payload']), true);
	$token = parse_url($payload['data']['url'], PHP_URL_FRAGMENT);
	$verify = http_request('/api/verify', ['token' => $token]);
	check($verify['status'] === 200 && $verify['json']['redirect'] === $return, 'Email opened in a new tab retains OAuth destination without browser storage');
	check(str_contains($verify['headers']['set-cookie'][0], 'HttpOnly') && str_contains(strtolower($verify['headers']['set-cookie'][0]), 'samesite=lax'), 'Authentication cookie is HttpOnly and SameSite Lax');
	check(http_request('/api/login', ['email' => 'qa-return-' . $run . '@example.test', 'alias' => 'Return test', 'accept_terms' => true, 'return_to' => '//attacker.test'])['status'] === 422, 'Login return cannot become an open redirect');
	$invite = '/invite#' . Support::token();
	Auth::request_link(['email' => 'qa-invite-' . $run . '@example.test', 'alias' => 'Invite test', 'accept_terms' => true, 'return_to' => $invite], 'invite-' . $run);
	$record = Db::one('SELECT v.return_target FROM email_verifications v JOIN users u ON u.id = v.user_id WHERE u.email = ?', ['qa-invite-' . $run . '@example.test']);
	check(!str_contains($record['return_target'], $invite) && Support::unseal($record['return_target']) === $invite, 'Invitation continuation is encrypted at rest');
	check(http_request('/api/logout', [], $session_headers, $cookie)['status'] === 200 && http_request('/settings', null, [], $cookie)['status'] === 401, 'Logout revokes the stored session');
	echo 'SUCCESS: ' . $checks . " HTTP checks. Loopback only.\n";
} finally {
	proc_terminate($process);
	proc_close($process);
	foreach (['stdout.log', 'stderr.log'] as $file) if (is_file($directory . '/' . $file)) unlink($directory . '/' . $file);
	rmdir($directory . '/empty'); rmdir($directory);
}

<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
putenv('DB_NAME=dilemma_test');
require dirname(__DIR__) . '/app/bootstrap.php';

use Dilemma\{Accounts, Arguments, Auth, Config, Connections, Db, DebateActions, Debates, Failure, I18n, Mail, Mcp, Moderation, OAuth, ReadModel, Support, ToolRegistry, Views};

if (Config::production() || Config::get('DB_PORT') !== '3317' || Config::get('DB_HOST') !== '127.0.0.1' || Config::get('DB_NAME') !== 'dilemma_test') throw new RuntimeException('Tests require the isolated local test database.');
require dirname(__DIR__) . '/bin/migrate.php';

$checks = 0;
$run = bin2hex(random_bytes(5));
function check(bool $condition, string $name): void {
	global $checks;
	if (!$condition) throw new RuntimeException('FAILED: ' . $name);
	$checks++;
	echo 'PASS ' . $name . PHP_EOL;
}
function rejects(callable $operation, int $status, string $name, ?string $error = null): void {
	try { $operation(); } catch (Failure $failure) { check($failure->status === $status && ($error === null || $failure->error === $error), $name); return; }
	throw new RuntimeException('FAILED: ' . $name . ' did not reject');
}
function user_fixture(string $label): array {
	global $run;
	$email = strtolower('qa-' . $run . '-' . $label . '@example.test');
	Auth::request_link(['email' => $email, 'alias' => 'Test ' . $label, 'locale' => $label === 'B' ? 'en' : 'sv', 'accept_terms' => true], 'fixture-' . $run . '-' . $label);
	$user = Db::one('SELECT * FROM users WHERE email = ?', [$email]);
	$outbox = Db::one('SELECT encrypted_payload FROM notification_outbox WHERE user_id = ? AND kind = ?', [$user['id'], 'login']);
	$payload = json_decode(Support::unseal($outbox['encrypted_payload']), true, 32, JSON_THROW_ON_ERROR);
	$token = parse_url($payload['data']['url'], PHP_URL_FRAGMENT);
	$session = Auth::verify($token);
	return ['actor' => ['user_id' => $user['id'], 'channel' => 'human'], 'session' => $session, 'email' => $email, 'login_token' => $token];
}
function debate_fixture(array $a, array $b, bool $public_b = true): array {
	$draft = Debates::create($a, ['idempotency_key' => Support::token(), 'title' => 'Should public parks remain open at night?', 'topic' => 'City life', 'thesis' => 'Should public parks remain open throughout the night?', 'stance' => 'Parks should remain accessible.', 'left_label' => 'Close at night', 'right_label' => 'Open all night', 'locale' => 'en', 'notify' => true, 'processing_consent' => true, 'public_consent' => true, 'position_consent' => true, 'position' => 0]);
	$invitation = Debates::invite($a, $draft['debate_id'], ['idempotency_key' => Support::token()]);
	$token = parse_url($invitation['invitation_url'], PHP_URL_FRAGMENT);
	$accepted = Debates::accept($b, ['token' => $token, 'idempotency_key' => Support::token(), 'stance' => 'Closing parks could reduce disturbance.', 'accept_terms' => true, 'notify' => true, 'processing_consent' => true, 'public_consent' => $public_b, 'position_consent' => false, 'position' => null]);
	return ['id' => $draft['debate_id'], 'revision' => $accepted['revision']];
}
function actor_token(array $actor, bool $write, ?string $id = null): array {
	$result = Connections::create($actor, ['label' => 'Protocol fixture', 'scopes' => $write ? 'debates:read arguments:write' : 'debates:read', 'debate_id' => $id]);
	return ['token' => $result['access_token'], 'actor' => Connections::authenticate('Bearer ' . $result['access_token']), 'id' => $result['connection_id']];
}

// Exercise the short clipboard bootstrap followed by the server's startup protocol.
function setup_requests(string $setup_text, string $debate_id): array {
	$requests = [];
	foreach (explode("\n", $setup_text) as $line) {
		if (!str_starts_with($line, '{')) continue;
		$request = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
		$request['params']['capabilities'] = (object) [];
		$requests[] = $request;
	}
	$requests[] = ['jsonrpc'=>'2.0', 'id'=>2, 'method'=>'tools/list'];
	$calls = [
		['name'=>'dilemma_whoami', 'arguments'=>(object) []],
		['name'=>'dilemma_test_connection', 'arguments'=>(object) []],
		['name'=>'dilemma_test_write', 'arguments'=>['idempotency_key'=>Support::id()]],
		['name'=>'dilemma_get_debate', 'arguments'=>['debate_id'=>$debate_id]],
	];
	foreach ($calls as $index => $params) $requests[] = ['jsonrpc'=>'2.0', 'id'=>$index + 3, 'method'=>'tools/call', 'params'=>$params];
	return $requests;
}

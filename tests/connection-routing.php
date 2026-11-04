<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Dilemma\{AiSetup,Connections,Db,Debates,Mcp,ReadModel,Support,ToolRegistry};

$a = user_fixture('RoutingA'); $b = user_fixture('RoutingB');
$first = debate_fixture($a['actor'], $b['actor'], false);
$second = debate_fixture($a['actor'], $b['actor'], false);
$access = static function (array $human, string $id): array {
	$setup = Connections::current($human, $id);
	preg_match('/Authorization: Bearer ([A-Za-z0-9_-]{43})/', $setup['setup_text'], $match);
	return [$setup, $match[1], Connections::authenticate('Bearer ' . $match[1])];
};
[$old, $old_token, $old_actor] = $access($a['actor'], $first['id']);
[$right, $token, $actor] = $access($a['actor'], $second['id']);
[, , $opponent] = $access($b['actor'], $second['id']);
$identity = ToolRegistry::invoke($old_actor, 'dilemma_whoami', (object) []);
check($identity['debate_id'] === $first['id'] && $identity['debate_id'] !== $second['id'] && $identity['user_id'] === $a['actor']['user_id'], 'Same human in two debates can detect the wrong connection without a forbidden read');
check(!isset($identity['email']) && !str_contains(Support::json($identity), $old_token), 'Connection identity excludes email and credentials');
check(ToolRegistry::invoke($opponent, 'dilemma_whoami', (object) [])['user_id'] !== $actor['user_id'], 'Same debate with the other participant is distinguishable');
check(Db::one('SELECT write_test_at FROM mcp_connections WHERE id=?', [$old['connection_id']])['write_test_at'] === null, 'Identity lookup does not test writes on the unrelated connection');

$headers = ['Content-Type'=>'application/json', 'MCP-Protocol-Version'=>Mcp::LEGACY, 'Authorization'=>'Bearer '.$token];
$call = static fn (array $request): array => Mcp::handle('POST', $headers, Support::json($request));
$methods = [];
foreach (explode("\n", $right['setup_text']) as $line) {
	if (!str_starts_with($line, '{')) continue;
	$request = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
	$name = $request['params']['name'] ?? $request['method']; $methods[] = $name;
	if ($name === 'dilemma_test_write') $request['params']['arguments']['idempotency_key'] = Support::id();
	if (isset($request['params']['arguments']) && !$request['params']['arguments']) $request['params']['arguments'] = (object) [];
	if (isset($request['params']['capabilities'])) $request['params']['capabilities'] = (object) [];
	$result = $call($request);
	check($result['status'] === 200 && isset($result['body']['result']) && empty($result['body']['result']['isError']), 'Copied direct setup works: ' . $name);
		if ($name === 'initialize') check(str_contains($result['body']['result']['instructions'], $second['id']) && str_contains($result['body']['result']['instructions'], $actor['user_id']) && !str_contains($result['body']['result']['instructions'], 'RoutingA'), 'Initialization advertises the actual scope without promoting a display name into instructions');
	if ($name === 'dilemma_whoami') check($result['body']['result']['structuredContent']->connection_id === $right['connection_id'] && $result['body']['result']['structuredContent']->debate_id === $second['id'], 'Pasted credential selects the requested connection while an old connection exists');
	if ($name === 'dilemma_test_connection' || $name === 'dilemma_test_write') check($result['body']['result']['structuredContent']->debate_id === $second['id'], 'Test receipt identifies its debate: ' . $name);
}
check(array_search('dilemma_whoami', $methods) < array_search('dilemma_test_write', $methods), 'Copied protocol verifies identity before any write test');
check((int)Db::one('SELECT COUNT(*) AS n FROM debate_messages WHERE debate_id IN (?,?)', [$first['id'], $second['id']])['n'] === 0, 'Connection checks never publish an argument');
$wrong_headers = array_replace($headers, ['Authorization'=>'Bearer '.$old_token]);
$denied = Mcp::handle('POST', $wrong_headers, Support::json(['jsonrpc'=>'2.0','id'=>20,'method'=>'tools/call','params'=>['name'=>'dilemma_get_debate','arguments'=>['debate_id'=>$second['id']]]]));
$error = json_decode($denied['body']['result']['content'][0]['text'], true);
check($denied['body']['result']['isError'] && $error['status'] === 403 && $error['error'] === 'connection_debate_mismatch' && $error['details']['connected_debate_id'] === $first['id'], 'Wrong connection remains denied with a precise, scoped diagnosis');
check(!str_contains(Support::json($error), $second['id']) && !str_contains(Support::json($error), $token), 'Mismatch error reveals no target debate metadata or credential');
rejects(fn()=>ReadModel::debate($first['id'], $actor), 403, 'Direct fallback never broadens scope');
foreach (ToolRegistry::tools($actor) as $tool) check(str_contains($tool['description'], $second['id']), 'Tool advertises its own debate: ' . $tool['name']);
foreach (['sv','en'] as $locale) {
	$native = AiSetup::connector($second['id'], $locale, $actor['user_id']);
	check(str_contains($native, $actor['user_id']) && !str_contains($native, 'Bearer '), 'Native setup checks participant identity without a secret: ' . $locale);
}
check(str_contains($right['setup_text'], 'Expected connection ID: '.$right['connection_id']) && str_contains($right['setup_text'], 'Expected user ID: '.$actor['user_id']), 'Direct setup pins both connection and participant');

Connections::revoke($a['actor'], $right['connection_id']);
$renewed = Connections::setup($a['actor'], ['debate_id'=>$second['id'], 'renew'=>true]);
rejects(fn()=>Connections::authenticate('Bearer '.$token), 401, 'Reported key is invalid after scoped revocation');
check(Connections::authenticate('Bearer '.$old_token)['debate_id'] === $first['id'], 'Other debate access survives key replacement');
check($renewed['connection_id'] !== $right['connection_id'] && $renewed['expires_at'] === null && str_contains($renewed['setup_text'], 'Expected connection ID: '.$renewed['connection_id']), 'Replacement stays persistent and supplies the new exact binding');
echo 'SUCCESS: '.$checks." connection routing checks. Isolated database only.\n";

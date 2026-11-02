<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Dilemma\{Connections, Db, Debates, DebateActions, I18n, ReadModel, Support, ToolRegistry};

$a = user_fixture('A'); $b = user_fixture('B'); $outsider = user_fixture('C');
$debate = debate_fixture($a['actor'], $b['actor']);
$other = debate_fixture($a['actor'], $outsider['actor']);
rejects(fn () => Connections::setup($outsider['actor'], ['debate_id' => $debate['id']]), 404, 'Another account cannot create access to this debate');
rejects(fn () => Connections::setup($a['actor'], []), 404, 'Setup cannot create unrestricted account access');
$setup = Connections::setup($a['actor'], ['debate_id' => $debate['id'], 'scopes' => 'admin', 'expires_in' => 999999999]);
preg_match('/Authorization: Bearer ([A-Za-z0-9_-]{43})/', $setup['setup_text'], $match);
$token = $match[1] ?? '';
check(strlen($token) === 43, 'Copyable setup contains complete bearer authentication');
$actor = Connections::authenticate('Bearer ' . $token);
$headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token];
foreach (\Dilemma\Mcp::LEGACY_VERSIONS as $version) {
	$init = \Dilemma\Mcp::handle('POST', $headers, Support::json(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => $version]]));
	$list = \Dilemma\Mcp::handle('POST', $headers + ['MCP-Protocol-Version' => $version], Support::json(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']));
	check(($init['body']['result']['protocolVersion'] ?? '') === $version && $list['status'] === 200 && in_array('dilemma_submit_argument', array_column($list['body']['result']['tools'] ?? [], 'name')), 'Copyable connection negotiates and exposes tools for ' . $version);
}
$unknown = \Dilemma\Mcp::handle('POST', $headers + ['MCP-Protocol-Version' => '2099-01-01'], Support::json(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/list']));
check($unknown['status'] === 400, 'Unsupported request versions remain rejected');
$missing = \Dilemma\Mcp::handle('POST', $headers, Support::json(['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/list', 'params' => ['_meta' => ['io.modelcontextprotocol/protocolVersion' => \Dilemma\Mcp::MODERN]] ]));
check($missing['status'] === 400, 'Modern metadata cannot silently fall back when the required header is absent');
check($actor['debate_id'] === $debate['id'] && $actor['scopes'] === 'arguments:write debates:read', 'Setup is bound to one debate and fixed read/write scopes');
$stored = Db::one('SELECT token_hash, expires_at FROM mcp_tokens WHERE connection_id = ?', [$setup['connection_id']]);
$ttl = strtotime($stored['expires_at'] . ' UTC') - time();
check($ttl > 86380 && $ttl <= 86400 && $stored['token_hash'] === Support::hash($token) && !str_contains(Support::json($stored), $token), 'Server fixes expiry to 24 hours and stores only a token hash');
check(ReadModel::debate($debate['id'], $actor)['id'] === $debate['id'], 'Copied credential reads the intended debate');
rejects(fn () => ReadModel::debate($other['id'], $actor), 403, 'Credential cannot read another debate belonging to the same user');
check(ToolRegistry::invoke($actor, 'dilemma_test_connection', (object) [])['read_verified'] === true, 'Copied credential completes real read test');
$before = Db::one('SELECT COUNT(*) AS n FROM notification_outbox')['n'];
$receipt = ToolRegistry::invoke($actor, 'dilemma_test_write', (object) ['idempotency_key' => Support::token()]);
check($receipt['write_verified'] === true && Db::one('SELECT write_test_at FROM mcp_connections WHERE id = ?', [$setup['connection_id']])['write_test_at'] !== null && Db::one('SELECT COUNT(*) AS n FROM notification_outbox')['n'] === $before, 'Write test persists proof without notifying anyone');
$published = ToolRegistry::invoke($actor, 'dilemma_submit_argument', (object) ['debate_id' => $debate['id'], 'revision' => (int) Debates::row($debate['id'])['revision'], 'idempotency_key' => Support::token(), 'body' => 'A synthetic argument from the copied connection.']);
check(Db::one('SELECT id FROM debate_messages WHERE id = ?', [$published['message_id']]) !== null, 'Copied setup can actually publish a stored argument on its own turn');
rejects(fn () => DebateActions::apply($actor, $debate['id'], ['action' => 'flag']), 403, 'Copied setup cannot raise the human white flag');
rejects(fn () => Connections::setup($actor, ['debate_id' => $debate['id']]), 403, 'AI cannot mint its own new access');
Connections::revoke($a['actor'], $setup['connection_id']);
rejects(fn () => Connections::authenticate('Bearer ' . $token), 401, 'Removing access immediately invalidates the copied credential');
$expired = Connections::setup($a['actor'], ['debate_id' => $debate['id']]);
preg_match('/Authorization: Bearer ([A-Za-z0-9_-]{43})/', $expired['setup_text'], $match);
Db::run('UPDATE mcp_tokens SET expires_at = ? WHERE connection_id = ?', [gmdate('Y-m-d H:i:s', time() - 1), $expired['connection_id']]);
rejects(fn () => Connections::authenticate('Bearer ' . $match[1]), 401, 'Expired setup cannot authenticate');
echo 'SUCCESS: ' . $checks . " connection-setup checks. Isolated database only.\n";

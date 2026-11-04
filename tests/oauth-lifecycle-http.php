<?php
use Dilemma\{Db, Support};

// Included for each OAuth authentication method by the guarded loopback suite.
$mcp_call = static function (string $name, array $arguments = []) use (&$token): array {
	return http_request('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => (object) $arguments]], ['Authorization: Bearer ' . $token['access_token'], 'MCP-Protocol-Version: 2025-11-25']);
};
$mcp_headers = ['Authorization: Bearer ' . $token['access_token'], 'MCP-Protocol-Version: 2025-11-25'];
$initialized = http_request('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-11-25', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'Local OAuth lifecycle test', 'version' => '1']]], $mcp_headers);
check($initialized['status'] === 200 && str_contains($initialized['json']['result']['instructions'] ?? '', 'CITATIONS AND REUSABLE SOURCES'), 'OAuth initialization supplies current source guidance');
$listed = http_request('/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'], $mcp_headers);
$tool_names = array_column($listed['json']['result']['tools'] ?? [], 'name');
check(in_array('dilemma_submit_argument', $tool_names, true) && in_array('dilemma_test_write', $tool_names, true), 'OAuth tool discovery includes delegated writes and harmless verification');
$prompt = http_request('/mcp', ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'prompts/get', 'params' => ['name' => 'dilemma_debate', 'arguments' => ['debate_id' => $setup_debate['id'], 'locale' => 'sv']]], $mcp_headers);
check($prompt['status'] === 200 && str_contains($prompt['json']['result']['messages'][0]['content']['text'] ?? '', 'CITATIONS AND REUSABLE SOURCES'), 'OAuth prompt delivery includes the current source and publishing instructions');
$mine = $mcp_call('dilemma_list_debates');
check(array_column($mine['json']['result']['structuredContent']['items'] ?? [], 'id') === [$setup_debate['id']], 'OAuth cannot enumerate another debate owned by the same human');
$other = $mcp_call('dilemma_get_debate', ['debate_id' => $open['debate_id']]);
$denied = json_decode($other['json']['result']['content'][0]['text'] ?? '', true);
check(($other['json']['result']['isError'] ?? false) && ($denied['status'] ?? 0) === 403 && ($denied['error'] ?? '') === 'connection_debate_mismatch', 'OAuth cannot read a different debate even when the same human owns it');
$read_test = $mcp_call('dilemma_test_connection');
check(($read_test['json']['result']['structuredContent']['read_verified'] ?? false) === true, 'OAuth read verification succeeds through the public MCP route');
$connection_id = $read_test['json']['result']['structuredContent']['connection_id'];
$before_messages = \Dilemma\Debates::row($setup_debate['id'])['message_count'];
$before_mail = Db::one('SELECT COUNT(*) AS n FROM notification_outbox')['n'];
$write_input = ['idempotency_key' => Support::id()];
$write_test = $mcp_call('dilemma_test_write', $write_input);
$write_retry = $mcp_call('dilemma_test_write', $write_input);
$receipt = $write_test['json']['result']['structuredContent'];
$replayed = $write_retry['json']['result']['structuredContent'];
check(($receipt['write_verified'] ?? false) === true && ($replayed['idempotent_replay'] ?? false) === true && array_diff_key($receipt, ['idempotent_replay' => true]) === array_diff_key($replayed, ['idempotent_replay' => true]) && Db::one('SELECT write_test_at FROM mcp_connections WHERE id = ?', [$connection_id])['write_test_at'] !== null, 'OAuth write verification persists one repeatable receipt');
check(\Dilemma\Debates::row($setup_debate['id'])['message_count'] === $before_messages && Db::one('SELECT COUNT(*) AS n FROM notification_outbox')['n'] === $before_mail, 'OAuth connection verification creates no argument or notification');

// Move only this isolated fixture's access expiry, then exercise the real refresh route.
Db::run('UPDATE mcp_tokens SET expires_at = ? WHERE token_hash = ?', [gmdate('Y-m-d H:i:s', time() - 1), Support::hash($token['access_token'])]);
$expired = $mcp_call('dilemma_get_debate', ['debate_id' => $setup_debate['id']]);
check($expired['status'] === 401 && str_contains($expired['headers']['www-authenticate'][0] ?? '', '/.well-known/oauth-protected-resource'), 'Expired OAuth access returns discoverable authentication metadata');
$oauth_form = static function (string $path, array $fields) use ($client, $method): array {
	$fields['client_id'] = $client['client_id'];
	$headers = ['Content-Type: application/x-www-form-urlencoded'];
	if ($method === 'client_secret_post') $fields['client_secret'] = $client['client_secret'];
	if ($method === 'client_secret_basic') $headers[] = 'Authorization: Basic ' . base64_encode($client['client_id'] . ':' . $client['client_secret']);
	$curl = curl_init(\Dilemma\Config::url($path));
	curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 5, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => http_build_query($fields)]);
	$body = curl_exec($curl);
	if ($body === false) throw new RuntimeException('Loopback OAuth request failed');
	return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'json' => json_decode($body, true)];
};
$old_refresh = $token['refresh_token'];
$refreshed = $oauth_form('/oauth/token', ['grant_type' => 'refresh_token', 'refresh_token' => $old_refresh]);
check($refreshed['status'] === 200 && isset($refreshed['json']['access_token'], $refreshed['json']['refresh_token']) && $refreshed['json']['refresh_token'] !== $old_refresh, 'OAuth renews expired access through a form-encoded refresh');
$token = $refreshed['json'];
$continued = $mcp_call('dilemma_get_debate', ['debate_id' => $setup_debate['id']]);
check($continued['status'] === 200 && ($continued['json']['result']['structuredContent']['id'] ?? '') === $setup_debate['id'] && Db::one('SELECT expires_at FROM mcp_tokens WHERE token_hash = ?', [Support::hash($token['refresh_token'])])['expires_at'] === null, 'Refreshed access retains the chosen debate and a persistent renewal grant');
$revoked = $oauth_form('/oauth/revoke', ['token' => $token['refresh_token'], 'token_type_hint' => 'refresh_token']);
check($revoked['status'] === 200 && $mcp_call('dilemma_test_connection')['status'] === 401, 'OAuth disconnect immediately invalidates the current access token');
$after_revoke = $oauth_form('/oauth/token', ['grant_type' => 'refresh_token', 'refresh_token' => $token['refresh_token']]);
check($after_revoke['status'] === 400 && ($after_revoke['json']['error'] ?? '') === 'invalid_grant', 'Revoked OAuth access cannot be renewed');

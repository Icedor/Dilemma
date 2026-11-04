<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Dilemma\{Accounts, Arguments, Auth, Config, Connections, Db, DebateActions, Debates, Failure, I18n, Mail, Mcp, Moderation, OAuth, ReadModel, Support, ToolRegistry, Views};

try {
	$a = user_fixture('A'); $b = user_fixture('B'); $c = user_fixture('C');
	check(Auth::session($a['session']['token'])['id'] === $a['actor']['user_id'], 'Email registration, verification and authenticated session');
	rejects(fn () => Auth::verify($a['login_token']), 400, 'Login token can only be consumed once', 'expired_link');
	rejects(fn () => Auth::csrf(Auth::session($a['session']['token']), 'wrong'), 403, 'CSRF rejects an invalid token');
	check(Auth::session('invalid') === null, 'Invalid session cannot authenticate');
	$debate = debate_fixture($a['actor'], $b['actor'], false); $id = $debate['id'];
	check(Debates::row($id)['status'] === 'active', 'Invitation acceptance activates a persistent debate');
	rejects(fn () => ReadModel::debate($id), 404, 'One missing publication consent hides the debate');
	rejects(fn () => ReadModel::debate($id, $c['actor']), 404, 'Unrelated account cannot read a private debate');
	$positions = ReadModel::debate($id, $a['actor'])['positions'];
	check(count($positions) === 2 && in_array(0, array_column($positions, 'value'), true) && in_array(null, array_column($positions, 'value'), true), 'Zero and omitted positions remain distinct');
	DebateActions::apply($b['actor'], $id, ['action' => 'consent', 'revision' => $debate['revision'], 'idempotency_key' => Support::token(), 'public_consent' => true, 'position_consent' => false]);
	check(ReadModel::debate($id)['is_public'] === true, 'Both publication consents permit public reading');
	check(count(ReadModel::debate($id)['positions']) === 1, 'Position publication requires separate participant consent');
	$ta = actor_token($a['actor'], true, $id); $tb = actor_token($b['actor'], true, $id); $read = actor_token($a['actor'], false, $id);
	check(!in_array('dilemma_submit_argument', array_column(ToolRegistry::tools($read['actor']), 'name'), true), 'Read grant does not expose write tools');
	$input = ['debate_id' => $id, 'revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token(), 'body' => 'Open parks provide access for workers returning home late.', 'sources' => [['url' => 'https://example.org/evidence', 'label' => 'Example evidence']], 'rationale' => 'Access depends on work schedules.'];
	$first = ToolRegistry::invoke($ta['actor'], 'dilemma_submit_argument', json_decode(Support::json($input)));
	check(Db::one('SELECT body FROM debate_messages WHERE id = ?', [$first['message_id']])['body'] === $input['body'], 'MCP publication returns a real stored message receipt');
	$replay = ToolRegistry::invoke($ta['actor'], 'dilemma_submit_argument', json_decode(Support::json($input)));
	check($replay['message_id'] === $first['message_id'] && $replay['idempotent_replay'] === true, 'An identical retry returns the same stored message');
	check((int) Db::one('SELECT COUNT(*) AS n FROM notification_outbox WHERE dedupe_key = ?', [Support::hash('event:' . $first['event_sequence'] . ':' . $b['actor']['user_id'])])['n'] === 1, 'Repeated publication creates exactly one notification queue entry');
	rejects(fn () => Arguments::submit($ta['actor'], $id, array_replace($input, ['body' => 'Different text under the same key.'])), 409, 'Idempotency key cannot be reused for different content', 'idempotency_conflict');
	rejects(fn () => Arguments::submit($ta['actor'], $id, array_replace($input, ['idempotency_key' => Support::token(), 'revision' => $first['revision']])), 409, 'Turn order is enforced on the server', 'not_your_turn');
	rejects(fn () => ToolRegistry::invoke($read['actor'], 'dilemma_submit_argument', json_decode(Support::json($input))), 403, 'Read grant cannot publish via direct tool invocation');
	rejects(fn () => ToolRegistry::invoke($ta['actor'], 'dilemma_raise_flag', (object) []), 403, 'No MCP tool can raise a white flag');
	rejects(fn () => DebateActions::apply($ta['actor'], $id, ['action' => 'flag']), 403, 'Even a write grant cannot use human actions', 'human_only');
	$injected = (object) ['debate_id' => $id, 'revision' => $first['revision'], 'idempotency_key' => Support::token(), 'body' => 'Valid text.', 'user_id' => $b['actor']['user_id']];
	rejects(fn () => ToolRegistry::invoke($ta['actor'], 'dilemma_submit_argument', $injected), 422, 'Undeclared identity parameters are rejected');
	$second_input = ['revision' => $first['revision'], 'idempotency_key' => Support::token(), 'body' => 'Residents also need quiet at night; zoning could address both concerns.', 'reply_to' => [$first['message_id']], 'agent_assessment' => 'A time-limited quiet zone may be more proportionate than closure.'];
	$second = Arguments::submit($tb['actor'], $id, $second_input);
	check(ReadModel::argument($second['message_id'], $ta['actor'])['reply_to'] === [$first['message_id']], 'Arguments preserve reply relationships across clients');
	check((int) Db::one('SELECT COUNT(*) AS n FROM debate_positions WHERE debate_id = ?', [$id])['n'] === 2, 'Agent assessment never changes human position observations');
	$amendment = Arguments::amend($ta['actor'], $first['message_id'], ['revision' => $second['revision'], 'idempotency_key' => Support::token(), 'clarification' => 'Access could be limited to marked paths.']);
	check(ReadModel::argument($first['message_id'])['body'] === $input['body'] && count(ReadModel::argument($first['message_id'])['amendments']) === 1, 'Clarification preserves the historical original');
	rejects(fn () => Arguments::amend($tb['actor'], $first['message_id'], ['revision' => $amendment['revision'], 'idempotency_key' => Support::token(), 'clarification' => 'Unauthorized edit']), 403, 'A participant cannot amend the opponent’s argument');
	$move = DebateActions::apply($a['actor'], $id, ['action' => 'position', 'position' => 30, 'revision' => $amendment['revision'], 'idempotency_key' => Support::token()]);
	check((int) Db::one('SELECT COUNT(*) AS n FROM debate_positions WHERE debate_id = ?', [$id])['n'] === 3, 'Human position updates append observations');
	$flag_input = ['action' => 'flag', 'confirm' => true, 'revision' => $move['revision'], 'idempotency_key' => Support::token()];
	$flag = DebateActions::apply($a['actor'], $id, $flag_input);
	check($flag['status'] === 'flagged' && Debates::row($id)['flag_user_id'] === $a['actor']['user_id'], 'Human white flag persists and closes the debate');
	check(DebateActions::apply($a['actor'], $id, $flag_input)['idempotent_replay'] === true, 'Flag retry is idempotent');
	rejects(fn () => Arguments::submit($ta['actor'], $id, ['revision' => $flag['revision'], 'idempotency_key' => Support::token(), 'body' => 'This must not be published.']), 409, 'Closed debate rejects new arguments');
	check(count(ReadModel::list(['q' => 'parks'])['items']) > 0, 'Consented debate is discoverable through indexed public search');
	I18n::locale('sv'); $_GET = [];
	$html = Views::page('/debate/' . $id, null, null);
	check(str_contains($html, $first['message_id']) && str_contains($html, 'flag-dialog'), 'Public debate renders persistent history and flag replay without authentication');

	$other = debate_fixture($a['actor'], $b['actor']);
	rejects(fn () => ReadModel::debate($other['id'], $ta['actor']), 403, 'Debate-scoped grant cannot cross into another owned debate');
	$pause = DebateActions::apply($a['actor'], $other['id'], ['action' => 'pause', 'revision' => $other['revision'], 'idempotency_key' => Support::token()]);
	$resume = DebateActions::apply($b['actor'], $other['id'], ['action' => 'resume', 'revision' => $pause['revision'], 'idempotency_key' => Support::token()]);
	$propose = DebateActions::apply($a['actor'], $other['id'], ['action' => 'propose_end', 'reason' => 'disagreement', 'revision' => $resume['revision'], 'idempotency_key' => Support::token()]);
	rejects(fn () => DebateActions::apply($a['actor'], $other['id'], ['action' => 'accept_end', 'confirm' => true, 'revision' => $propose['revision'], 'idempotency_key' => Support::token()]), 409, 'Proposer cannot accept an ending on behalf of the opponent');
	$end = DebateActions::apply($b['actor'], $other['id'], ['action' => 'accept_end', 'confirm' => true, 'revision' => $propose['revision'], 'idempotency_key' => Support::token()]);
	check($end['status'] === 'ended' && Debates::row($other['id'])['flag_user_id'] === null, 'Pause, resume and mutually agreed disagreement do not fabricate a winner');

	$manual = debate_fixture($a['actor'], $b['actor']);
	$history = debate_fixture($a['actor'], $b['actor']);
	$history_revision = $history['revision'];
	for ($position = 1; $position <= 22; $position++) {
		$observation = DebateActions::apply($a['actor'], $history['id'], ['action' => 'position', 'position' => $position, 'revision' => $history_revision, 'idempotency_key' => Support::token()]);
		$history_revision = $observation['revision'];
	}
	$history_page = ReadModel::debate($history['id'], $a['actor'], 1, 2);
	check(count($history_page['positions']) === 4 && $history_page['position_pages'] === 2, 'Position history is bounded and accessible beyond its first page');
	check((int) $history_page['latest_positions'][0]['value'] === 22 && $history_page['latest_positions'][1]['value'] === null, 'Latest positions preserve same-second order and omitted values');
	$events = ReadModel::updates($history['id'], $a['actor']);
	$next_events = ReadModel::updates($history['id'], $a['actor'], $events['next_sequence']);
	check(count($events['events']) <= 50 && $next_events['next_sequence'] >= $events['next_sequence'] && isset($events['events'][0]['actor_alias']), 'History cursor is bounded, monotonic and identifies the actual actor');
	$manual_result = Arguments::submit($a['actor'], $manual['id'], ['revision' => $manual['revision'], 'idempotency_key' => Support::token(), 'body' => '<img src=x onerror=alert(1)> A pasted argument from a read-only client.']);
	check(ReadModel::argument($manual_result['message_id'])['channel'] === 'manual', 'Manual fallback uses the same persistent debate engine');
	$html = Views::page('/debate/' . $manual['id'], null, null);
	check(str_contains($html, '&lt;img') && !str_contains($html, '<img src=x'), 'User HTML is escaped in public server rendering');
	rejects(fn () => Arguments::submit($c['actor'], $manual['id'], ['revision' => $manual_result['revision'], 'idempotency_key' => Support::token(), 'body' => 'Intruder message']), 404, 'Public reading does not grant publication rights');

	$write_test = Connections::test($ta['actor'], ['idempotency_key' => Support::token()], true);
	check($write_test['write_verified'] && Db::one('SELECT write_test_at FROM mcp_connections WHERE id = ?', [$ta['id']])['write_test_at'] !== null, 'Connection status follows a real persisted write-test call');
	$headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $ta['token'], 'MCP-Protocol-Version' => Mcp::MODERN, 'Mcp-Method' => 'server/discover'];
	$meta = (object) ['io.modelcontextprotocol/protocolVersion' => Mcp::MODERN, 'io.modelcontextprotocol/clientCapabilities' => (object) []];
	$request = ['jsonrpc' => '2.0', 'id' => 'modern', 'method' => 'server/discover', 'params' => ['_meta' => $meta]];
	$discovery = Mcp::handle('POST', $headers, Support::json($request));
	check($discovery['status'] === 200 && $discovery['body']['result']['resultType'] === 'complete', 'Modern MCP discovery conforms to its versioned result envelope');
	$request['method'] = 'ping'; $headers['Mcp-Method'] = 'ping';
	check(Mcp::handle('POST', $headers, Support::json($request))['body']['result']['resultType'] === 'complete', 'Modern ping has a complete result type');
	$request['method'] = 'unknown'; $headers['Mcp-Method'] = 'unknown';
	check(Mcp::handle('POST', $headers, Support::json($request))['status'] === 404, 'Modern unknown method returns HTTP 404');
	$request['method'] = 'tools/call'; $headers['Mcp-Method'] = 'tools/call'; $headers['Mcp-Name'] = '=?base64?' . base64_encode('dilemma_whoami') . '?='; $request['params']['name'] = 'dilemma_whoami'; $request['params']['arguments'] = (object) [];
	check(Mcp::handle('POST', $headers, Support::json($request))['body']['result']['isError'] === false, 'Modern Mcp-Name Base64 sentinel is decoded before comparison');
	$headers['Mcp-Name'] = 'dilemma_other';
	check(Mcp::handle('POST', $headers, Support::json($request))['status'] === 400, 'Contradictory MCP headers are rejected');
	check(Mcp::handle('POST', ['Content-Type' => 'application/json'], '{bad')['status'] === 400, 'Malformed JSON is rejected');
	$legacy = Mcp::handle('POST', ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $ta['token']], Support::json(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => Mcp::LEGACY]]));
	check($legacy['body']['result']['protocolVersion'] === Mcp::LEGACY, 'Legacy initialize negotiates the supported legacy version');
	check(Mcp::handle('POST', ['Content-Type' => 'application/json', 'Origin' => 'https://attacker.test'], '{}')['status'] === 403, 'Untrusted browser Origin is rejected');
	$unauthorized = Mcp::handle('POST', ['Content-Type' => 'application/json'], Support::json(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']));
	check($unauthorized['status'] === 401 && isset($unauthorized['headers']['WWW-Authenticate']), 'Unauthenticated MCP advertises protected-resource metadata');

	$client = OAuth::register(['token_endpoint_auth_method' => 'none', 'client_name' => 'Dilemma test client', 'redirect_uris' => ['https://client.example.test/callback']], 'oauth-' . $run);
	$verifier = Support::token();
	$authorize = ['client_id' => $client['client_id'], 'redirect_uri' => $client['redirect_uris'][0], 'response_type' => 'code', 'code_challenge_method' => 'S256', 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='), 'resource' => Config::url('/mcp'), 'scope' => 'debates:read arguments:write', 'state' => 'fixture-state', 'approve' => true, 'debate_id' => $id];
	$authorization = OAuth::authorize($a['actor'], $authorize);
	parse_str(parse_url($authorization['redirect'], PHP_URL_QUERY), $callback);
	check($callback['state'] === 'fixture-state', 'OAuth preserves client state on an exact registered callback');
	$exchange = ['grant_type' => 'authorization_code', 'code' => $callback['code'], 'code_verifier' => $verifier, 'client_id' => $client['client_id'], 'redirect_uri' => $client['redirect_uris'][0], 'resource' => Config::url('/mcp')];
	rejects(fn () => OAuth::token(array_replace($exchange, ['code_verifier' => Support::token()]), 'oauth-' . $run), 400, 'Incorrect PKCE verifier cannot exchange a code');
	$tokens = OAuth::token($exchange, 'oauth-' . $run);
	check(Connections::authenticate('Bearer ' . $tokens['access_token'])['user_id'] === $a['actor']['user_id'], 'OAuth token is bound to the consenting account');
	rejects(fn () => OAuth::token($exchange, 'oauth-' . $run), 400, 'Authorization code is single-use');
	$refresh_input = ['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token'], 'client_id' => $client['client_id'], 'resource' => Config::url('/mcp')];
	$rotated = OAuth::token($refresh_input, 'oauth-' . $run);
	check($rotated['refresh_token'] !== $tokens['refresh_token'], 'Refresh token rotation issues a new secret');
	rejects(fn () => OAuth::token($refresh_input, 'oauth-' . $run), 400, 'Refresh-token reuse is rejected');
	rejects(fn () => Connections::authenticate('Bearer ' . $rotated['access_token']), 401, 'Refresh-token reuse revokes its entire connection');
	rejects(fn () => OAuth::request(array_replace($authorize, ['redirect_uri' => 'https://attacker.test/callback'])), 400, 'OAuth rejects an unregistered redirect');
	Connections::revoke($a['actor'], $ta['id']);
	rejects(fn () => Connections::authenticate('Bearer ' . $ta['token']), 401, 'Revoked grant cannot authenticate');
	rejects(fn () => Connections::test($ta['actor'], ['idempotency_key' => Support::token()], true), 401, 'Previously resolved actor is rechecked before writing');

	$report = Moderation::report($c['actor'], $id, ['reason' => 'Please review this test content.']);
	check(Db::one('SELECT status FROM moderation_reports WHERE id = ?', [$report['report_id']])['status'] === 'open', 'Moderation reports are persisted');
	rejects(fn () => Moderation::apply($c['actor'], ['action' => 'hide_debate', 'target_id' => $id, 'reason' => 'Unauthorized action']), 403, 'Ordinary user cannot perform administration');
	Db::run('UPDATE users SET is_admin = 1 WHERE id = ?', [$c['actor']['user_id']]);
	Moderation::apply($c['actor'], ['action' => 'hide_debate', 'target_id' => $id, 'reason' => 'Authorized moderation fixture']);
	rejects(fn () => ReadModel::debate($id), 404, 'Administrator can remove a reported debate from public access');
	Moderation::apply($c['actor'], ['action' => 'restore_debate', 'target_id' => $id, 'reason' => 'Authorized restoration fixture']);
	check(ReadModel::debate($id)['is_public'], 'Restoration still depends on both publication consents');
	check((int) Db::one('SELECT COUNT(*) AS n FROM audit_events WHERE actor_id = ? AND target_id = ?', [$c['actor']['user_id'], $id])['n'] === 2, 'Moderation records both audited actions');
	Moderation::apply($c['actor'], ['action' => 'block_user', 'target_id' => $b['actor']['user_id'], 'reason' => 'Block fixture account']);
	check(Auth::session($b['session']['token']) === null, 'Blocked accounts lose browser sessions');
	rejects(fn () => Connections::authenticate('Bearer ' . $tb['token']), 401, 'Blocked account cannot use an otherwise valid agent token');
	Moderation::apply($c['actor'], ['action' => 'restore_user', 'target_id' => $b['actor']['user_id'], 'reason' => 'Restore fixture account']);
	Accounts::update($a['actor'], ['alias' => 'Updated alias', 'bio' => '<script>not executed</script>', 'locale' => 'en', 'notify' => false, 'chat_link' => 'https://chatgpt.com/c/12345678-abcd']);
	$profile = Auth::active($a['actor']['user_id']);
	check($profile['locale'] === 'en' && !str_contains($profile['chat_link'], 'chatgpt.com'), 'Preferences persist and private chat links are encrypted at rest');
	check(!str_contains(Support::json(ReadModel::debate($id)), 'chatgpt.com'), 'Public debate does not disclose private chat links');
	$export = Accounts::export($a['actor']);
	check($export['profile']['email'] === $a['email'] && count($export['arguments']) >= 2, 'Account export returns real owned data');
	Accounts::delete($a['actor'], ['confirm' => true, 'email' => $a['email']]);
	check(Auth::session($a['session']['token']) === null && Db::one('SELECT email FROM users WHERE id = ?', [$a['actor']['user_id']])['email'] === null, 'Deletion clears identity and revokes sessions');
	rejects(fn () => ReadModel::debate($id), 404, 'Account deletion withdraws public debate access');
	check(Db::one('SELECT body FROM debate_messages WHERE id = ?', [$first['message_id']])['body'] === '', 'Account deletion removes authored argument text');
	check(Debates::row($manual['id'])['end_reason'] === 'account_deleted' && Debates::row($manual['id'])['status'] === 'ended', 'Deleting a participant records the correct ending reason');
	$rendered = Mail::render(Support::id(), 'argument', ['email' => 'recipient@example.test', 'locale' => 'sv', 'data' => ['url' => Config::url('/debate/' . $id), 'title' => 'Åsikter & argument']]);
	check(str_contains($rendered, 'multipart/alternative') && str_contains($rendered, 'text/plain; charset=UTF-8') && str_contains($rendered, 'text/html; charset=UTF-8'), 'Email has UTF-8 HTML and plain-text alternatives');
	echo 'SUCCESS: ' . $checks . " checks. Test database only; no production email or real AI-client calls.\n";
} catch (Throwable $error) {
	fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . "\n" . $error->getFile() . ':' . $error->getLine() . "\n");
	exit(1);
}

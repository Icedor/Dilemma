<?php
// Included by http.php while its isolated loopback server is running.
use Dilemma\{Db, Support, Connections, Debates, ReadModel};

function flow_human(string $label): array {
	global $run;
	$email = 'flow-' . $run . '-' . strtolower($label) . '@example.test';
	$response = http_request('/api/login', ['email' => $email, 'alias' => $label, 'accept_terms' => true]);
	check($response['status'] === 200, 'Full flow: request magic link for ' . $label);
	$row = Db::one("SELECT n.encrypted_payload FROM notification_outbox n JOIN users u ON u.id = n.user_id WHERE u.email = ? AND n.kind = 'login'", [$email]);
	$mail = json_decode(Support::unseal($row['encrypted_payload']), true);
	$response = http_request('/api/verify', ['token' => parse_url($mail['data']['url'], PHP_URL_FRAGMENT)]);
	check($response['status'] === 200, 'Full flow: verify magic link for ' . $label);
	$cookie = explode(';', $response['headers']['set-cookie'][0])[0];
	$page = http_request('/new', null, [], $cookie);
	preg_match('/name="csrf-token" content="([^"]+)"/', $page['body'], $csrf);
	check(isset($csrf[1]), 'Full flow: separate authenticated session for ' . $label);
	return ['cookie' => $cookie, 'headers' => ['X-CSRF-Token: ' . $csrf[1]]];
}
function flow_post(array $human, string $path, array $data): array {
	return http_request($path, $data + ['idempotency_key' => Support::token()], $human['headers'], $human['cookie']);
}
function flow_tool(array $headers, string $name, array $args = []): array {
	return http_request('/mcp', ['jsonrpc' => '2.0', 'id' => Support::id(), 'method' => 'tools/call', 'params' => ['name' => 'dilemma_' . $name, 'arguments' => (object) $args]], $headers);
}
function flow_ok(array $response, string $name): array {
	check($response['status'] === 200 && isset($response['json']['result']['structuredContent']) && empty($response['json']['result']['isError']), 'Full flow: ' . $name);
	return $response['json']['result']['structuredContent'];
}
function flow_connect(array $human, string $id, string $label): array {
	$response = flow_post($human, '/api/connections/setup', ['debate_id' => $id]);
	check($response['status'] === 201, 'Full flow: copy persistent access for ' . $label);
	$setup = $response['json'];
	preg_match('/Authorization: Bearer ([A-Za-z0-9_-]{43})/', $setup['setup_text'], $key);
	$headers = ['Authorization: Bearer ' . $key[1], 'MCP-Protocol-Version: 2025-11-25'];
	foreach (explode("\n", $setup['setup_text']) as $line) {
		if (!str_starts_with($line, '{')) continue;
		$request = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
		if (($request['params']['name'] ?? '') === 'dilemma_test_write') $request['params']['arguments']['idempotency_key'] = Support::id();
		if (isset($request['params']['arguments']) && $request['params']['arguments'] === []) $request['params']['arguments'] = (object) [];
		if (isset($request['params']['capabilities'])) $request['params']['capabilities'] = (object) [];
		$result = http_request('/mcp', $request, $headers);
		check($result['status'] === 200 && isset($result['json']['result']) && empty($result['json']['result']['isError']), 'Full flow: ' . $label . ' ' . ($request['params']['name'] ?? $request['method']));
	}
	return $headers;
}
function flow_publish(array $headers, string $id, string $body, string $outcome = 'argument'): array {
	$state = flow_ok(flow_tool($headers, 'get_debate', ['debate_id' => $id]), 'read before publication');
	$messages = $state['messages'];
	$parents = $messages ? [end($messages)['id']] : [];
	if ($parents) flow_ok(flow_tool($headers, 'get_argument', ['message_id' => $parents[0]]), 'read full opposing argument');
	$input = ['debate_id' => $id, 'revision' => (int) $state['revision'], 'idempotency_key' => Support::token(), 'body' => $body, 'reply_to' => $parents, 'outcome' => $outcome];
	$receipt = flow_ok(flow_tool($headers, 'submit_argument', $input), 'publish ' . $outcome . ' on site');
	$retry = flow_ok(flow_tool($headers, 'submit_argument', $input), 'retry preserves stored receipt');
	check($retry['message_id'] === $receipt['message_id'], 'Full flow: no duplicate post');
	return $receipt;
}

$fa = flow_human('Sam'); $fb = flow_human('Robin');
$made = flow_post($fa, '/api/debates', ['title' => 'Parks should stay open at night', 'stance' => 'People working late need access to public space.', 'processing_consent' => true, 'public_consent' => true, 'opponent_mode' => 'invite', 'locale' => 'en']);
check($made['status'] === 201, 'Full flow: create opinion through HTTP');
$fid = $made['json']['debate_id']; $fpath = '/api/debates/' . $fid;
check(http_request('/debate/' . $fid)['status'] === 303 && http_request($fpath . '/status')['status'] === 404, 'Full flow: private page offers login while the data API stays private');
$ma = flow_connect($fa, $fid, 'Sam');
$first = flow_publish($ma, $fid, 'Parks should remain accessible to people returning from late shifts. Quiet paths could preserve access without encouraging noisy gatherings.');
$state = flow_ok(flow_tool($ma, 'get_debate', ['debate_id' => $fid]), 'opening remains readable while waiting');
check($state['workflow']['next_action'] === 'wait_for_opponent' && (int) $state['message_count'] === 1, 'Full flow: one opening then wait');
$blocked = flow_tool($ma, 'submit_argument', ['debate_id' => $fid, 'revision' => (int) $state['revision'], 'idempotency_key' => Support::token(), 'body' => 'A second opening must not be accepted.']);
check(!empty($blocked['json']['result']['isError']), 'Full flow: no repeated solo arguments');
$invite = flow_post($fa, $fpath . '/invite', []);
check($invite['status'] === 200, 'Full flow: generate friend link');
$token = parse_url($invite['json']['invitation_url'], PHP_URL_FRAGMENT);
check(flow_post($fb, '/api/invitation', ['token' => $token])['status'] === 200, 'Full flow: friend reads invitation');
$accepted = flow_post($fb, '/api/accept', ['token' => $token, 'stance' => 'Residents need quiet and undisturbed sleep.', 'accept_terms' => true, 'processing_consent' => true, 'public_consent' => true]);
check($accepted['status'] === 200, 'Full flow: friend joins own side');
$mb = flow_connect($fb, $fid, 'Robin');
check($ma !== $mb, 'Full flow: two separate scoped credentials');
$status = http_request($fpath . '/status', null, [], $fa['cookie']);
check($status['status'] === 200 && count(array_filter($status['json']['active_participants'], static fn ($p) => $p['ai_connected'])) === 2, 'Full flow: both connections shown as verified');
flow_publish($mb, $fid, 'Quiet access may work, but the paths closest to homes should close if disturbance persists. A limited trial could test that compromise.');
flow_publish($ma, $fid, 'Unrestricted access is too broad. I cannot defend keeping every path open regardless of repeated disturbance; I need my participant’s input.', 'needs_human');
$state = flow_ok(flow_tool($ma, 'get_debate', ['debate_id' => $fid]), 'read human decision state');
check($state['status'] === 'awaiting_human' && $state['workflow']['next_action'] === 'ask_human' && !$state['flag_user_id'] && count($state['positions']) === 2, 'Full flow: AI assessment does not change human opinion or flag');
Db::run('UPDATE debate_participants SET ai_wait_until = ? WHERE debate_id = ?', [gmdate('Y-m-d H:i:s', time() + 7200), $fid]);
$opponent_pause = flow_ok(flow_tool($mb, 'get_debate', ['debate_id' => $fid]), 'read opponent human-decision wait');
check($opponent_pause['workflow']['keep_waiting'] && !$opponent_pause['workflow']['can_reconsider'] && !$state['workflow']['keep_waiting'] && $state['workflow']['can_reconsider'], 'Full flow: only the side needing new human reasons stops; opponent keeps waiting');
$wrong = flow_tool($mb, 'reconsider', ['debate_id' => $fid, 'revision' => (int) $state['revision'], 'idempotency_key' => Support::token()]);
check(!empty($wrong['json']['result']['isError']), 'Full flow: opponent cannot resume the other side');
$decision_page = http_request('/debate/' . $fid, null, [], $fa['cookie']);
check($decision_page['status'] === 200 && preg_match('/<details[^>]*id="your-decision"[^>]*\bopen\b[^>]*>/', $decision_page['body']) === 1 && str_contains($decision_page['body'], 'value="leave_seat"'), 'Full flow: human sees new arguments, open seat and ending choices');
flow_ok(flow_tool($ma, 'reconsider', ['debate_id' => $fid, 'revision' => (int) $state['revision'], 'idempotency_key' => Support::token()]), 'resume own side after new human arguments');
flow_publish($ma, $fid, 'My participant proposes a trial limited to paths away from homes. This keeps late access while allowing closure where disturbance is documented.');
flow_publish($mb, $fid, 'That narrower trial addresses much of my objection. The remaining question is whether a safe alternative route already exists.');
flow_publish($ma, $fid, 'If an equally safe alternative already exists, I cannot justify the added disturbance. My participant needs to decide whether to continue.', 'needs_human');
$revision = (int) Debates::row($fid)['revision'];
check(flow_post($fa, $fpath . '/action', ['action' => 'leave_seat', 'revision' => $revision])['status'] === 422, 'Full flow: leaving requires human confirmation');
$left = flow_post($fa, $fpath . '/action', ['action' => 'leave_seat', 'confirm' => true, 'revision' => $revision]);
check($left['status'] === 200 && $left['json']['status'] === 'open', 'Full flow: creator leaves a public vacant seat');
$observer = flow_ok(flow_tool($ma, 'get_debate', ['debate_id' => $fid]), 'old persistent access still reads history');
check($observer['workflow']['next_action'] === 'observe' && (int) $observer['message_count'] === 6, 'Full flow: former participant observes, history intact');
$public = http_request('/debate/' . $fid);
check($public['status'] === 200 && str_contains($public['body'], 'id="arguments"') && str_contains($public['body'], 'late shifts') && !str_contains($public['body'], 'Authorization: Bearer'), 'Full flow: open replacement shows full public history safely');
$fc = flow_human('Alex');
$claim = flow_post($fc, $fpath . '/claim', ['stance' => 'I can make the case for a limited night access trial.', 'accept_terms' => true, 'processing_consent' => true, 'public_consent' => true]);
check($claim['status'] === 200, 'Full flow: next participant takes vacant creator side');
$mc = flow_connect($fc, $fid, 'Alex');
$new = flow_publish($mc, $fid, 'I would compare the alternative route with a quiet park path in a time-limited trial, then review the recorded disturbance and accessibility.');
check((int) Db::one('SELECT COUNT(*) AS n FROM debate_participants WHERE debate_id = ?', [$fid])['n'] === 3 && count(ReadModel::argument($new['message_id'])['reply_to']) === 1, 'Full flow: replacement preserves authorship and reply relationships');
$bad = flow_tool($ma, 'submit_argument', ['debate_id' => $fid, 'revision' => $new['revision'], 'idempotency_key' => Support::token(), 'body' => 'The former participant must not publish new arguments.']);
check(!empty($bad['json']['result']['isError']), 'Full flow: old key cannot occupy the new participant’s seat');
flow_publish($mb, $fid, 'A reversible trial with a clear review addresses my concern. I cannot reject it outright and will ask my participant to decide.', 'needs_human');
$ended = flow_post($fb, $fpath . '/action', ['action' => 'flag', 'confirm' => true, 'revision' => (int) Debates::row($fid)['revision']]);
check($ended['status'] === 200 && $ended['json']['status'] === 'flagged', 'Full flow: human alone raises flag and closes discussion');
$events = http_request($fpath . '/events');
check(in_array('seat_opened', array_column($events['json']['events'], 'kind'), true) && in_array('agent_reconsidering', array_column($events['json']['events'], 'kind'), true), 'Full flow: decisions and reconsideration are in event history');
$withdraw_consent = flow_post($fa, $fpath . '/action', ['action' => 'consent', 'public_consent' => false, 'revision' => (int) Debates::row($fid)['revision']]);
check($withdraw_consent['status'] === 200 && http_request('/debate/' . $fid)['status'] === 303, 'Full flow: former participant retains control over publication consent');

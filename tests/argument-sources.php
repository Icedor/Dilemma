<?php
require __DIR__ . '/bootstrap.php';
use Dilemma\{AiSetup, Arguments, Db, Debates, Mcp, ReadModel, Support};

$a = user_fixture('SourceA');
$b = user_fixture('SourceB');
$outsider = user_fixture('SourceC');
$debate = debate_fixture($a['actor'], $b['actor'], false);
$id = $debate['id'];
$first_client = actor_token($a['actor'], true, $id);
$next_client = actor_token($b['actor'], true, $id);
$call = static function (array $client, string $name, array $arguments): array {
	$response = Mcp::handle('POST', ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $client['token'], 'MCP-Protocol-Version' => Mcp::LEGACY], Support::json(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => (object) $arguments]]));
	if ($response['status'] !== 200 || !empty($response['body']['result']['isError']) || isset($response['body']['error'])) throw new RuntimeException('MCP fixture call failed');
	return json_decode(Support::json($response['body']['result']['structuredContent']), true, 32, JSON_THROW_ON_ERROR);
};
$sources = [
	['url' => 'https://example.org/research#a', 'label' => 'Fixture A (2024) — Original study'],
	['url' => 'https://example.org/data?year=2025', 'label' => 'Fixture B (2025) — Dataset'],
];
$input = ['debate_id' => $id, 'revision' => $debate['revision'], 'idempotency_key' => Support::id(), 'body' => 'This is fixture evidence, not a real-world claim (Fixture A, 2024; Fixture B, 2025).', 'sources' => $sources];
$receipt = $call($first_client, 'dilemma_submit_argument', $input);
$message = $call($next_client, 'dilemma_get_argument', ['message_id' => $receipt['message_id']]);
check($message['body'] === $input['body'], 'The next MCP participant receives the complete inline references');
check(array_column($message['sources'], 'label', 'url') == array_column($sources, 'label', 'url'), 'The next MCP participant receives all reusable URLs and source labels');
$replay = $call($first_client, 'dilemma_submit_argument', $input);
check($replay['message_id'] === $receipt['message_id'] && count(ReadModel::argument($receipt['message_id'], $a['actor'])['sources']) === 2, 'Publication retries never duplicate sources');
rejects(fn () => ReadModel::argument($receipt['message_id'], $outsider['actor']), 404, 'A source list does not expose a private debate to another account');
$malicious = ['revision' => $receipt['revision'], 'idempotency_key' => Support::id(), 'body' => 'Another fixture argument with invalid references.', 'sources' => [['url' => 'https://example.org/valid', 'label' => 'First valid source'], ['url' => 'javascript:alert(1)', 'label' => 'Unsafe source']]];
rejects(fn () => Arguments::submit($b['actor'], $id, $malicious), 422, 'An unsafe source URL is rejected');
check((int) Debates::row($id)['message_count'] === 1 && (int) Db::one('SELECT COUNT(*) AS n FROM argument_sources WHERE message_id=?', [$receipt['message_id']])['n'] === 2, 'Invalid sources roll back the full post and its partial inserts');
$amendment = ['message_id' => $receipt['message_id'], 'revision' => $receipt['revision'], 'idempotency_key' => Support::id(), 'clarification' => 'The added reference narrows the original claim (Fixture C, 2026).', 'sources' => [['url' => 'https://example.org/correction', 'label' => 'Fixture C (2026) — Correction']]];
$call($first_client, 'dilemma_update_argument_metadata', $amendment);
$updated = $call($next_client, 'dilemma_get_argument', ['message_id' => $receipt['message_id']]);
check(count($updated['sources']) === 3 && $updated['amendments'][0]['clarification'] === $amendment['clarification'] && $updated['body'] === $input['body'], 'The next agent can read appended evidence and corrections with original history intact');
$workflow = $call($next_client, 'dilemma_get_debate', ['debate_id' => $id])['workflow'];
check(str_contains($workflow['instructions'], 'CITATIONS AND REUSABLE SOURCES') && str_contains($workflow['instructions'], 'dilemma_get_argument'), 'An existing chat receives the current citation instructions when reading the debate');
foreach (['sv', 'en'] as $locale) {
	check(str_contains(AiSetup::connector($id, $locale), 'dilemma_get_instructions') && str_contains(AiSetup::text(Support::token(), $id, null, $locale), 'result.instructions'), 'Native and direct bootstrap retrieve the full server guidance in ' . $locale);
}
echo 'SUCCESS: ' . $checks . " source handoff and security checks.\n";

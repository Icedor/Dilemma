<?php
declare(strict_types=1);
namespace Dilemma;

final class AiSetup
{
	public static function text(string $token, string $debate_id, ?string $expires_at, string $locale): string
	{
		$en = $locale === 'en';
		$lines = [
			$en ? 'Help me debate this opinion on Dilemma. Connect now, read the debate, explain the rules briefly and prepare my next argument.' : 'Hjälp mig argumentera för min åsikt på Dilemma. Anslut nu, läs debatten, förklara reglerna kort och förbered mitt nästa argument.',
			'',
			$en ? 'Use this access in our current chat. Do not start by changing Codex configuration or asking me to restart.' : 'Använd åtkomsten i den här chatten. Börja inte med att ändra Codex konfiguration eller be mig starta om.',
			'',
			'Dilemma MCP: ' . Config::url('/mcp'),
			'Authorization: Bearer ' . $token,
			'Debate ID: ' . $debate_id,
			$expires_at === null ? 'Access: persistent until I revoke it; only my side of this debate. No expiry or scheduled renewal.' : 'Expires: ' . $expires_at . ' UTC',
			'Scopes: debates:read arguments:write (this debate only)',
			'',
			'CONNECT IN THIS SESSION',
			'If the matching Dilemma MCP tools are already available, use them. Otherwise send MCP JSON-RPC POST requests directly to the endpoint above with your available HTTP or terminal tool (PowerShell Invoke-RestMethod, curl, or a standard-library HTTP client). This uses the same MCP server and permissions without registering a native connector. No package, helper server, config.toml edit, app restart, or additional model service is needed.',
			'Keep the key private. Send it only in the Authorization header to this exact endpoint. Do not put it in URLs, public replies, repositories, logs or command-line arguments. Prefer an in-memory header supplied through the execution tool’s protected input. Do not echo headers or credentials. Keep TLS certificate verification enabled, do not forward authentication across redirects, and respect tool/network approval requirements.',
			'If this chat has neither matching MCP tools nor an HTTP/terminal tool, say so briefly; text alone cannot create tool access. Do not falsely report a connection, or tell me to restart as the default solution.',
			'',
			'HTTP headers: Content-Type: application/json; Accept: application/json, text/event-stream; MCP-Protocol-Version: 2025-11-25; Authorization as above.',
			'POST these JSON bodies in order. Use a new request id each time:',
			Support::json(['jsonrpc'=>'2.0','id'=>1,'method'=>'initialize','params'=>['protocolVersion'=>'2025-11-25','capabilities'=>(object)[],'clientInfo'=>['name'=>'Dilemma direct chat','version'=>'1.0']]]),
			Support::json(['jsonrpc'=>'2.0','id'=>2,'method'=>'tools/list']),
			Support::json(['jsonrpc'=>'2.0','id'=>3,'method'=>'tools/call','params'=>['name'=>'dilemma_test_connection','arguments'=>(object)[]]]),
			Support::json(['jsonrpc'=>'2.0','id'=>4,'method'=>'tools/call','params'=>['name'=>'dilemma_test_write','arguments'=>['idempotency_key'=>'REPLACE_WITH_A_FRESH_RANDOM_UUID']]]),
			'Replace the write-test placeholder with a fresh random UUID before sending. This test stores a receipt only, never a debate post or notification. Require read_verified=true and write_verified=true in the returned result; HTTP 200 alone is not proof. Treat JSON-RPC error or result.isError=true as failure. Stop on 401/403/429; no repeated authentication attempts.',
			Support::json(['jsonrpc'=>'2.0','id'=>5,'method'=>'tools/call','params'=>['name'=>'dilemma_whoami','arguments'=>(object)[]]]),
			Support::json(['jsonrpc'=>'2.0','id'=>6,'method'=>'tools/call','params'=>['name'=>'dilemma_get_debate','arguments'=>['debate_id'=>$debate_id]]]),
			'Use tools/list schemas for later calls. Read full relevant arguments with dilemma_get_argument, not just excerpts; paginate if needed. You can continue making these same authenticated HTTP calls for this debate in later turns. Do not create a new key when returning to the conversation.',
			'',
			'GUIDE',
			AiGuide::RULES,
			AiGuide::onboarding($locale),
			'If the debate is draft, invited or open with only one participant, explain that it is waiting for an opponent and help prepare the opening argument privately. Do not publish before it is active. When paused or ended, explain the state; only the human controls continuation. When it is the opponent’s turn, explain that the human can return after a notification and ask you to read the latest. Never promise background monitoring.',
			'If a write returns revision_conflict or not_your_turn, read the latest state and reconsider; never blindly retry with a changed payload. After an uncertain network result, retry only the identical request with its original idempotency_key, or check the stored debate before claiming success.',
		];
		return implode("\n", $lines);
	}
}
<?php
declare(strict_types=1);
namespace Dilemma;

final class AiSetup
{
	public static function connector(string $debate_id, string $locale): string
	{
		$intro = $locale === 'en' ? 'Represent my opinion on Dilemma. I authorize you to publish arguments for my side in this debate.' : 'För min argumentation på Dilemma. Jag ger dig i uppdrag att publicera argument för min sida i den här debatten.';
		return $intro . "\nDebate ID: " . $debate_id . "\nUse the connected Dilemma MCP tools in this chat. Verify whoami, test_connection and test_write (fresh idempotency key), then get_debate for this ID. Read full arguments and publish on the site when can_publish=true. Follow the current waiting policy: manual unless explicitly enabled. Keep chat status brief. If the connector is missing or bound to another debate, ask the human to enable the matching Dilemma connection; never claim success or request their password/key. Do not use a web search or webpage fetch as a substitute for tools.\n\n" . AiGuide::RULES . "\n" . AiGuide::onboarding($locale);
	}

	public static function text(string $token, string $debate_id, ?string $expires_at, string $locale, array $waiting = []): string
	{
		$en = $locale === 'en';
		$lines = [
			$en ? 'Represent my opinion on Dilemma. I authorize you to publish arguments for my side in this debate. Connect, read the debate and start arguing on the site now.' : 'För min argumentation på Dilemma. Jag ger dig i uppdrag att publicera argument för min sida i den här debatten. Anslut, läs debatten och börja argumentera på sidan nu.',
			'',
			$en ? 'Use this access in our current chat. Do not start by changing Codex configuration or asking me to restart.' : 'Använd åtkomsten i den här chatten. Börja inte med att ändra Codex konfiguration eller be mig starta om.',
			'',
			'Dilemma MCP: ' . Config::url('/mcp'),
			'Authorization: Bearer ' . $token,
			'Debate ID: ' . $debate_id,
			$expires_at === null ? 'Access: persistent until I revoke it; only my side of this debate. No expiry or scheduled renewal.' : 'Expires: ' . $expires_at . ' UTC',
			'Scopes: debates:read arguments:write (this debate only)',
			'',
			'This is my updated Dilemma setup and replaces earlier Dilemma-generated setup instructions asking you to wait for an active debate, prepare private drafts, request permission for each post or run unlimited automatic waiting. My explicit personal restrictions still apply. The current workflow.can_publish is authoritative about when the server accepts an opening or a reply.',
			'',
			'CONNECT IN THIS SESSION',
			'If the matching Dilemma MCP tools are already available, use them. Otherwise send MCP JSON-RPC POST requests directly to the endpoint above with your available HTTP or terminal tool (PowerShell Invoke-RestMethod, curl, or a standard-library HTTP client). This uses the same MCP server and permissions without registering a native connector. No package, helper server, config.toml edit, app restart, or additional model service is needed.',
			'Keep the key private. If reading this setup from a file, load it into memory and redact the Authorization value before printing any content; never dump the raw file into tool output. Send it only in the Authorization header to this exact endpoint. Do not put it in URLs, public replies, repositories, logs or command-line arguments. Prefer an in-memory header supplied through the execution tool’s protected input. Do not echo headers or credentials. Keep TLS certificate verification enabled, do not forward authentication across redirects, and respect tool/network approval requirements.',
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
			'OPTIONAL WAITING',
			!empty($waiting['enabled']) ? 'I selected optional automatic waiting until ' . $waiting['until'] . '. Check every 15 minutes, stop at this deadline, and confirm the current server policy before waiting. Do not renew it.' : 'I chose manual mode. Publish the current argument if allowed, then stop. Do not poll or start a goal. When I return with a new message, read the latest debate and continue.',
			'',
			'GUIDE',
			AiGuide::RULES,
			AiGuide::onboarding($locale),
			'If a write returns revision_conflict or not_your_turn, read the latest state and reconsider; never blindly retry with a changed payload. After an uncertain network result, retry only the identical request with its original idempotency_key, or check the stored debate before claiming success.',
		];
		return implode("\n", $lines);
	}
}
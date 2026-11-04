<?php
declare(strict_types=1);
namespace Dilemma;

final class AiSetup
{
	public static function connector(string $debate_id, string $locale, ?string $user_id = null): string
	{
		$intro = $locale === 'en'
			? 'Represent my opinion on Dilemma and publish my arguments on the site. Keep chat updates brief.'
			: 'För min argumentation på Dilemma och publicera mina argument på sidan. Håll chatten kort.';
		return implode("\n", [
			$intro,
			'Debate ID: ' . $debate_id,
			$user_id ? 'Expected user ID: ' . $user_id : '',
			'First call dilemma_whoami. Match debate_id and user_id before any debate read or write test. Skip connections for other debates. If none matches, ask briefly to connect this debate on Dilemma.',
			'Fetch prompts/get dilemma_debate with this debate_id and locale=' . $locale . '. Follow its current rules, verify test_connection and test_write with a fresh idempotency key, then read and publish when workflow.can_publish=true. The server supplies source, waiting and human-decision rules. Report the result briefly with the debate link.',
		]);
	}

	public static function text(string $token, string $debate_id, ?string $expires_at, string $locale, array $waiting = [], array $binding = []): string
	{
		$intro = $locale === 'en'
			? 'Represent my opinion on Dilemma. I authorize publication of my arguments on the site. Connect and act, with brief chat updates.'
			: 'För min argumentation på Dilemma. Du får publicera mina argument på sidan. Anslut och agera, med korta besked i chatten.';
		return implode("\n", array_filter([
			'PRIVATE DILEMMA ACCESS — redact Authorization before any file output.',
			$intro,
			'Dilemma MCP: ' . Config::url('/mcp'),
			'Authorization: Bearer ' . $token,
			'Debate ID: ' . $debate_id,
			isset($binding['user_id']) ? 'Expected user ID: ' . $binding['user_id'] : '',
			isset($binding['connection_id']) ? 'Expected connection ID: ' . $binding['connection_id'] : '',
			$expires_at === null ? 'Access persists for my side of this debate until revoked.' : 'Expires: ' . $expires_at . ' UTC',
			'Use direct HTTPS MCP POST with THIS credential through an HTTP/terminal tool in this chat. Skip existing Dilemma connections. Keep credentials in memory; suppress raw file, header and key output. Send Authorization only to the exact endpoint above, with TLS verification and redirects disabled. Respect tool approvals.',
			'Headers: Content-Type: application/json; Accept: application/json, text/event-stream; MCP-Protocol-Version: 2025-11-25.',
			'Initialize with the JSON below, then follow result.instructions. Verify the returned debate_id, user_id and connection_id against the IDs above before proceeding. On 401/403/429 stop; no retries. If HTTP tools are unavailable, say so briefly.',
			Support::json(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-11-25', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'Dilemma direct chat', 'version' => '1.1']]]),
			!empty($waiting['enabled']) ? 'I selected optional automatic waiting until ' . $waiting['until'] . ' UTC. Check every 900 seconds while the current server policy permits.' : 'I chose manual mode. Publish when allowed, then stop until my next message.',
			'This replaces earlier Dilemma-generated setup. My personal restrictions still apply. Use the current server rules for sources, waiting and human decisions. Reply in ' . ($locale === 'en' ? 'English.' : 'Swedish.'),
		]));
	}
}

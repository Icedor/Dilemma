<?php
declare(strict_types=1);
namespace Dilemma;

final class AiSetup
{
	// Short text the human pastes into a Claude chat. The server supplies the full rules.
	public static function connector(string $debate_id, string $locale, ?string $user_id = null): string
	{
		$link = Config::url('/debate/' . $debate_id);
		return implode("\n", array_filter($locale === 'en' ? [
			'Argue my side in this Dilemma debate: ' . $link,
			'Use the Dilemma connector. First call dilemma_get_instructions with debate_id ' . $debate_id . ' and locale en, then follow it. Act only in this debate.',
			$user_id ? 'My Dilemma user ID: ' . $user_id : '',
			'Publish the arguments on Dilemma, not here. Keep your replies to me short and skip technical IDs.',
			'If the Dilemma tools are missing, just tell me to turn on Dilemma under + in this chat.',
		] : [
			'För min talan i den här Dilemma-debatten: ' . $link,
			'Använd Dilemma-anslutningen. Anropa först dilemma_get_instructions med debate_id ' . $debate_id . ' och locale sv, och följ den. Agera bara i den här debatten.',
			$user_id ? 'Mitt Dilemma-användar-ID: ' . $user_id : '',
			'Publicera argumenten på Dilemma, inte här. Svara mig kort på svenska, utan tekniska ID:n.',
			'Saknas Dilemma-verktygen? Säg bara att jag ska slå på Dilemma under + i den här chatten.',
		]));
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

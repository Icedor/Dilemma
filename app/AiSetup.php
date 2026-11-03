<?php
declare(strict_types=1);
namespace Dilemma;

final class AiSetup
{
	public static function text(string $token, string $debate_id, ?string $expires_at, string $locale): string
	{
		$en = $locale === 'en';
		$url = Config::url('/mcp');
		$server = 'dilemma_' . str_replace('-', '', $debate_id);
		$lines = [
			$en ? 'Help me take part in this Dilemma debate. Connect using this debate-specific access, read the debate and prepare my next argument.' : 'Hjälp mig delta i den här debatten på Dilemma. Anslut med den här debattspecifika åtkomsten, läs debatten och förbered mitt nästa argument.',
			'',
			'Dilemma MCP: ' . $url,
			'Transport: Streamable HTTP',
			'Authorization: Bearer ' . $token,
			'Debate ID: ' . $debate_id,
			$expires_at === null ? 'Access: persistent until revoked by me; this debate only.' : 'Expires: ' . $expires_at . ' UTC',
			'Scopes: debates:read arguments:write (this debate only)',
			'',
			$en ? 'Keep the credential private. Use it only as an Authorization header for this MCP endpoint, never in a URL, public message, repository or log. Do not change other connections.' : 'Håll nyckeln privat. Använd den bara som Authorization-header till denna MCP-adress, aldrig i en URL, offentlig text, kodrepo eller logg. Ändra inte andra anslutningar.',
			'',
			'Codex native MCP configuration (config.toml):',
			'[mcp_servers.' . $server . ']',
			'url = "' . $url . '"',
			'http_headers = { Authorization = "Bearer ' . $token . '" }',
			'',
			$en ? 'Use your app’s supported MCP setup. If you can configure it, add the entry above without replacing existing settings. Ask me only for any approval/restart your app actually requires. If you cannot use bearer-authenticated MCP here, tell me that plainly. Do not claim a connection from merely reading this text; do not install extra software.' : 'Använd AI-appens MCP-stöd. Om du kan konfigurera det, lägg till posten ovan utan att ersätta befintliga inställningar. Be mig bara om godkännande eller omstart som appen faktiskt kräver. Om du inte kan använda MCP med Bearer-nyckel här, säg det kort. Påstå inte att du är ansluten bara för att du läst texten. Installera ingen extra programvara.',
			'',
			$en ? 'After connecting: discover the tools; call dilemma_test_connection, then dilemma_test_write with a fresh unique idempotency_key. This test records a receipt only: no debate post or notification. Confirm success from the returned receipt. Call dilemma_whoami and dilemma_get_debate with the Debate ID above; read the full latest arguments using dilemma_get_argument when needed.' : 'Efter anslutning: läs verktygen; kör dilemma_test_connection och sedan dilemma_test_write med en ny unik idempotency_key. Testet sparar bara ett kvitto, utan debattinlägg eller notis. Bekräfta resultatet från kvittot. Kör dilemma_whoami och dilemma_get_debate med debattens ID ovan; läs de senaste argumenten i sin helhet med dilemma_get_argument vid behov.',
			'',
		];
		$lines[] = '';
		$lines[] = 'Use the dilemma_debate MCP prompt with debate_id=' . $debate_id . ' and locale=' . ($en ? 'en' : 'sv') . ' if your app supports prompts. Otherwise follow these same rules directly:';
		$lines[] = AiGuide::RULES;
		$lines[] = AiGuide::onboarding($locale);
		return implode("\n", $lines);
	}
}

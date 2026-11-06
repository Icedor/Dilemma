<?php
// Runs inside the isolated HTTP test server. Claude's prefilled install link and one-click debate enabling.
$install_debate = debate_fixture($a['actor'], user_fixture('InstallB')['actor']);
$guest = http_request('/connect/claude?debate=' . $install_debate['id']);
check($guest['status'] === 303 && str_starts_with($guest['headers']['location'][0], '/login?') && str_contains(urldecode($guest['headers']['location'][0]), '/debate/' . $install_debate['id']), 'Install link sends a signed-out visitor to sign-in and back to the debate');
$install = http_request('/connect/claude?debate=' . $install_debate['id'], null, [], $cookie);
$set_cookie = implode("\n", $install['headers']['set-cookie'] ?? []);
check($install['status'] === 303 && $install['headers']['location'][0] === \Dilemma\Connections::install_url(), 'Install link opens Claude\'s prefilled add-connector form');
check(str_contains($set_cookie, 'dilemma_connect_debate=' . $install_debate['id']) && stripos($set_cookie, 'httponly') !== false && stripos($set_cookie, 'samesite=lax') !== false && str_contains($set_cookie, 'path=/oauth/'), 'The starting debate is remembered only for the consent page');
check(http_request('/connect/claude?debate=' . \Dilemma\Support::id(), null, [], $cookie)['status'] === 404, 'Install link refuses a debate the user is not part of');
$install_client = http_request('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'], 'token_endpoint_auth_method' => 'none'])['json'];
$install_query = http_build_query(['client_id' => $install_client['client_id'], 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback', 'response_type' => 'code', 'code_challenge_method' => 'S256', 'code_challenge' => str_repeat('A', 43)]);
$preselected = http_request('/oauth/authorize?' . $install_query, null, [], $cookie . '; dilemma_connect_debate=' . $install_debate['id']);
check($preselected['status'] === 200 && preg_match('~<option value="' . $install_debate['id'] . '" selected~', $preselected['body']) === 1, 'Consent preselects the debate the human started from');
$plain = http_request('/oauth/authorize?' . $install_query, null, [], $cookie);
check(preg_match('~<option value="[a-f0-9-]{36}" selected~', $plain['body']) === 0, 'Without a starting debate the human must choose one');
check(http_request('/api/connections/claude', ['debate_id' => $install_debate['id']], [], $cookie)['status'] === 403, 'Enabling a debate for Claude requires CSRF protection');
$enable = http_request('/api/connections/claude', ['debate_id' => $install_debate['id']], $session_headers, $cookie);
check($enable['status'] === 409 && $enable['json']['error'] === 'claude_not_connected', 'Enabling waits until Claude is connected to the account');
$native = http_request('/api/connections/native?debate_id=' . $install_debate['id'], null, [], $cookie);
check($native['status'] === 200 && $native['json']['state'] === 'connect' && str_contains($native['json']['native_setup'], 'dilemma_get_instructions'), 'Status reports the next step for this debate');
$page = http_request('/debate/' . $install_debate['id'], null, [], $cookie);
check(str_contains($page['body'], 'href="/connect/claude?debate=' . $install_debate['id'] . '"') && str_contains($page['body'], 'data-claude-state="connect"'), 'Debate page offers the install link as the first step');

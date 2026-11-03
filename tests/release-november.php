<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Dilemma\{Accounts, AiGuide, Avatar, Connections, Db, Debates, Mcp, Moderation, Support, ToolRegistry, Views};

$a = user_fixture('GradientA'); $b = user_fixture('GradientB'); $c = user_fixture('GradientC');
$points = [['color' => '#FFCF24', 'x' => 50, 'y' => 50], ['color' => '#f16916', 'x' => 0, 'y' => 100], ['color' => '#108080', 'x' => 100, 'y' => 0]];
$gradient = Avatar::normalize(Support::json($points));
Accounts::update($a['actor'], ['alias' => 'Gradient tester', 'avatar' => $gradient]);
check(Db::one('SELECT avatar FROM users WHERE id = ?', [$a['actor']['user_id']])['avatar'] === $gradient, 'Three gradient colors and edge coordinates persist');
check(substr_count(Views::avatar($gradient), '<radialGradient') === 2 && str_contains(Views::avatar($gradient), '#ffcf24'), 'Saved gradient renders as native SVG');
foreach (['[]', '[{"color":"red","x":0,"y":0}]', '[{"color":"#ffffff\" onload=alert(1)","x":0,"y":0}]', '[{"color":"#ffffff","x":101,"y":0}]', '[{"color":"#ffffff","x":"0","y":0}]', Support::json(array_fill(0, 4, $points[0]))] as $bad) rejects(fn () => Avatar::normalize($bad), 422, 'Malformed or unsafe avatar is rejected');

$draft = Debates::create($a['actor'], ['idempotency_key' => Support::token(), 'title' => 'Should streets have more trees?', 'stance' => 'More trees improve city life.', 'processing_consent' => true]);
$id = $draft['debate_id'];
$link = Debates::invite($a['actor'], $id, ['idempotency_key' => Support::token()]);
$again = Debates::invite($a['actor'], $id, ['idempotency_key' => Support::token()]);
check($link['invitation_url'] === $again['invitation_url'], 'Repeated sharing preserves the existing invitation URL');
$email = Debates::invite($a['actor'], $id, ['email' => $b['email'], 'idempotency_key' => Support::token()]);
check($email['email_sent'] && $email['invitation_url'] !== $link['invitation_url'], 'Email invitation is explicit and recipient-specific');
$bound = parse_url($email['invitation_url'], PHP_URL_FRAGMENT);
rejects(fn () => Debates::invitation($c['actor'], $bound), 404, 'Email invitation cannot be read by a different account');
Debates::accept($b['actor'], ['token' => $bound, 'accept' => false, 'idempotency_key' => Support::token()]);
$shared = parse_url($link['invitation_url'], PHP_URL_FRAGMENT);
check(Debates::invitation($c['actor'], $shared)['debate']['id'] === $id, 'Declining an email leaves the shared link working');
Debates::accept($b['actor'], ['token' => $shared, 'accept' => false, 'idempotency_key' => Support::token()]);
check(Debates::invitation($c['actor'], $shared)['debate']['id'] === $id, 'A reader cannot invalidate a shared link by declining');
Debates::accept($c['actor'], ['token' => $shared, 'stance' => 'Trees can obstruct narrow streets.', 'accept_terms' => true, 'processing_consent' => true, 'idempotency_key' => Support::token()]);
rejects(fn () => Debates::accept($b['actor'], ['token' => $shared, 'idempotency_key' => Support::token()]), 404, 'A link cannot add a third participant');
check((int) Db::one('SELECT COUNT(*) AS n FROM debate_invitations WHERE debate_id = ? AND encrypted_token IS NOT NULL', [$id])['n'] === 0, 'Accepting removes every recoverable invitation token');

$setup = Connections::setup($a['actor'], ['debate_id' => $id]); preg_match('/Authorization: Bearer ([A-Za-z0-9_-]{43})/', $setup['setup_text'], $match);
$headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $match[1], 'MCP-Protocol-Version' => Mcp::LEGACY];
$rpc = static fn ($method, $params = []) => Mcp::handle('POST', $headers, Support::json(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params]));
check(isset($rpc('initialize', ['protocolVersion' => Mcp::LEGACY])['body']['result']['capabilities']['prompts']), 'MCP advertises its prompts capability');
check($rpc('prompts/list')['body']['result']['prompts'][0]['name'] === 'dilemma_debate', 'AI apps can discover the debate prompt');
$prompt = $rpc('prompts/get', ['name' => 'dilemma_debate', 'arguments' => (object) ['debate_id' => $id, 'locale' => 'sv']]);
check($prompt['status'] === 200 && str_contains(Support::json($prompt['body']), 'Förbered ett svar') && !str_contains(Support::json($prompt['body']), $match[1]), 'Prompt supplies human guidance and scoped context without credentials');
check($rpc('prompts/get', ['name' => 'dilemma_debate', 'arguments' => (object) ['debate_id' => Support::id()]])['status'] === 400, 'Prompt cannot bypass the debate scope');
$actor = Connections::authenticate('Bearer ' . $match[1]);
rejects(fn () => Moderation::apply($a['actor'], ['action' => 'hide_debate', 'target_id' => $id, 'reason' => 'Not allowed']), 403, 'Only an administrator can close debates');
Db::run('UPDATE users SET is_admin = 1 WHERE id = ?', [$b['actor']['user_id']]);
Moderation::apply($b['actor'], ['action' => 'hide_debate', 'target_id' => $id, 'reason' => 'Fixture moderation']);
rejects(fn () => ToolRegistry::invoke($actor, 'dilemma_submit_argument', (object) ['debate_id' => $id, 'revision' => (int) Debates::row($id)['revision'], 'idempotency_key' => Support::token(), 'body' => 'A hidden debate must not accept this argument.']), 409, 'Admin closure blocks agent publication');
echo 'SUCCESS: ' . $checks . " November release checks. Local only.\n";

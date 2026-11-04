<?php
require __DIR__ . '/bootstrap.php';
use Dilemma\{Auth, Db, Support};

function pending_signup(string $email, string $ip): array {
	Auth::request_link(['email' => $email, 'locale' => 'sv'], $ip);
	$rows = Db::all('SELECT * FROM email_verifications WHERE pending_email IS NOT NULL ORDER BY expires_at DESC');
	foreach ($rows as $row) {
		if (Support::unseal($row['pending_email']) !== $email) continue;
		$outbox = Db::one('SELECT encrypted_payload FROM notification_outbox WHERE dedupe_key = ?', [Support::hash('auth:' . $row['token_hash'])]);
		$payload = json_decode(Support::unseal($outbox['encrypted_payload']), true, 32, JSON_THROW_ON_ERROR);
		return $row + ['token' => parse_url($payload['data']['url'], PHP_URL_FRAGMENT)];
	}
	throw new RuntimeException('Pending signup fixture not found');
}

$expired = pending_signup('qa-expired-' . $run . '@example.test', $run . '-expired');
Db::run('UPDATE email_verifications SET expires_at = ? WHERE token_hash = ?', [gmdate('Y-m-d H:i:s', time() - 5), $expired['token_hash']]);
rejects(fn () => Auth::verify($expired['token'], ['alias' => 'Expired', 'accept_terms' => true]), 400, 'Expired signup cannot create an account', 'expired_link');
rejects(fn () => Auth::verify(Support::token(), ['alias' => 'Invalid', 'accept_terms' => true]), 400, 'Invented signup token cannot create an account', 'expired_link');
$pending = pending_signup('qa-two-links-' . $run . '@example.test', $run . '-two');
// A different valid link can finish creating the same account before this one is used.
$email = Support::unseal($pending['pending_email']);
Auth::request_link(['email' => $email, 'alias' => 'Original name', 'accept_terms' => true], $run . '-legacy');
$existing = Db::one('SELECT * FROM users WHERE email = ?', [$email]);
$session = Auth::verify($pending['token'], ['alias' => 'Must not replace name', 'accept_terms' => true]);
check($session['user_id'] === $existing['id'] && Db::one('SELECT alias FROM users WHERE id = ?', [$existing['id']])['alias'] === 'Original name', 'Pending signup reuses an account created by another link without changing its name');
check((int) Db::one('SELECT COUNT(*) AS n FROM users WHERE email = ?', [$email])['n'] === 1, 'Multiple entry paths retain one account per address');
$blocked = pending_signup('qa-blocked-' . $run . '@example.test', $run . '-blocked');
$blocked_email = Support::unseal($blocked['pending_email']);
Auth::request_link(['email' => $blocked_email, 'alias' => 'Blocked fixture', 'accept_terms' => true], $run . '-blocked-existing');
Db::run("UPDATE users SET status = 'suspended' WHERE email = ?", [$blocked_email]);
rejects(fn () => Auth::verify($blocked['token'], ['alias' => 'Bypass', 'accept_terms' => true]), 403, 'Pending signup cannot bypass an account suspension');
$unconsented = pending_signup('qa-consent-' . $run . '@example.test', $run . '-consent');
check(Auth::verify($unconsented['token'], ['alias' => 'No consent', 'accept_terms' => 'true']) === ['registration_required' => true], 'Registration requires explicit boolean consent');
$before = Db::one('SELECT * FROM email_verifications WHERE token_hash = ?', [$unconsented['token_hash']]);
rejects(fn () => Auth::verify($unconsented['token'], ['alias' => ['invalid'], 'accept_terms' => true]), 422, 'Malformed account name is rejected');
check(Db::one('SELECT * FROM email_verifications WHERE token_hash = ?', [$unconsented['token_hash']]) === $before, 'Failed completion leaves the valid link usable');
$done = Auth::verify($unconsented['token'], ['alias' => 'Valid name', 'accept_terms' => true]);
check(Auth::session($done['token'])['alias'] === 'Valid name', 'Corrected completion creates the expected authenticated session');
echo 'SUCCESS: ' . $checks . " email-first security checks.\n";

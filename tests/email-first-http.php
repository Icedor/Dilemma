<?php
use Dilemma\{Db, Support};

// Exercise the visitor's email-only entry through the actual local HTTP routes.
$new_email = 'qa-email-first-' . $run . '@example.test';
$new_return = '/invite#' . Support::token();
$before_mail = (int) Db::one('SELECT COUNT(*) AS n FROM notification_outbox')['n'];
$new_request = http_request('/api/login', ['email' => $new_email, 'locale' => 'en', 'return_to' => $new_return]);
check($new_request['status'] === 200 && $new_request['json'] === ['sent' => true], 'Email-only entry accepts a new visitor');
check(Db::one('SELECT id FROM users WHERE email = ?', [$new_email]) === null, 'An email request alone does not create an account');
check((int) Db::one('SELECT COUNT(*) AS n FROM notification_outbox')['n'] === $before_mail + 1, 'New visitors receive a queued sign-in email');
$pending = Db::one('SELECT * FROM email_verifications WHERE pending_email IS NOT NULL ORDER BY expires_at DESC LIMIT 1');
check($pending['user_id'] === null && $pending['pending_email'] !== $new_email && Support::unseal($pending['pending_email']) === $new_email, 'Pending signup address is encrypted and has no user identity');
$outbox = Db::one("SELECT * FROM notification_outbox WHERE dedupe_key = ?", [Support::hash('auth:' . $pending['token_hash'])]);
$payload = json_decode(Support::unseal($outbox['encrypted_payload']), true, 32, JSON_THROW_ON_ERROR);
$new_token = parse_url($payload['data']['url'], PHP_URL_FRAGMENT);
check($payload['email'] === $new_email && $payload['locale'] === 'en' && strlen($new_token) === 43, 'New visitor email uses the requested address, language and private one-time link');
$verification = http_request('/api/verify', ['token' => $new_token]);
check($verification['status'] === 200 && $verification['json'] === ['registration_required' => true] && !isset($verification['headers']['set-cookie']), 'Verified new address requests name and consent before a session exists');
$missing_consent = http_request('/api/verify', ['token' => $new_token, 'alias' => 'Test New Visitor', 'accept_terms' => false]);
check($missing_consent['json'] === ['registration_required' => true] && Db::one('SELECT id FROM users WHERE email = ?', [$new_email]) === null, 'Name alone cannot bypass registration consent');
check(http_request('/api/verify', ['token' => $new_token, 'alias' => '', 'accept_terms' => true])['status'] === 422, 'Signup requires a nonempty public name');
$completed = http_request('/api/verify', ['token' => $new_token, 'alias' => 'Test New Visitor', 'accept_terms' => true]);
check($completed['status'] === 200 && $completed['json']['redirect'] === $new_return && isset($completed['headers']['set-cookie']), 'New account confirmation signs in and restores the invitation');
$created = Db::one('SELECT * FROM users WHERE email = ?', [$new_email]);
check($created['alias'] === 'Test New Visitor' && $created['locale'] === 'en' && $created['verified_at'] !== null, 'Signup saves the chosen name and verified address');
$used = Db::one('SELECT * FROM email_verifications WHERE token_hash = ?', [$pending['token_hash']]);
check($used['used_at'] !== null && $used['pending_email'] === null && $used['user_id'] === $created['id'], 'Consumed signup removes the pending address and binds the single-use receipt');
check(http_request('/api/verify', ['token' => $new_token])['status'] === 400, 'New-account link cannot be replayed');
$existing = http_request('/api/login', ['email' => $new_email]);
check($existing['json'] === $new_request['json'] && $existing['status'] === $new_request['status'], 'Public email request response does not disclose account existence');
$existing_link = Db::one('SELECT token_hash FROM email_verifications WHERE user_id = ? AND used_at IS NULL', [$created['id']]);
$existing_mail = Db::one('SELECT encrypted_payload FROM notification_outbox WHERE dedupe_key = ?', [Support::hash('auth:' . $existing_link['token_hash'])]);
$existing_payload = json_decode(Support::unseal($existing_mail['encrypted_payload']), true, 32, JSON_THROW_ON_ERROR);
$existing_token = parse_url($existing_payload['data']['url'], PHP_URL_FRAGMENT);
$signed_in = http_request('/api/verify', ['token' => $existing_token, 'alias' => 'Injected replacement', 'accept_terms' => true]);
check($signed_in['status'] === 200 && $signed_in['json']['redirect'] === '/app' && Db::one('SELECT alias FROM users WHERE id = ?', [$created['id']])['alias'] === 'Test New Visitor', 'Returning visitors sign in without changing their existing name');

foreach (['sv', 'en'] as $locale) {
	foreach (['/login', '/login?register=1'] as $entry) {
		$page = http_request($entry . (str_contains($entry, '?') ? '&' : '?') . 'lang=' . $locale);
		$dom = new DOMDocument(); @$dom->loadHTML($page['body']); $xpath = new DOMXPath($dom);
		check($page['status'] === 200 && $xpath->query('//form//input[@type="email" and @required]')->length === 1 && $xpath->query('//form//input[@name="alias"]')->length === 0, 'Both entry links start with one email field in ' . $locale);
		check($xpath->query('//*[@data-auth-sent and @hidden]//*[@data-auth-address]')->length === 1 && $xpath->query('//header//*[contains(@class,"header-actions")]/a[@href="/login?register=1"]')->length === 1, 'Account creation and safe email correction have visible entry points in ' . $locale);
	}
	$page = http_request('/verify?lang=' . $locale);
	$dom = new DOMDocument(); @$dom->loadHTML($page['body']); $xpath = new DOMXPath($dom);
	check($xpath->query('//fieldset[@data-registration and @hidden and @disabled]//input[@name="accept_terms" and @required]')->length === 1, 'Registration fields activate only after email verification in ' . $locale);
	check($xpath->query('//fieldset//a[@target="_blank" and contains(@rel,"noopener")]')->length === 2, 'Legal pages open separately during account completion in ' . $locale);
}

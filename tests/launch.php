<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

use Dilemma\{Auth, Db, I18n, Views};

$email = 'qa-launch-' . $run . '@example.test';
try {
	putenv('APP_ENV=production');
	putenv('LEGAL_REVIEWED=false');
	Auth::request_link(['email' => $email, 'alias' => 'Launch test', 'accept_terms' => true], 'launch-' . $run);
	$account = Db::one('SELECT id, verified_at FROM users WHERE email = ?', [$email]);
	check($account !== null && $account['verified_at'] === null, 'Registration remains available with legal text marked for review; email still needs verification');
	check(Db::one("SELECT COUNT(*) AS n FROM notification_outbox WHERE user_id = ? AND kind = 'login'", [$account['id']])['n'] === 1, 'Registration creates one verification message in the queue only');
	foreach (['sv', 'en'] as $locale) {
		I18n::locale($locale);
		$html = Views::page('/login', null, null);
		check(str_contains($html, 'name="email"') && !str_contains($html, 'class="opening-soon"'), 'Sign-in form remains available: ' . $locale);
	}
	$info = Views::page('/privacy', null, null);
	check(str_contains($info, I18n::t('legal_review')), 'Legal review status is disclosed on the privacy page');
} finally {
	putenv('APP_ENV'); putenv('LEGAL_REVIEWED');
}
echo 'SUCCESS: ' . $checks . " launch checks. No external delivery.\n";

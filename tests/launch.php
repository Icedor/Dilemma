<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

use Dilemma\{Auth, Config, Db, I18n, Views};

$before = (int) Db::one('SELECT COUNT(*) AS total FROM users')['total'];
try {
	putenv('APP_ENV=production');
	putenv('LEGAL_REVIEWED=false');
	rejects(fn () => Auth::request_link(['email' => 'launch-test@example.test', 'alias' => 'Test', 'accept_terms' => true], 'launch-test'), 503, 'Unreviewed production refuses signup before processing personal data', 'opening_soon');
	check((int) Db::one('SELECT COUNT(*) AS total FROM users')['total'] === $before, 'Closed signup creates no account');
	foreach (['sv', 'en'] as $locale) {
		I18n::locale($locale);
		$html = Views::page('/login', null, null);
		check(!str_contains($html, 'name="email"') && str_contains($html, I18n::t('opening_soon')), 'Closed login explains availability without collecting email: ' . $locale);
	}
	putenv('LEGAL_REVIEWED=true');
	check(Config::participation_open(), 'Reviewed production permits participation');
} finally {
	putenv('APP_ENV');
	putenv('LEGAL_REVIEWED');
}
echo 'SUCCESS: ' . $checks . " launch checks.\n";

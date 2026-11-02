<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';

use Dilemma\{Config, Db, Support};

if (isset($argv[1])) Config::load($argv[1]);
$failed = 0;
function verify(string $name, callable $check): void {
	global $failed;
	try { $ok = $check() === true; } catch (Throwable) { $ok = false; }
	if (!$ok) $failed++;
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
}
verify('PHP 8.3 or newer', static fn () => PHP_VERSION_ID >= 80300);
foreach (['pdo_mysql', 'mbstring', 'openssl', 'curl'] as $extension) verify('Extension ' . $extension, static fn () => extension_loaded($extension));
verify('Production environment', Config::production(...));
verify('Canonical HTTPS URL without credentials, query, port or path', static function (): bool {
	$url = parse_url(Config::url());
	return ($url['scheme'] ?? '') === 'https' && !empty($url['host']) && empty($url['path']) && !isset($url['user']) && !isset($url['pass']) && !isset($url['query']) && !isset($url['fragment']) && !isset($url['port']);
});
verify('Encryption key configured', static fn () => strlen(Config::key()) === 32);
verify('Dedicated production DB identity', static fn () => Config::get('DB_USER') !== '' && Config::get('DB_USER') !== 'root' && Config::get('DB_PASSWORD') !== '' && Config::get('DB_NAME') !== '' && !in_array(Config::get('DB_NAME'), ['dilemma_dev', 'dilemma_test'], true));
verify('Database and current migration checksums', static function (): bool {
	$rows = Db::all('SELECT name, checksum FROM schema_migrations');
	$applied = array_column($rows, 'checksum', 'name');
	foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') as $path) if (!hash_equals($applied[basename($path)] ?? '', hash_file('sha256', $path))) return false;
	return true;
});
verify('SMTP encryption and credentials configured', static fn () => Config::get('MAIL_TRANSPORT') === 'smtp' && Config::get('MAIL_HOST') !== '' && Config::get('MAIL_USERNAME') !== '' && Config::get('MAIL_PASSWORD') !== '' && in_array(Config::get('MAIL_ENCRYPTION'), ['tls', 'starttls'], true) && in_array(Config::get('MAIL_PORT'), ['465', '587', '2525'], true));
verify('Valid sender address', static fn () => Support::email(Config::get('MAIL_FROM')) !== '');
verify('Controller and privacy contact recorded', static fn () => Config::get('LEGAL_OWNER') !== '' && Support::email(Config::get('PRIVACY_CONTACT')) !== '');
if (Config::get('LEGAL_REVIEWED') !== 'true') echo "INFO Privacy/terms are marked for final owner review.\n";
verify('Private runtime is writable outside public_html', static function (): bool {
	$runtime = Config::storage(); $webroot = realpath(dirname(__DIR__) . '/public_html');
	return is_writable($runtime) && $webroot !== false && !str_starts_with(realpath($runtime), $webroot . DIRECTORY_SEPARATOR);
});
echo $failed ? "NOT READY: resolve failed checks before release. No values or secrets printed.\n" : "Configuration checks pass. Web-server mapping, SMTP delivery and real clients still require verification.\n";
exit($failed ? 1 : 0);

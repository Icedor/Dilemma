<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';

use Dilemma\Db;

$db = Db::connection();
if ((int) $db->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':migrations'), 10)")->fetchColumn() !== 1) throw new RuntimeException('Migration lock unavailable');
try {
	$db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(100) PRIMARY KEY, checksum CHAR(64) NOT NULL, applied_at DATETIME NOT NULL) ENGINE=InnoDB');
	foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') as $path) {
		$name = basename($path);
		$checksum = hash_file('sha256', $path);
		$existing = Db::one('SELECT checksum FROM schema_migrations WHERE name = ?', [$name]);
		if ($existing) {
			if (!hash_equals($existing['checksum'], $checksum)) throw new RuntimeException('Applied migration checksum mismatch: ' . $name);
			continue;
		}
		$db->exec(file_get_contents($path));
		Db::run('INSERT INTO schema_migrations VALUES (?, ?, UTC_TIMESTAMP())', [$name, $checksum]);
		echo 'Applied ' . $name . PHP_EOL;
	}
	echo "Migrations current.\n";
} finally {
	$db->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':migrations'))");
}

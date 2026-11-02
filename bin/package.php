<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
if (!class_exists(ZipArchive::class)) throw new RuntimeException('The standard PHP Zip extension is required.');
$root = dirname(__DIR__);
$paths = [];
foreach (['app', 'database/migrations', 'public_html'] as $directory) {
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
	foreach ($iterator as $file) {
		if ($file->isLink() || !$file->isFile()) throw new RuntimeException('Unexpected release entry.');
		$relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
		if (!preg_match('~^(?:app/(?:[A-Za-z0-9]+\.php|views/[a-z-]+\.php)|database/migrations/[0-9]{3}_[a-z_]+\.sql|public_html/(?:index\.php|\.htaccess|\.user\.ini|manifest\.webmanifest|sw\.js|offline\.html|assets/(?:app\.js|clipboard\.js|language\.js|pwa\.js|style\.css|gradient-(?:yellow-v2|red)\.webp|favicon\.svg|share-opinion\.png|share-change-your-mind\.jpg|icon-(?:192|512|maskable)\.png|badge\.png)))$~D', $relative)) throw new RuntimeException('Unallowlisted file in release directories: ' . $relative);
		$paths[] = $relative;
	}
}
foreach (['bin/migrate.php', 'bin/maintenance.php', 'bin/admin.php', 'bin/preflight.php', 'bin/verify-release.php', 'bin/init-push.php', '.env.example', 'README.md', 'docs/DEPLOYMENT.md'] as $path) $paths[] = $path;
if (is_file($root . '/LICENSE')) $paths[] = 'LICENSE';
sort($paths);
$manifest = ['format' => 1, 'created_at' => gmdate('c'), 'files' => []];
foreach ($paths as $path) {
	if (!is_file($root . '/' . $path)) throw new RuntimeException('Missing release source: ' . $path);
	$manifest['files'][$path] = hash_file('sha256', $root . '/' . $path);
}
$fingerprint = substr(hash('sha256', json_encode($manifest['files'], JSON_THROW_ON_ERROR)), 0, 16);
$directory = $root . '/var/releases';
if (!is_dir($directory)) mkdir($directory, 0700, true);
$output = $directory . '/dilemma-' . $fingerprint . '.zip';
if (file_exists($output)) throw new RuntimeException('This release archive already exists; it was not overwritten.');
$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Cannot create private release archive.');
foreach ($paths as $path) if (!$zip->addFile($root . '/' . $path, $path)) throw new RuntimeException('Cannot add release source.');
$zip->addFromString('release-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
if (!$zip->close()) throw new RuntimeException('Cannot finalize release archive.');
echo 'Created ' . basename($output) . ' (' . count($paths) . " files); no environment, keys, tests or runtime data included.\n";
echo 'SHA256 ' . hash_file('sha256', $output) . PHP_EOL;

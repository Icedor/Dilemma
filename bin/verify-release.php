<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$root = realpath(dirname(__DIR__));
$manifest = json_decode(file_get_contents($root . '/release-manifest.json'), true, 32, JSON_THROW_ON_ERROR);
if (($manifest['format'] ?? 0) !== 1 || !is_array($manifest['files'] ?? null)) throw new RuntimeException('Invalid release manifest.');
$failed = 0;
foreach ($manifest['files'] as $path => $expected) {
	if (!is_string($path) || str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/') || str_contains($path, ':') || !is_string($expected)) throw new RuntimeException('Invalid release path.');
	$absolute = realpath($root . '/' . $path);
	if (!$absolute || !str_starts_with($absolute, $root . DIRECTORY_SEPARATOR) || !is_file($absolute) || is_link($root . '/' . $path) || !hash_equals($expected, hash_file('sha256', $absolute))) { echo 'FAIL ' . $path . PHP_EOL; $failed++; }
}
echo $failed ? "Release verification failed.\n" : 'Verified ' . count($manifest['files']) . " release file hashes.\n";
exit($failed ? 1 : 0);

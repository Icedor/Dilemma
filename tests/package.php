<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
$root = realpath(dirname(__DIR__));
$name = $argv[1] ?? '';
if (!preg_match('/^dilemma-[a-f0-9]{16}\.zip$/D', $name)) throw new RuntimeException('Pass the generated release ZIP basename.');
$path = $root . '/var/releases/' . $name;
$zip = new ZipArchive();
if ($zip->open($path) !== true) throw new RuntimeException('Cannot open generated release.');
$files = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
	$file = $zip->getNameIndex($i);
	if (str_contains($file, '..') || str_contains($file, '\\') || str_starts_with($file, '/') || str_contains($file, ':') || (str_starts_with($file, '.env') && $file !== '.env.example') || preg_match('~^(?:tests|var|\.git|\.private)/~', $file)) throw new RuntimeException('Unsafe or private release entry.');
	$files[] = $file;
}
echo "PASS Release entries exclude secrets, fixtures and runtime data.\n";
$directory = sys_get_temp_dir() . '/dilemma-package-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$target = realpath($directory);
$directories = [];
try {
	if (!$zip->extractTo($target)) throw new RuntimeException('Cannot extract generated release.');
	$zip->close();
	foreach ($files as $file) {
		$parent = dirname($target . '/' . $file);
		while ($parent !== $target && strlen($parent) > strlen($target)) { $directories[$parent] = true; $parent = dirname($parent); }
	}
	$verify = static function () use ($target): int {
		$process = proc_open([PHP_BINARY, $target . '/bin/verify-release.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $target, null, ['bypass_shell' => true, 'create_no_window' => true]);
		fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
		$code = proc_close($process); if ($stderr !== '') throw new RuntimeException('Release verifier produced an error.');
		return $code;
	};
	if ($verify() !== 0) throw new RuntimeException('Extracted release failed hash verification.');
	echo "PASS Extracted release verifies all manifest hashes.\n";
	file_put_contents($target . '/app/Config.php', "\n// Integrity-test change.\n", FILE_APPEND);
	if ($verify() !== 1) throw new RuntimeException('Modified release was not detected.');
	echo "PASS Modified release file fails integrity verification.\n";
} finally {
	foreach ($files as $file) {
		$absolute = realpath($target . '/' . $file);
		if ($absolute !== false && str_starts_with($absolute, $target . DIRECTORY_SEPARATOR) && is_file($absolute)) unlink($absolute);
	}
	$directories = array_keys($directories); usort($directories, static fn ($a, $b) => strlen($b) <=> strlen($a));
	foreach ($directories as $folder) { $absolute = realpath($folder); if ($absolute !== false && str_starts_with($absolute, $target . DIRECTORY_SEPARATOR)) rmdir($absolute); }
	rmdir($target);
}

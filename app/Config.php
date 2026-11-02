<?php

declare(strict_types=1);

namespace Dilemma;

final class Config
{
	private static array $values = [];

	public static function load(string $path): void
	{
		if (!is_file($path)) return;
		foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) continue;
			if (!preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $parts)) continue;
			$value = trim($parts[2]);
			if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) $value = substr($value, 1, -1);
			self::$values[$parts[1]] = $value;
		}
	}

	public static function get(string $key, string $default = ''): string
	{
		$environment = getenv($key);
		return $environment !== false ? $environment : (self::$values[$key] ?? $default);
	}

	public static function production(): bool
	{
		return self::get('APP_ENV', 'production') === 'production';
	}

	public static function url(string $path = ''): string
	{
		return rtrim(self::get('APP_URL', 'https://dilemma.se'), '/') . $path;
	}

	public static function storage(string $path = ''): string
	{
		$base = dirname(__DIR__) . '/var';
		if (!is_dir($base)) mkdir($base, 0700, true);
		return $base . ($path !== '' ? '/' . $path : '');
	}

	public static function key(): string
	{
		$key = base64_decode(self::get('APP_KEY'), true);
		if ($key === false || strlen($key) !== 32) throw new \RuntimeException('APP_KEY must be a base64 encoded 32-byte key.');
		return $key;
	}
}

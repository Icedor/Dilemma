<?php

declare(strict_types=1);

namespace Dilemma;

final class Support
{
	public static function id(): string
	{
		$bytes = random_bytes(16);
		$bytes[6] = chr((ord($bytes[6]) & 15) | 64);
		$bytes[8] = chr((ord($bytes[8]) & 63) | 128);
		$hex = bin2hex($bytes);
		return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
	}

	public static function token(): string { return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='); }
	public static function hash(string $value): string { return hash('sha256', $value); }
	public static function now(): string { return gmdate('Y-m-d H:i:s'); }
	public static function json(mixed $value): string { return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
	public static function escape(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

	public static function text(array $input, string $key, int $min, int $max): string
	{
		$value = $input[$key] ?? '';
		if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) throw new Failure(422, 'invalid_field', ['field' => $key]);
		$value = trim($value);
		if (mb_strlen($value) < $min || mb_strlen($value) > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) throw new Failure(422, 'invalid_field', ['field' => $key]);
		return $value;
	}

	public static function uuid(mixed $value): string
	{
		if (!is_string($value) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $value)) throw new Failure(404, 'not_found');
		return $value;
	}

	public static function integer(mixed $value, int $min, int $max, string $field): int
	{
		if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < $min || (int) $value > $max) throw new Failure(422, 'invalid_field', ['field' => $field]);
		return (int) $value;
	}

	public static function email(string $value): string
	{
		$value = strtolower(trim($value));
		if (strlen($value) > 254 || !filter_var($value, FILTER_VALIDATE_EMAIL) || strpbrk($value, "\r\n") !== false) throw new Failure(422, 'invalid_email');
		return $value;
	}

	public static function url(string $value, bool $private_chat = false): string
	{
		if ($value === '') return '';
		$parts = parse_url($value);
		if (strlen($value) > 2000 || !filter_var($value, FILTER_VALIDATE_URL) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || preg_match('/[\x00-\x20\x7F]/', $value)) throw new Failure(422, 'invalid_url');
		if ($private_chat) {
			$host = strtolower($parts['host'] ?? '');
			$path = $parts['path'] ?? '';
			if (!(($host === 'chatgpt.com' && preg_match('~^/c/[a-zA-Z0-9-]+$~D', $path)) || ($host === 'claude.ai' && preg_match('~^/chat/[a-zA-Z0-9-]+$~D', $path))) || isset($parts['query']) || isset($parts['fragment'])) throw new Failure(422, 'invalid_chat_url');
		}
		return $value;
	}

	public static function seal(string $value): string
	{
		$iv = random_bytes(12);
		$cipher = openssl_encrypt($value, 'aes-256-gcm', Config::key(), OPENSSL_RAW_DATA, $iv, $tag, 'dilemma:v1');
		if ($cipher === false) throw new \RuntimeException('Encryption failed');
		return base64_encode($iv . $tag . $cipher);
	}

	public static function unseal(string $value): string
	{
		$raw = base64_decode($value, true);
		if ($raw === false || strlen($raw) < 28) throw new \RuntimeException('Invalid encrypted record');
		$result = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', Config::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), 'dilemma:v1');
		if ($result === false) throw new \RuntimeException('Encrypted record verification failed');
		return $result;
	}

	public static function rate(string $bucket, int $limit, int $seconds): void
	{
		$key = self::hash($bucket . ':' . (int) floor(time() / $seconds));
		Db::run('INSERT INTO rate_limits (bucket, hits, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE hits = hits + 1', [$key, gmdate('Y-m-d H:i:s', time() + $seconds * 2)]);
		$row = Db::one('SELECT hits FROM rate_limits WHERE bucket = ?', [$key]);
		if ((int) $row['hits'] > $limit) throw new Failure(429, 'rate_limited');
	}
}

<?php

declare(strict_types=1);

namespace Dilemma;

final class Idempotency
{
	public static function key(array $input): string
	{
		$key = Support::text($input, 'idempotency_key', 8, 128);
		if (!preg_match('/^[A-Za-z0-9_.:-]+$/D', $key)) throw new Failure(422, 'invalid_field', ['field' => 'idempotency_key']);
		return Support::hash($key);
	}

	private static function canonical(mixed $value): mixed
	{
		if (!is_array($value)) return $value;
		if (!array_is_list($value)) ksort($value);
		return array_map(self::canonical(...), $value);
	}

	public static function replay(string $user_id, string $operation, array $input): ?array
	{
		$row = Db::one('SELECT * FROM idempotency_keys WHERE user_id = ? AND operation = ? AND key_hash = ?', [$user_id, $operation, self::key($input)]);
		if (!$row) return null;
		if (!hash_equals($row['payload_hash'], Support::hash(Support::json(self::canonical($input))))) throw new Failure(409, 'idempotency_conflict');
		$stored = json_decode($row['receipt'], true, 32, JSON_THROW_ON_ERROR);
		return json_decode(Support::unseal($stored['sealed']), true, 32, JSON_THROW_ON_ERROR) + ['idempotent_replay' => true];
	}

	public static function save(string $user_id, string $operation, array $input, array $receipt): array
	{
		Db::run('INSERT INTO idempotency_keys (user_id, operation, key_hash, payload_hash, receipt, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$user_id, $operation, self::key($input), Support::hash(Support::json(self::canonical($input))), Support::json(['sealed' => Support::seal(Support::json($receipt))]), Support::now()]);
		return $receipt + ['idempotent_replay' => false];
	}
}

<?php

declare(strict_types=1);

namespace Dilemma;

use PDO;

final class Db
{
	private static ?PDO $connection = null;

	public static function connection(): PDO
	{
		if (self::$connection === null) {
			$dsn = 'mysql:host=' . Config::get('DB_HOST', '127.0.0.1') . ';port=' . Config::get('DB_PORT', '3306') . ';dbname=' . Config::get('DB_NAME', 'dilemma') . ';charset=utf8mb4';
			self::$connection = new PDO($dsn, Config::get('DB_USER'), Config::get('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
			self::$connection->exec("SET time_zone = '+00:00'");
		}
		return self::$connection;
	}

	public static function run(string $sql, array $parameters = []): \PDOStatement
	{
		$statement = self::connection()->prepare($sql);
		$statement->execute($parameters);
		return $statement;
	}

	public static function one(string $sql, array $parameters = []): ?array
	{
		return self::run($sql, $parameters)->fetch() ?: null;
	}

	public static function all(string $sql, array $parameters = []): array
	{
		return self::run($sql, $parameters)->fetchAll();
	}

	public static function transaction(callable $operation): mixed
	{
		$db = self::connection();
		if ($db->inTransaction()) return $operation();
		for ($attempt = 0; ; $attempt++) {
			$db->beginTransaction();
			try {
				$result = $operation();
				$db->commit();
				return $result;
			} catch (\Throwable $error) {
				if ($db->inTransaction()) $db->rollBack();
				// Transaction callbacks contain database operations only, never delivery.
				// MariaDB snapshot conflicts (1020) require the same full rollback as deadlocks.
				if ($error instanceof \PDOException && in_array((int) ($error->errorInfo[1] ?? 0), [1020, 1205, 1213], true) && $attempt < 2) {
					usleep(random_int(10000, 50000));
					continue;
				}
				throw $error;
			}
		}
	}
}

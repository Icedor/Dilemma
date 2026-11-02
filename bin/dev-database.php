<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';
use Dilemma\Config;

if (Config::production() || Config::get('DB_HOST') !== '127.0.0.1' || Config::get('DB_PORT') !== '3317') throw new RuntimeException('Only the isolated local development server is allowed.');
$db = new PDO('mysql:host=127.0.0.1;port=3317;charset=utf8mb4', Config::get('DB_USER'), Config::get('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (['dilemma_dev', 'dilemma_test'] as $name) {
	$db->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
echo "Local development/test databases ready.\n";

<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
	$prefix = 'Dilemma\\';
	if (str_starts_with($class, $prefix)) {
		$path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
		if (is_file($path)) require $path;
	}
});

date_default_timezone_set('UTC');
Dilemma\Config::load(dirname(__DIR__) . '/.env');

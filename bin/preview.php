<?php

declare(strict_types=1);

// Loopback development only. This file must never be deployed to a webroot.
if (PHP_SAPI !== 'cli-server') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';
if (Dilemma\Config::production() || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) exit(1);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$assets = ['/assets/app.js' => 'text/javascript', '/assets/clipboard.js' => 'text/javascript', '/assets/language.js' => 'text/javascript', '/assets/pwa.js' => 'text/javascript', '/assets/style.css' => 'text/css', '/assets/gradient-yellow-v2.webp' => 'image/webp', '/assets/gradient-red.webp' => 'image/webp', '/assets/favicon.svg' => 'image/svg+xml', '/manifest.webmanifest' => 'application/manifest+json', '/sw.js' => 'text/javascript', '/offline.html' => 'text/html', '/assets/share-opinion.png' => 'image/png', '/assets/icon-192.png' => 'image/png', '/assets/icon-512.png' => 'image/png', '/assets/icon-maskable.png' => 'image/png', '/assets/badge.png' => 'image/png'];
if (isset($assets[$path])) {
	header('Content-Type: ' . $assets[$path]);
	header('X-Content-Type-Options: nosniff');
	readfile(dirname(__DIR__) . '/public_html' . $path);
	return;
}
Dilemma\Http::run();

<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';
use Dilemma\Config;
use Dilemma\Db;
use Dilemma\Support;

$email = Support::email(Config::get('ADMIN_EMAIL'));
$user = Db::one("SELECT id FROM users WHERE email = ? AND status = 'active' AND verified_at IS NOT NULL", [$email]);
if (!$user) throw new RuntimeException('Configured administrator must first sign in with verified email.');
Db::transaction(static function () use ($user): void {
	Db::run('UPDATE users SET is_admin = 1 WHERE id = ?', [$user['id']]);
	Db::run('INSERT INTO audit_events (actor_id, action, target_id, data, created_at) VALUES (?, ?, ?, ?, ?)', [$user['id'], 'admin_provisioned_cli', $user['id'], '{}', Support::now()]);
});
echo "Configured verified account granted administrator access.\n";

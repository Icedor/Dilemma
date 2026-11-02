<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';

use Dilemma\Db;
use Dilemma\Mail;
use Dilemma\Support;

echo Support::json(Mail::work()) . PHP_EOL;
echo Support::json(['push' => Dilemma\Push::work()]) . PHP_EOL;
Db::run("DELETE FROM push_outbox WHERE created_at < UTC_TIMESTAMP() - INTERVAL 1 DAY AND status <> 'blocked'");
Db::run('DELETE FROM rate_limits WHERE expires_at < UTC_TIMESTAMP()');
Db::run('DELETE FROM auth_sessions WHERE expires_at < UTC_TIMESTAMP()');
Db::run('DELETE FROM email_verifications WHERE expires_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
Db::run('DELETE FROM oauth_codes WHERE expires_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
Db::run('DELETE FROM mcp_tokens WHERE expires_at < UTC_TIMESTAMP() - INTERVAL 31 DAY');
Db::run("UPDATE notification_outbox SET status = 'failed', lease_until = NULL, last_error = 'delivery_interrupted' WHERE status = 'sending' AND lease_until < UTC_TIMESTAMP() AND attempts >= 8");
Db::run("UPDATE notification_outbox SET status = 'failed', encrypted_payload = '', last_error = 'expired' WHERE created_at < UTC_TIMESTAMP() - INTERVAL 7 DAY AND status IN ('failed', 'pending')");

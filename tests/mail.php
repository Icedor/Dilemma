<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Dilemma\{Config, Db, Mail, Support};

if (Config::get('MAIL_TRANSPORT') !== 'spool') throw new RuntimeException('Mail tests require local spool transport.');
// This guarded disposable database contains only synthetic test accounts.
Db::run("UPDATE notification_outbox SET available_at = UTC_TIMESTAMP() + INTERVAL 1 HOUR WHERE status = 'pending'");
$key = 'mail-fixture-' . $run;
$data = ['url' => Config::url('/app'), 'title' => 'Åsikter, <etik> & källor'];
Mail::enqueue($key, 'argument', 'test@example.test', 'sv', $data);
Mail::enqueue($key, 'argument', 'test@example.test', 'sv', $data);
$rows = Db::all('SELECT * FROM notification_outbox WHERE dedupe_key = ?', [Support::hash($key)]);
check(count($rows) === 1, 'Mail enqueue suppresses duplicate event recipients');
$row = $rows[0];
check(!str_contains($row['encrypted_payload'], 'test@example.test'), 'Recipient and message content are encrypted in the queue');
$original_from = Config::get('MAIL_FROM');
putenv('MAIL_FROM=invalid-address');
$result = Mail::work(1);
$failed = Db::one('SELECT * FROM notification_outbox WHERE id = ?', [$row['id']]);
check($result['failed'] === 1 && $failed['status'] === 'pending' && (int) $failed['attempts'] === 1 && $failed['available_at'] > Support::now(), 'Delivery failure schedules backoff without losing the queue item');
check($failed['last_error'] === 'delivery_failed', 'Delivery logs exclude credentials and message contents');
putenv('MAIL_FROM=' . $original_from);
Db::run('UPDATE notification_outbox SET available_at = UTC_TIMESTAMP() WHERE id = ?', [$row['id']]);
$result = Mail::work(1);
$sent = Db::one('SELECT * FROM notification_outbox WHERE id = ?', [$row['id']]);
$path = Config::storage('mail/' . $row['id'] . '.eml');
check($result['sent'] === 1 && $sent['status'] === 'sent' && is_file($path), 'Retried message reaches the isolated local spool');
check($sent['encrypted_payload'] === '' && $sent['lease_until'] === null, 'Successful delivery erases queued private payload and lease');
$message = file_get_contents($path);
check(str_contains($message, 'Message-ID: <' . $row['id'] . '@'), 'Delivery uses a stable message identifier');
$parts = explode('Content-Transfer-Encoding: base64', $message);
$html = base64_decode(trim(explode('--dilemma-', explode("\r\n\r\n", $parts[2], 2)[1])[0]));
check(str_contains($html, 'Åsikter, &lt;etik&gt; &amp; källor'), 'Localized mail preserves UTF-8 and escapes user text');
check(Mail::work(1)['sent'] === 0, 'A completed delivery is not immediately resent');
unlink($path);
echo 'SUCCESS: ' . $checks . " mail checks. Spool only; no external delivery.\n";

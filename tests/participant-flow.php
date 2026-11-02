<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

use Dilemma\{Auth, Arguments, Connections, Db, DebateActions, Debates, Mail, ReadModel, Support};

$a = user_fixture('FlowA'); $b = user_fixture('FlowB'); $outsider = user_fixture('FlowC');
$created = Debates::create($a['actor'], ['idempotency_key' => Support::token(), 'title' => 'Public transport should be free', 'stance' => 'It should be funded collectively.', 'processing_consent' => true]);
$id = $created['debate_id'];
$row = Debates::row($id);
check($row['thesis'] === $row['title'] && $row['topic'] === 'Övrigt', 'Simple opinion form creates a complete debate without duplicate required fields');
check((int) Debates::member($id, $a['actor']['user_id'])['notify'] === 0, 'Creator notifications are off unless explicitly chosen');
$link = Debates::invite($a['actor'], $id, ['idempotency_key' => Support::token()]);
Debates::accept($b['actor'], ['idempotency_key' => Support::token(), 'token' => parse_url($link['invitation_url'], PHP_URL_FRAGMENT), 'stance' => 'Fares help pay for better services.', 'accept_terms' => true, 'processing_consent' => true]);
check((int) Debates::member($id, $b['actor']['user_id'])['notify'] === 0, 'Invited participant notifications are also off by default');
$chosen = debate_fixture($a['actor'], $b['actor']);
check((int) Debates::member($chosen['id'], $a['actor']['user_id'])['notify'] === 1 && (int) Debates::member($chosen['id'], $b['actor']['user_id'])['notify'] === 1, 'Explicit choices persist at both creation and acceptance');
$initial = (int) Db::one('SELECT COUNT(*) AS n FROM notification_outbox WHERE user_id IN (?, ?) AND kind <> ?', [$a['actor']['user_id'], $b['actor']['user_id'], 'login'])['n'];
$count = static fn (): int => (int) Db::one('SELECT COUNT(*) AS n FROM notification_outbox WHERE user_id IN (?, ?) AND kind <> ?', [$a['actor']['user_id'], $b['actor']['user_id'], 'login'])['n'] - $initial;
Mail::notify($id, $a['actor']['user_id'], 'argument', Debates::event($id, $a['actor']['user_id'], 'argument_published'));
check($count() === 0, 'No optional mail is queued before either participant opts in');
$act = static fn (array $actor, string $action, array $extra = []): array => DebateActions::apply($actor, $id, ['idempotency_key' => Support::token(), 'revision' => (int) Debates::row($id)['revision'], 'action' => $action] + $extra);
$act($b['actor'], 'notifications', ['notify' => true]);
Mail::notify($id, $a['actor']['user_id'], 'argument', Debates::event($id, $a['actor']['user_id'], 'argument_published'));
check($count() === 1, 'Only the participant who opted in receives the update');
$act($b['actor'], 'notifications', ['notify' => false]);
Mail::notify($id, $a['actor']['user_id'], 'argument', Debates::event($id, $a['actor']['user_id'], 'argument_published'));
check($count() === 1, 'Opting out prevents future notifications');
rejects(fn () => $act($outsider['actor'], 'notifications', ['notify' => true]), 404, 'Another account cannot change participant notification choices');
$connection = actor_token($a['actor'], true, $id);
rejects(fn () => $act($connection['actor'], 'withdraw', ['confirm' => true]), 403, 'An AI connection cannot decide that its human gives up');
rejects(fn () => $act($a['actor'], 'withdraw'), 422, 'Ending without persuasion still requires human confirmation');
$result = $act($a['actor'], 'withdraw', ['confirm' => true]);
$row = Debates::row($id);
check($result['status'] === 'ended' && $row['end_reason'] === 'withdrawn' && $row['flag_user_id'] === null, 'A human can leave without claiming persuasion or raising a flag');
rejects(fn () => Arguments::submit($a['actor'], $id, ['idempotency_key' => Support::token(), 'revision' => $row['revision'], 'body' => 'This debate is already closed.']), 409, 'Ending participation closes further argument publication');
echo 'SUCCESS: ' . $checks . " participant flow checks. No external mail.\n";

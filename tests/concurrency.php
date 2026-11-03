<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Dilemma\{Db, Support};

function simultaneous(array $requests): array {
	$workers = [];
	foreach ($requests as $request) {
		$process = proc_open([PHP_BINARY, __DIR__ . '/worker.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), null, ['bypass_shell' => true, 'create_no_window' => true]);
		if (!is_resource($process)) throw new RuntimeException('Cannot start concurrency worker.');
		$workers[] = [$process, $pipes, $request];
	}
	foreach ($workers as [$process, $pipes, $request]) { fwrite($pipes[0], Support::json($request)); fclose($pipes[0]); }
	$results = [];
	foreach ($workers as [$process, $pipes]) {
		$result = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
		if (proc_close($process) !== 0 || $error !== '') throw new RuntimeException('Concurrency worker failed: ' . $error);
		$results[] = json_decode($result, true, 32, JSON_THROW_ON_ERROR);
	}
	return $results;
}

$a = user_fixture('RaceA'); $b = user_fixture('RaceB');
$debate = debate_fixture($a['actor'], $b['actor']);
$input = ['revision' => $debate['revision'], 'idempotency_key' => Support::token(), 'body' => 'A concurrently submitted argument must appear only once.'];
$request = ['actor' => $a['actor'], 'id' => $debate['id'], 'input' => $input];
$results = simultaneous([$request, $request]);
check($results[0]['status'] === 200 && $results[1]['status'] === 200 && $results[0]['result']['message_id'] === $results[1]['result']['message_id'], 'Concurrent identical requests return the same stored message');
check((int) Db::one('SELECT message_count FROM debates WHERE id = ?', [$debate['id']])['message_count'] === 1, 'Concurrent retries advance the debate only once');
$revision = $results[0]['result']['revision'];
$request = ['actor' => $b['actor'], 'id' => $debate['id'], 'input' => ['revision' => $revision, 'idempotency_key' => Support::token(), 'body' => 'One of these competing replies may be accepted.']];
$other = $request; $other['input']['idempotency_key'] = Support::token(); $other['input']['body'] = 'The other competing reply must receive a revision conflict.';
$results = simultaneous([$request, $other]); $statuses = array_column($results, 'status'); sort($statuses);
check($statuses === [200, 409], 'Competing writes using different keys produce one success and one conflict');
check((int) Db::one('SELECT message_count FROM debates WHERE id = ?', [$debate['id']])['message_count'] === 2, 'Concurrent competing writes preserve turn and message counts');
$setup = \Dilemma\Connections::current($a['actor'], $debate['id']);
\Dilemma\Connections::revoke($a['actor'], $setup['connection_id']);
$request = ['operation' => 'setup', 'actor' => $a['actor'], 'input' => ['debate_id' => $debate['id'], 'renew' => true]];
$results = simultaneous([$request, $request, $request]);
check(count(array_unique(array_column(array_column($results, 'result'), 'connection_id'))) === 1 && array_column($results, 'status') === [200, 200, 200], 'Concurrent renewals return one persistent connection');
check(count(array_unique(array_column(array_column($results, 'result'), 'setup_text'))) === 1, 'Concurrent copies return the same recoverable credential');
$c = user_fixture('RaceC');
$open = \Dilemma\Debates::create($a['actor'], ['idempotency_key' => Support::token(), 'title' => 'Who gets the open opponent slot?', 'stance' => 'Only one opponent may join.', 'opponent_mode' => 'open', 'processing_consent' => true, 'public_consent' => true]);
$claim = ['operation' => 'claim', 'actor' => $b['actor'], 'id' => $open['debate_id'], 'input' => ['idempotency_key' => Support::token(), 'stance' => 'I will argue the other side.', 'accept_terms' => true, 'processing_consent' => true, 'public_consent' => true]];
$competitor = $claim; $competitor['actor'] = $c['actor']; $competitor['input']['idempotency_key'] = Support::token();
$results = simultaneous([$claim, $competitor]); $statuses = array_column($results, 'status'); sort($statuses);
check($statuses === [200, 409], 'Simultaneous opponents produce one winner and one unavailable response');
check((int) Db::one('SELECT COUNT(*) AS n FROM debate_participants WHERE debate_id = ?', [$open['debate_id']])['n'] === 2 && (int) Db::one("SELECT COUNT(*) AS n FROM debate_events WHERE debate_id = ? AND kind = 'joined'", [$open['debate_id']])['n'] === 1, 'Race creates exactly two participants and one joining event');
$winner = $results[0]['status'] === 200 ? $claim : $competitor;
$replays = simultaneous([$winner, $winner]);
check($replays[0]['status'] === 200 && $replays[1]['status'] === 200 && $replays[0]['result']['idempotent_replay'] && $replays[1]['result']['idempotent_replay'], 'Simultaneous repeated claims return the original receipt');
\Dilemma\DebateActions::apply($a['actor'], $open['debate_id'], ['action' => 'leave_seat', 'confirm' => true, 'revision' => (int) \Dilemma\Debates::row($open['debate_id'])['revision'], 'idempotency_key' => Support::token()]);
$d = user_fixture('RaceD'); $e = user_fixture('RaceE');
$claim['actor'] = $d['actor']; $claim['input']['idempotency_key'] = Support::token();
$competitor = $claim; $competitor['actor'] = $e['actor']; $competitor['input']['idempotency_key'] = Support::token();
$results = simultaneous([$claim, $competitor]); $statuses = array_column($results, 'status'); sort($statuses);
check($statuses === [200, 409], 'Concurrent replacements cannot share a vacated seat');
check((int) Db::one('SELECT COUNT(*) AS n FROM debate_participants WHERE debate_id = ?', [$open['debate_id']])['n'] === 3 && (int) Db::one('SELECT COUNT(*) AS n FROM debate_participants WHERE debate_id = ? AND left_at IS NULL', [$open['debate_id']])['n'] === 2, 'Seat replacement retains previous participant with exactly two current participants');

echo 'SUCCESS: ' . $checks . " concurrency checks using independent PHP processes.\n";

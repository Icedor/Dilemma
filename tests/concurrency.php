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
echo 'SUCCESS: ' . $checks . " concurrency checks using independent PHP processes.\n";

<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
putenv('DB_NAME=dilemma_test');
require dirname(__DIR__) . '/app/bootstrap.php';

use Dilemma\{Arguments, Config, Failure, Support};

if (Config::production() || Config::get('DB_HOST') !== '127.0.0.1' || Config::get('DB_PORT') !== '3317' || Config::get('DB_NAME') !== 'dilemma_test') exit(1);
$request = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
try { echo Support::json(['status' => 200, 'result' => ($request['operation'] ?? '') === 'setup' ? \Dilemma\Connections::setup($request['actor'], $request['input']) : Arguments::submit($request['actor'], $request['id'], $request['input'])]); }
catch (Failure $error) { echo Support::json(['status' => $error->status, 'error' => $error->error]); }

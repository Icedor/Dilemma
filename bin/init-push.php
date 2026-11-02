<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';
$path = Dilemma\Config::storage('push-keys.json');
if (is_file($path)) { Dilemma\Push::keys(); echo "Existing push identity preserved.\n"; exit; }
$key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
if (!$key || !openssl_pkey_export($key, $private)) throw new RuntimeException('Cannot create push signing identity');
$details = openssl_pkey_get_details($key);
$public = rtrim(strtr(base64_encode("\x04" . $details['ec']['x'] . $details['ec']['y']), '+/', '-_'), '=');
$file = fopen($path, 'x');
if (!$file) throw new RuntimeException('Push identity was not overwritten');
chmod($path, 0600);
fwrite($file, json_encode(['private' => $private, 'public' => $public], JSON_THROW_ON_ERROR));
fclose($file);
echo "Push signing identity created in private storage.\n";

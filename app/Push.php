<?php

declare(strict_types=1);

namespace Dilemma;

final class Push
{
	public static function keys(): array
	{
		$path = Config::storage('push-keys.json');
		if (!is_file($path)) throw new Failure(503, 'push_unavailable');
		return json_decode(file_get_contents($path), true, 8, JSON_THROW_ON_ERROR);
	}

	public static function status(array $user): array
	{
		$devices = (int) Db::one('SELECT COUNT(*) AS n FROM push_subscriptions p JOIN auth_sessions s ON s.token_hash = p.session_hash WHERE p.user_id = ? AND s.expires_at > UTC_TIMESTAMP()', [$user['id']])['n'];
		return ['devices' => $devices, 'public_key' => self::keys()['public'], 'enabled' => (bool) $user['push_enabled'], 'notifications_enabled' => (bool) $user['notify'], 'device' => (bool) Db::one('SELECT id FROM push_subscriptions WHERE user_id = ? AND session_hash = ?', [$user['id'], $user['session_hash']])];
	}

	public static function endpoint(string $endpoint): array
	{
		$url = parse_url($endpoint);
		$host = strtolower($url['host'] ?? '');
		$allowed = in_array($host, ['fcm.googleapis.com', 'updates.push.services.mozilla.com'], true) || preg_match('/^(?:[a-z0-9-]+\.)?push\.apple\.com$/D', $host);
		if (!$url || !$allowed || ($url['scheme'] ?? '') !== 'https' || isset($url['user'], $url['pass']) || isset($url['user']) || isset($url['port']) || isset($url['fragment']) || preg_match('/[\x00-\x20\x7f]/', $endpoint) || empty($url['path']) || strlen($endpoint) > 2048) throw new Failure(422, 'invalid_field', ['field' => 'subscription']);
		return ['host' => $host, 'origin' => 'https://' . $host];
	}

	public static function subscribe(array $user, array $input): array
	{
		self::keys();
		Support::rate('push-subscribe:' . $user['id'], 12, 3600);
		$endpoint = Support::text($input, 'endpoint', 20, 2048);
		self::endpoint($endpoint);
		if (($input['consent'] ?? false) !== true) throw new Failure(422, 'confirmation_required');
		return Db::transaction(static function () use ($user, $endpoint): array {
			Debates::actor(['user_id' => $user['id']], true);
			$hash = Support::hash($endpoint);
			$existing = Db::one('SELECT * FROM push_subscriptions WHERE endpoint_hash = ? FOR UPDATE', [$hash]);
			if ($existing && $existing['user_id'] !== $user['id']) throw new Failure(409, 'push_other_account');
			if (!$existing && (int) Db::one('SELECT COUNT(*) AS n FROM push_subscriptions WHERE user_id = ?', [$user['id']])['n'] >= 10) throw new Failure(422, 'push_device_limit');
			// Replace this session's device binding; a shared browser cannot retain another login's delivery.
			Db::run('DELETE FROM push_subscriptions WHERE session_hash = ? OR endpoint_hash = ?', [$user['session_hash'], $hash]);
			Db::run('INSERT INTO push_subscriptions (id, user_id, session_hash, endpoint_hash, encrypted_endpoint, locale, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [Support::id(), $user['id'], $user['session_hash'], $hash, Support::seal($endpoint), I18n::locale(), Support::now()]);
			Db::run('UPDATE users SET push_enabled = 1 WHERE id = ?', [$user['id']]);
			return ['enabled' => true, 'device' => true];
		});
	}

	public static function disable(array $user): array
	{
		return Db::transaction(static function () use ($user): array {
			Debates::actor(['user_id' => $user['id']], true);
			Db::run('UPDATE users SET push_enabled = 0 WHERE id = ?', [$user['id']]);
			Db::run('DELETE FROM push_subscriptions WHERE user_id = ?', [$user['id']]);
			return ['enabled' => false, 'device' => false];
		});
	}

	public static function enqueue(string $user_id, int $sequence, string $debate_id): array
	{
		$receipts = [];
		if (is_file(Config::storage('push-paused')) || Db::one("SELECT id FROM push_outbox WHERE status = 'blocked' LIMIT 1")) return $receipts;
		foreach (Db::all('SELECT p.id FROM push_subscriptions p JOIN auth_sessions s ON s.token_hash = p.session_hash WHERE p.user_id = ? AND s.expires_at > UTC_TIMESTAMP()', [$user_id]) as $subscription) {
			Db::run('INSERT IGNORE INTO push_outbox (id, subscription_id, dedupe_key, available_at, created_at, debate_id) VALUES (?, ?, ?, ?, ?, ?)', [Support::id(), $subscription['id'], Support::hash($sequence . ':' . $subscription['id']), Support::now(), Support::now(), $debate_id]);
			$receipts[] = Support::hash($sequence . ':' . $subscription['id']);
		}
		return $receipts;
	}

	public static function authorization(string $origin, array $keys, ?int $now = null): string
	{
		$encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
		$input = $encode('{"typ":"JWT","alg":"ES256"}') . '.' . $encode(Support::json(['aud' => $origin, 'exp' => ($now ?? time()) + 3600, 'sub' => Config::url('/about')]));
		if (!openssl_sign($input, $der, $keys['private'], OPENSSL_ALGO_SHA256)) throw new \RuntimeException('Push signing failed');
		// OpenSSL returns ASN.1 DER; JWS ES256 requires the fixed-width R || S form.
		if (ord($der[0]) !== 0x30 || ord($der[2]) !== 0x02) throw new \RuntimeException('Invalid ES256 signature');
		$r_length = ord($der[3]);
		$s_offset = 4 + $r_length;
		if (ord($der[$s_offset]) !== 0x02) throw new \RuntimeException('Invalid ES256 signature');
		$r = ltrim(substr($der, 4, $r_length), "\0");
		$s = ltrim(substr($der, $s_offset + 2, ord($der[$s_offset + 1])), "\0");
		if (strlen($r) > 32 || strlen($s) > 32) throw new \RuntimeException('Invalid ES256 coordinates');
		return 'vapid t=' . $input . '.' . $encode(str_pad($r, 32, "\0", STR_PAD_LEFT) . str_pad($s, 32, "\0", STR_PAD_LEFT)) . ', k=' . $keys['public'];
	}

	public static function send(string $endpoint, array $keys): int
	{
		if (!Config::production()) throw new \RuntimeException('External push delivery is disabled outside production');
		$url = self::endpoint($endpoint);
		$addresses = gethostbynamel($url['host']) ?: [];
		if (!$addresses) throw new \RuntimeException('Push DNS unavailable');
		foreach ($addresses as $address) if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) throw new \RuntimeException('Non-public push address rejected');
		$curl = curl_init($endpoint);
		curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => '', CURLOPT_RETURNTRANSFER => false, CURLOPT_WRITEFUNCTION => static fn ($handle, string $data): int => strlen($data), CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_PROXY => '', CURLOPT_RESOLVE => [$url['host'] . ':443:' . $addresses[0]], CURLOPT_HTTPHEADER => ['Authorization: ' . self::authorization($url['origin'], $keys), 'TTL: 300', 'Urgency: normal', 'Topic: dilemma-update', 'Content-Length: 0']]);
		if (curl_exec($curl) === false) throw new \RuntimeException('Push delivery unavailable');
		return (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
	}

	public static function work(int $limit = 10): array
	{
		$counts = ['sent' => 0, 'failed' => 0, 'blocked' => false];
		if (is_file(Config::storage('push-paused')) || Db::one("SELECT id FROM push_outbox WHERE status = 'blocked' LIMIT 1")) return $counts + ['paused' => true];
		for ($i = 0; $i < $limit; $i++) {
			$row = Db::transaction(static function (): ?array {
				$row = Db::one("SELECT o.*, p.encrypted_endpoint, p.user_id FROM push_outbox o JOIN push_subscriptions p ON p.id = o.subscription_id JOIN users u ON u.id = p.user_id JOIN auth_sessions s ON s.token_hash = p.session_hash WHERE u.status = 'active' AND u.notify = 1 AND u.push_enabled = 1 AND (o.debate_id IS NULL OR EXISTS (SELECT 1 FROM debate_participants dp WHERE dp.debate_id = o.debate_id AND dp.user_id = u.id AND dp.notify = 1 AND dp.left_at IS NULL)) AND s.expires_at > UTC_TIMESTAMP() AND o.created_at > UTC_TIMESTAMP() - INTERVAL 1 DAY AND ((o.status = 'pending' AND o.available_at <= UTC_TIMESTAMP()) OR (o.status = 'sending' AND o.lease_until < UTC_TIMESTAMP())) AND o.attempts < 3 ORDER BY o.available_at LIMIT 1 FOR UPDATE SKIP LOCKED");
				if ($row) Db::run("UPDATE push_outbox SET status = 'sending', attempts = attempts + 1, lease_until = ? WHERE id = ?", [gmdate('Y-m-d H:i:s', time() + 120), $row['id']]);
				return $row;
			});
			if (!$row) break;
			try { $status = self::send(Support::unseal($row['encrypted_endpoint']), self::keys()); } catch (\Throwable) { $status = 0; }
			if (in_array($status, [404, 410], true)) { Db::run('DELETE FROM push_subscriptions WHERE id = ?', [$row['subscription_id']]); continue; }
			if (in_array($status, [401, 403, 429], true)) {
				file_put_contents(Config::storage('push-paused'), 'Delivery paused after HTTP ' . $status . '. Verify the cause before resuming.', LOCK_EX);
				Db::run("UPDATE push_outbox SET status = 'blocked', lease_until = NULL WHERE id = ?", [$row['id']]);
				$counts['blocked'] = true;
				break; // Explicit operator correction is required; no authentication/reconnect loop.
			}
			$success = $status >= 200 && $status < 300;
			$retry = !$success && ($status === 0 || $status >= 500) && (int) $row['attempts'] < 2;
			Db::run('UPDATE push_outbox SET status = ?, available_at = ?, lease_until = NULL WHERE id = ?', [$success ? 'sent' : ($retry ? 'pending' : 'failed'), gmdate('Y-m-d H:i:s', time() + 600), $row['id']]);
			$counts[$success ? 'sent' : 'failed']++;
			if (!$success) break;
		}
		return $counts;
	}
}

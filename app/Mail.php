<?php

declare(strict_types=1);

namespace Dilemma;

final class Mail
{
	public static function enqueue(string $dedupe, string $kind, string $email, string $locale, array $data, ?string $user_id = null): void
	{
		$payload = Support::seal(Support::json(['email' => Support::email($email), 'locale' => $locale, 'data' => $data]));
		Db::run('INSERT INTO notification_outbox (id, dedupe_key, user_id, kind, encrypted_payload, available_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE dedupe_key = VALUES(dedupe_key)', [Support::id(), Support::hash($dedupe), $user_id, $kind, $payload, Support::now(), Support::now()]);
	}

	public static function notify(string $debate_id, string $actor_id, string $kind, int $sequence): void
	{
		$debate = Db::one('SELECT title FROM debates WHERE id = ?', [$debate_id]);
		foreach (Db::all("SELECT u.* FROM debate_participants p JOIN users u ON u.id = p.user_id WHERE p.debate_id = ? AND p.user_id <> ? AND u.status = 'active' AND (u.push_enabled = 1 OR (u.notify = 1 AND p.notify = 1))", [$debate_id, $actor_id]) as $user) {
			if ($user['push_enabled']) { Push::enqueue($user['id'], $sequence); continue; }
			self::enqueue('event:' . $sequence . ':' . $user['id'], $kind, $user['email'], $user['locale'], ['url' => Config::url('/debate/' . $debate_id), 'title' => $debate['title']], $user['id']);
		}
	}

	public static function work(int $limit = 20): array
	{
		$counts = ['sent' => 0, 'failed' => 0];
		for ($i = 0; $i < $limit; $i++) {
			$row = Db::transaction(static function (): ?array {
				$row = Db::one("SELECT * FROM notification_outbox WHERE ((status = 'pending' AND available_at <= UTC_TIMESTAMP()) OR (status = 'sending' AND lease_until < UTC_TIMESTAMP())) AND attempts < 8 ORDER BY available_at LIMIT 1 FOR UPDATE SKIP LOCKED");
				if (!$row) return null;
				Db::run("UPDATE notification_outbox SET status = 'sending', attempts = attempts + 1, lease_until = ? WHERE id = ?", [gmdate('Y-m-d H:i:s', time() + 300), $row['id']]);
				return $row;
			});
			if (!$row) break;
			try {
				if (!in_array($row['kind'], ['login', 'invitation'], true) && $row['user_id'] && Db::one('SELECT id FROM users WHERE id = ? AND push_enabled = 1', [$row['user_id']])) {
					Db::run("UPDATE notification_outbox SET status = 'cancelled', encrypted_payload = '', lease_until = NULL WHERE id = ?", [$row['id']]);
					continue;
				}
				$payload = json_decode(Support::unseal($row['encrypted_payload']), true, 32, JSON_THROW_ON_ERROR);
				$message = self::render($row['id'], $row['kind'], $payload);
				if (Config::get('MAIL_TRANSPORT') === 'spool' && !Config::production()) {
					$directory = Config::storage('mail');
					if (!is_dir($directory)) mkdir($directory, 0700, true);
					if (file_put_contents($directory . '/' . $row['id'] . '.eml', $message, LOCK_EX) === false) throw new \RuntimeException('Spool write failed');
				} else {
					(new Smtp())->send($payload['email'], $message);
				}
				Db::run("UPDATE notification_outbox SET status = 'sent', sent_at = ?, lease_until = NULL, encrypted_payload = '', last_error = NULL WHERE id = ?", [Support::now(), $row['id']]);
				$counts['sent']++;
			} catch (\Throwable) {
				$attempts = (int) $row['attempts'] + 1;
				Db::run('UPDATE notification_outbox SET status = ?, available_at = ?, lease_until = NULL, last_error = ? WHERE id = ?', [$attempts >= 8 ? 'failed' : 'pending', gmdate('Y-m-d H:i:s', time() + min(86400, 60 * 2 ** $attempts)), 'delivery_failed', $row['id']]);
				$counts['failed']++;
			}
		}
		return $counts;
	}

	public static function render(string $id, string $kind, array $payload): string
	{
		$en = $payload['locale'] === 'en';
		$subjects = [
			'login' => $en ? 'Sign in to Dilemma' : 'Logga in på Dilemma',
			'invitation' => $en ? 'An invitation to a debate' : 'En inbjudan till en debatt',
			'joined' => $en ? 'Your debate can begin' : 'Er debatt kan börja',
			'argument' => $en ? 'A new argument in your debate' : 'Ett nytt argument i din debatt',
			'resumed' => $en ? 'Your debate has resumed' : 'Er debatt har återupptagits',
			'flag' => $en ? 'A white flag in your debate' : 'En vit flagga i din debatt',
			'ended' => $en ? 'Your debate has ended' : 'Er debatt har avslutats',
		];
		$subject = $subjects[$kind] ?? ($en ? 'Your debate has an update' : 'Din debatt har uppdaterats');
		$data = $payload['data'];
		$copy = [
			'login' => [$en ? 'Sign in.' : 'Logga in.', $en ? 'Sign in to Dilemma' : 'Logga in på Dilemma'],
			'invitation' => [$en ? 'You’re invited.' : 'Du är inbjuden.', $en ? 'View invitation' : 'Se inbjudan'],
			'joined' => [$en ? 'Ready to begin.' : 'Nu kan ni börja.', $en ? 'Open debate' : 'Öppna debatten'],
			'argument' => [$en ? 'A new argument.' : 'Ett nytt argument.', $en ? 'Read the argument' : 'Läs argumentet'],
			'resumed' => [$en ? 'The debate continues.' : 'Debatten fortsätter.', $en ? 'Open debate' : 'Öppna debatten'],
			'flag' => [$en ? 'A white flag.' : 'En vit flagga.', $en ? 'Open debate' : 'Öppna debatten'],
			'ended' => [$en ? 'The debate has ended.' : 'Debatten är avslutad.', $en ? 'View debate' : 'Se debatten'],
		];
		[$heading, $action] = $copy[$kind] ?? [$en ? 'An update in your debate.' : 'Nytt i din debatt.', $en ? 'Open debate' : 'Öppna debatten'];
		$title = $kind === 'login' ? '' : (string) ($data['title'] ?? '');
		$expiry = $en ? 'Valid for 15 minutes. One use only.' : 'Gäller i 15 minuter. Kan användas en gång.';
		$ignore = $en ? 'Didn’t request this? You can ignore this email.' : 'Har du inte begärt länken? Bortse från det här mejlet.';
		$settings = !in_array($kind, ['login', 'invitation'], true);
		$settings_label = $en ? 'Notification settings' : 'Ändra dina notiser';
		$chat = '';
		$invitation_copy = $en ? 'Think differently? Explore the arguments together. Your own AI assistants help you debate; you decide what changes your mind.' : 'Tycker du annorlunda? Pröva argumenten tillsammans. Era egna AI-assistenter hjälper er debattera; ni avgör vad som övertygar.';
		$chat_label = $en ? 'Your AI conversation' : 'Din AI-konversation';
		$preview = $kind === 'login' ? $expiry : ($title !== '' ? $title : $subject);
		$text = $subject . "\n\n" . ($title !== '' ? $title . "\n\n" : '') . $action . ":\n" . $data['url'];
		if ($kind === 'invitation') $text = $subject . "\n\n" . $title . "\n\n" . $invitation_copy . "\n\n" . $action . ":\n" . $data['url'];
		if ($kind === 'login') $text .= "\n\n" . $expiry . "\n" . $ignore;
		if ($chat !== '') $text .= "\n\n" . $chat_label . ': ' . $chat;
		if ($settings) $text .= "\n\n" . $settings_label . ': ' . Config::url('/settings');
		$text .= "\n\nDilemma · " . Config::url();
		$e = Support::escape(...);
		ob_start();
		try { require __DIR__ . '/views/email.php'; $html = ob_get_clean(); }
		catch (\Throwable $error) { ob_end_clean(); throw $error; }
		$boundary = 'dilemma-' . $id;
		$from = Support::email(Config::get('MAIL_FROM'));
		$to = Support::email($payload['email']);
		$domain = parse_url(Config::url(), PHP_URL_HOST) ?: 'dilemma.se';
		return 'From: Dilemma <' . $from . ">\r\nTo: <" . $to . ">\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\nMessage-ID: <" . $id . '@' . $domain . ">\r\nDate: " . gmdate(DATE_RFC2822) . "\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"" . $boundary . "\"\r\n\r\n--" . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text), 76, "\r\n") . '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html), 76, "\r\n") . '--' . $boundary . "--\r\n";
	}
}

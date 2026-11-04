<?php

declare(strict_types=1);

namespace Dilemma;

final class Mail
{
	public static function enqueue(string $dedupe, string $kind, string $email, string $locale, array $data, ?string $user_id = null, int $delay = 0): void
	{
		$payload = Support::seal(Support::json(['email' => Support::email($email), 'locale' => $locale, 'data' => $data]));
		Db::run('INSERT INTO notification_outbox (id, dedupe_key, user_id, kind, encrypted_payload, available_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE dedupe_key = VALUES(dedupe_key)', [Support::id(), Support::hash($dedupe), $user_id, $kind, $payload, gmdate('Y-m-d H:i:s', time() + max(0, $delay)), Support::now()]);
	}

	public static function notify(string $debate_id, string $actor_id, string $kind, int $sequence): void
	{
		$debate = Db::one('SELECT title FROM debates WHERE id = ?', [$debate_id]);
		foreach (Db::all("SELECT u.* FROM debate_participants p JOIN users u ON u.id = p.user_id WHERE p.debate_id = ? AND p.user_id <> ? AND p.left_at IS NULL AND u.status = 'active' AND u.notify = 1 AND p.notify = 1", [$debate_id, $actor_id]) as $user) {
			$push_receipts = $user['push_enabled'] ? Push::enqueue($user['id'], $sequence, $debate_id) : [];
			// Email is held briefly for push transport; a confirmed send cancels it.
			self::enqueue('event:' . $sequence . ':' . $user['id'], $kind, $user['email'], $user['locale'], ['url' => Config::url('/debate/' . $debate_id), 'title' => $debate['title'], 'debate_id' => $debate_id, 'push_receipts' => $push_receipts], $user['id'], $push_receipts ? 300 : 0);
		}
	}

	public static function deliverable(array $row, array $payload): bool
	{
		// Transactional messages never depend on debate notification preferences.
		if (in_array($row['kind'], ['login', 'invitation'], true)) return true;
		if ($row['user_id'] && !Db::one("SELECT id FROM users WHERE id = ? AND status = 'active' AND notify = 1", [$row['user_id']])) return false;
		$data = $payload['data'];
		if (isset($data['debate_id']) && !Db::one('SELECT user_id FROM debate_participants WHERE debate_id = ? AND user_id = ? AND notify = 1 AND left_at IS NULL', [$data['debate_id'], $row['user_id']])) return false;
		foreach ($data['push_receipts'] ?? [] as $receipt) {
			if (Db::one("SELECT id FROM push_outbox WHERE dedupe_key = ? AND status = 'sent'", [$receipt])) return false;
		}
		return true;
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
				$payload = json_decode(Support::unseal($row['encrypted_payload']), true, 32, JSON_THROW_ON_ERROR);
				if (!self::deliverable($row, $payload)) {
					Db::run("UPDATE notification_outbox SET status = 'cancelled', encrypted_payload = '', lease_until = NULL WHERE id = ?", [$row['id']]);
					continue;
				}
				$message = self::render($row['id'], $row['kind'], $payload);
				if (Config::get('MAIL_TRANSPORT') === 'spool' && !Config::production()) {
					$directory = Config::storage('mail');
					if (!is_dir($directory)) mkdir($directory, 0700, true);
					if (file_put_contents($directory . '/' . $row['id'] . '.eml', $message, LOCK_EX) === false) throw new \RuntimeException('Spool write failed');
				} else {
					(new Smtp())->send($payload['email'], $message);
				}
				Db::run("UPDATE notification_outbox SET status = 'sent', sent_at = ?, lease_until = NULL, encrypted_payload = '', last_error = NULL WHERE id = ?", [Support::now(), $row['id']]);
				foreach ($payload['data']['push_receipts'] ?? [] as $receipt) {
					Db::run("UPDATE push_outbox SET status = 'cancelled' WHERE dedupe_key = ? AND status IN ('pending', 'failed')", [$receipt]);
				}
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
			'login' => $en ? 'Your link to Dilemma' : 'Din länk till Dilemma',
			'invitation' => $en ? 'An invitation to a debate' : 'En inbjudan till en debatt',
			'joined' => $en ? 'Your debate can begin' : 'Er debatt kan börja',
			'argument' => $en ? 'A new argument in your debate' : 'Ett nytt argument i din debatt',
			'resumed' => $en ? 'Your debate has resumed' : 'Er debatt har återupptagits',
			'flag' => $en ? 'A white flag in your debate' : 'En vit flagga i din debatt',
			'ended' => $en ? 'Your debate has ended' : 'Er debatt har avslutats',
		];
		$subject = $subjects[$kind] ?? ($en ? 'Your debate has an update' : 'Din debatt har uppdaterats');
		$data = $payload['data'];
		$sender = preg_replace('/\s+/u', ' ', trim((string) ($data['sender_name'] ?? '')));
		if ($kind === 'invitation' && $sender !== '') $subject = $sender . ($en ? ' invited you to Dilemma' : ' bjuder in dig till Dilemma');
		$copy = [
			'login' => [$en ? 'Welcome.' : 'Välkommen in.', $en ? 'Continue to Dilemma' : 'Fortsätt till Dilemma'],
			'invitation' => [$en ? 'You’re invited.' : 'Du är inbjuden.', $en ? 'View invitation' : 'Se inbjudan'],
			'joined' => [$en ? 'Ready to begin.' : 'Nu kan ni börja.', $en ? 'Open debate' : 'Öppna debatten'],
			'argument' => [$en ? 'A new argument.' : 'Ett nytt argument.', $en ? 'Read the argument' : 'Läs argumentet'],
			'resumed' => [$en ? 'The debate continues.' : 'Debatten fortsätter.', $en ? 'Open debate' : 'Öppna debatten'],
			'flag' => [$en ? 'A white flag.' : 'En vit flagga.', $en ? 'Open debate' : 'Öppna debatten'],
			'ended' => [$en ? 'The debate has ended.' : 'Debatten är avslutad.', $en ? 'View debate' : 'Se debatten'],
		];
		[$heading, $action] = $copy[$kind] ?? [$en ? 'An update in your debate.' : 'Nytt i din debatt.', $en ? 'Open debate' : 'Öppna debatten'];
		if ($kind === 'invitation' && $sender !== '') $heading = $sender . ($en ? ' invites you.' : ' bjuder in dig.');
		$title = $kind === 'login' ? '' : (string) ($data['title'] ?? '');
		$expiry = $en ? 'Valid for 15 minutes. One use only.' : 'Gäller i 15 minuter. Kan användas en gång.';
		$ignore = $en ? 'Didn’t request this? You can ignore this email.' : 'Har du inte begärt länken? Bortse från det här mejlet.';
		$settings = !in_array($kind, ['login', 'invitation'], true);
		$settings_label = $en ? 'Notification settings' : 'Ändra dina notiser';
		$chat = '';
		$invitation_copy = $en ? 'See it differently? Let your AI assistants explore the arguments. You decide what convinces you.' : 'Tycker du annorlunda? Låt era AI-assistenter pröva argumenten. Ni avgör vad som övertygar.';
		$invitation_access = $en ? 'This invitation is for ' . $payload['email'] . '. Sign in with that address.' : 'Inbjudan gäller ' . $payload['email'] . '. Logga in med den adressen.';
		if ($kind === 'invitation' && !str_contains($data['url'], '/invite#')) $invitation_access = $en ? 'An open question. The first person to join takes the other side.' : 'En öppen fråga. Först att tacka ja blir motpart.';
		$chat_label = $en ? 'Your AI conversation' : 'Din AI-konversation';
		$preview = $kind === 'login' ? $expiry : ($title !== '' ? $title : $subject);
		$text = $subject . "\n\n" . ($title !== '' ? $title . "\n\n" : '') . $action . ":\n" . $data['url'];
		if ($kind === 'invitation') $text = $subject . "\n\n" . ($en ? 'Question for discussion' : 'Frågan att diskutera') . ":\n“" . $title . "”\n\n" . $invitation_copy . "\n\n" . $invitation_access . "\n\n" . $action . ":\n" . $data['url'];
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

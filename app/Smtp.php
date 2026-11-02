<?php

declare(strict_types=1);

namespace Dilemma;

final class Smtp
{
	private $stream;

	public function send(string $recipient, string $message): void
	{
		$host = Config::get('MAIL_HOST');
		$port = (int) Config::get('MAIL_PORT', '587');
		$mode = Config::get('MAIL_ENCRYPTION', 'starttls');
		if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $host) || !in_array($mode, ['tls', 'starttls'], true) || !in_array($port, [465, 587, 2525], true)) throw new \RuntimeException('Invalid SMTP configuration');
		$context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'allow_self_signed' => false, 'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT]]);
		$this->stream = stream_socket_client(($mode === 'tls' ? 'tls://' : 'tcp://') . $host . ':' . $port, $error_number, $error_message, 15, STREAM_CLIENT_CONNECT, $context);
		if (!$this->stream) throw new \RuntimeException('SMTP connection failed');
		stream_set_timeout($this->stream, 15);
		try {
			$this->response([220]);
			$this->command('EHLO dilemma.se', [250]);
			if ($mode === 'starttls') {
				$this->command('STARTTLS', [220]);
				if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT) !== true) throw new \RuntimeException('SMTP TLS negotiation failed');
				$this->command('EHLO dilemma.se', [250]);
			}
			if (Config::get('MAIL_USERNAME') !== '') {
				$this->command('AUTH LOGIN', [334]);
				$this->command(base64_encode(Config::get('MAIL_USERNAME')), [334]);
				$this->command(base64_encode(Config::get('MAIL_PASSWORD')), [235]);
			}
			$this->command('MAIL FROM:<' . Support::email(Config::get('MAIL_FROM')) . '>', [250]);
			$this->command('RCPT TO:<' . Support::email($recipient) . '>', [250, 251]);
			$this->command('DATA', [354]);
			$body = preg_replace('/(?m)^\./', '..', $message);
			$this->write(rtrim($body, "\r\n") . "\r\n.\r\n");
			$this->response([250]);
			// A failed QUIT must not retry an already acknowledged message.
			try { $this->write("QUIT\r\n"); } catch (\Throwable) { }
		} finally {
			fclose($this->stream);
		}
	}

	private function command(string $command, array $expected): void
	{
		$this->write($command . "\r\n");
		$this->response($expected);
	}

	private function write(string $text): void
	{
		while ($text !== '') {
			$written = fwrite($this->stream, $text);
			if ($written === false || $written === 0) throw new \RuntimeException('SMTP write failed');
			$text = substr($text, $written);
		}
	}

	private function response(array $expected): void
	{
		for ($i = 0; $i < 40; $i++) {
			$line = fgets($this->stream, 4096);
			if ($line === false || !preg_match('/^(\d{3})([ -])/', $line, $match)) throw new \RuntimeException('Invalid SMTP response');
			if ($match[2] === ' ') {
				if (!in_array((int) $match[1], $expected, true)) throw new \RuntimeException('SMTP rejected command');
				return;
			}
		}
		throw new \RuntimeException('SMTP response too long');
	}
}

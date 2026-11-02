<?php
declare(strict_types=1);
// Local previews use synthetic addresses/tokens and never enqueue/send mail.
require dirname(__DIR__) . '/app/bootstrap.php';
use Dilemma\{Config, Mail, Support};
if (PHP_SAPI !== 'cli' || Config::production()) exit(1);
$directory = Config::storage('email-preview');
if (!is_dir($directory)) mkdir($directory, 0700, true);
foreach (['sv', 'en'] as $locale) foreach (['login', 'invitation', 'joined', 'argument', 'resumed', 'flag', 'ended'] as $kind) {
	$payload = ['email' => 'preview@example.test', 'locale' => $locale, 'data' => ['url' => 'https://example.test/' . ($kind === 'login' ? 'verify#synthetic-preview-only' : 'debate/preview'), 'title' => $locale === 'sv' ? 'Borde kollektivtrafik vara gratis?' : 'Should public transport be free?']];
	$message = Mail::render(Support::id(), $kind, $payload);
	preg_match_all('/Content-Transfer-Encoding: base64\r\n\r\n([A-Za-z0-9+\/=\r\n]+)--dilemma-/', $message, $parts);
	$html = base64_decode(trim($parts[1][1] ?? ''), true);
	if ($html === false || $html === '') throw new RuntimeException('Preview decoding failed');
	file_put_contents($directory . '/' . $kind . '-' . $locale . '.html', $html);
	file_put_contents($directory . '/' . $kind . '-' . $locale . '.eml', $message);
}
echo "Generated 14 bilingual local HTML/EML previews. No mail sent.\n";

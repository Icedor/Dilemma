<?php
require __DIR__ . '/bootstrap.php';
use Dilemma\{Auth, AiWaiting, I18n, Views};
$a = user_fixture('ControlsA');
$b = user_fixture('ControlsB');
$debate = debate_fixture($a['actor'], $b['actor']);
$user = Auth::session($a['session']['token']);
foreach (['sv', 'en'] as $locale) {
	I18n::locale($locale);
	$html = Views::page('/debate/' . $debate['id'], $user, $a['actor']);
	$dom = new DOMDocument(); @$dom->loadHTML($html); $xpath = new DOMXPath($dom);
	check($xpath->query('//button[@id="ai-wait-toggle" and @role="switch" and @aria-checked="false"]')->length === 1, 'Waiting is an accessible off switch in ' . $locale);
	check($xpath->query('//*[@id="ai-wait-label"]')->length === 1 && $xpath->query('//*[@id="ai-wait-note"]')->length === 1, 'Switch has a stable name and description in ' . $locale);
	check($xpath->query('//input[@name="notify" and @type="checkbox" and @role="switch" and @checked]')->length === 1, 'Notification switch retains native checked form state in ' . $locale);
	check($xpath->query('//input[@name="public_consent" and @type="checkbox" and not(@role)]')->length === 1, 'Consent retains its explicit checkbox semantics in ' . $locale);
	check($xpath->query('//button[@data-copy-target="ai-setup"]/*[local-name()="svg"]')->length === 1, 'Copy action includes its decorative library icon in ' . $locale);
	foreach ($xpath->query('//*[local-name()="use"]') as $use) {
		$id = substr($use->getAttribute('href'), 1);
		if ($xpath->query('//*[@id="' . $id . '"]')->length !== 1) throw new RuntimeException('Missing or duplicated icon ' . $id);
	}
	check(true, 'Every rendered icon resolves locally exactly once in ' . $locale);
	check($xpath->query('//summary[contains(@class,"disclosure-control")]/*[local-name()="svg"]')->length > 3, 'Disclosures have visible consistent indicators in ' . $locale);
}
AiWaiting::update($a['actor'], ['debate_id' => $debate['id'], 'enabled' => true]);
$html = Views::page('/debate/' . $debate['id'], $user, $a['actor']);
check(str_contains($html, 'role="switch" aria-checked="true"'), 'Reload restores the saved waiting state');
check(str_contains(Views::check('notify', 'notify', false), 'role="switch"') && !str_contains(Views::check('notify', 'notify', false), ' checked'), 'Explicit notification off state renders unchecked');
echo 'SUCCESS: ' . $checks . " rendered control checks.\n";

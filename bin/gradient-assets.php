<?php
declare(strict_types=1);
// Native brand graphics with a bounded palette: no dark pools or pale highlights.
if (PHP_SAPI !== 'cli' || !extension_loaded('gd')) exit(1);
$width = 1440; $height = 720;
$palettes = [
	'yellow' => [[255, 219, 36], [255, 184, 28], [255, 229, 44]],
	'red' => [[255, 147, 54], [255, 127, 48], [255, 181, 48]],
];
function luminance(array $rgb): float {
	$linear = array_map(static function ($c) { $v = $c / 255; return $v <= .04045 ? $v / 12.92 : (($v + .055) / 1.055) ** 2.4; }, $rgb);
	return .2126 * $linear[0] + .7152 * $linear[1] + .0722 * $linear[2];
}
foreach ($palettes as $name => [$base, $warm, $bright]) {
	$image = imagecreatetruecolor($width, $height);
	mt_srand(2112026);
	$minimum = 21.0;
	for ($y = 0; $y < $height; $y++) for ($x = 0; $x < $width; $x++) {
		$u = $x / $width; $v = $y / $height;
		// Broad curved transitions keep the type area calm at every crop/viewport.
		$warm_weight = exp(-((($u - .93) / .60) ** 2 + (($v - .42 - .18 * sin($u * 4)) / .80) ** 2));
		$bright_weight = .70 * exp(-((($u - .15) / .65) ** 2 + (($v - .15) / .75) ** 2));
		$grain = mt_rand(-4, 4);
		$rgb = [];
		foreach ($base as $i => $channel) {
			$value = $channel * (1 - $warm_weight) + $warm[$i] * $warm_weight;
			$value = $value * (1 - $bright_weight) + $bright[$i] * $bright_weight;
			$rgb[] = max(0, min(255, (int) round($value) + $grain));
		}
		$minimum = min($minimum, (luminance($rgb) + .05) / (luminance([25, 25, 24]) + .05));
		imagesetpixel($image, $x, $y, ($rgb[0] << 16) | ($rgb[1] << 8) | $rgb[2]);
	}
	if ($minimum < 6.5) throw new RuntimeException('Palette fails the intended 6.5:1 contrast floor');
	$target = dirname(__DIR__) . '/public_html/assets/gradient-' . ($name === 'yellow' ? 'yellow-v2' : $name) . '.webp';
	if (!imagewebp($image, $target, 88)) throw new RuntimeException('Cannot export gradient');
	// Verify the delivered compressed image, not just the source palette.
	$decoded = imagecreatefromwebp($target);
	$minimum = 21.0;
	for ($y = 0; $y < $height; $y++) for ($x = 0; $x < $width; $x++) {
		$c = imagecolorat($decoded, $x, $y);
		$minimum = min($minimum, (luminance([($c >> 16) & 255, ($c >> 8) & 255, $c & 255]) + .05) / (luminance([25, 25, 24]) + .05));
	}
	if ($minimum < 6.5) throw new RuntimeException('Compressed gradient contrast failed');
	echo $name . ': minimum text contrast ' . round($minimum, 2) . ':1 across every exported pixel; ' . filesize($target) . " bytes\n";
}

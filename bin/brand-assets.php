<?php
declare(strict_types=1);
// Reproducible code-drawn brand artwork; no stock images or runtime font requests.
if (PHP_SAPI !== 'cli') exit(1);
$font = $argv[1] ?? 'C:/Windows/Fonts/arialbd.ttf';
if (!is_file($font) || !extension_loaded('gd')) throw new RuntimeException('GD and a local bold sans-serif font are required');
$directory = dirname(__DIR__) . '/public_html/assets';
function canvas(int $width, int $height): GdImage {
	$image = imagecreatetruecolor($width, $height);
	mt_srand(112026);
	for ($y = 0; $y < $height; $y++) for ($x = 0; $x < $width; $x++) {
		$u = $x / $width; $v = $y / $height;
		$rgb = [255.0, 220.0, 57.0];
		$layers = [
			[.08, .95, .8, .7, [255, 207, 22], .9],
			[1.1, .95, .7, .9, [255, 112, 100], .95],
			[.84, .03, .75, .7, [255, 249, 200], 1],
		];
		foreach ($layers as [$cx, $cy, $rx, $ry, $tone, $strength]) {
			$distance = hypot(($u - $cx) / $rx, ($v - $cy) / $ry);
			$weight = max(0, 1 - $distance); $weight = $weight * $weight * (3 - 2 * $weight) * $strength;
			foreach ($rgb as $channel => $value) $rgb[$channel] = $value * (1 - $weight) + $tone[$channel] * $weight;
		}
		$grain = mt_rand(-2, 2);
		$color = imagecolorallocate($image, ...array_map(static fn ($value) => max(0, min(255, (int) round($value) + $grain)), $rgb));
		imagesetpixel($image, $x, $y, $color);
	}
	return $image;
}
$image = canvas(1200, 630);
$ink = imagecolorallocate($image, 25, 25, 24);
imagettftext($image, 27, 0, 62, 76, $ink, $font, 'dilemma');
imagettftext($image, 82, 0, 54, 245, $ink, $font, 'What would');
imagettftext($image, 82, 0, 54, 360, $ink, $font, 'change your');
imagettftext($image, 82, 0, 54, 475, $ink, $font, 'mind?');
imagettftext($image, 16, 0, 62, 581, $ink, $font, 'dilemma.se');
if (!imagepng($image, $directory . '/share-opinion.png', 9)) throw new RuntimeException('Cannot write share image');
foreach (['icon-192.png' => 192, 'icon-512.png' => 512, 'icon-maskable.png' => 512] as $name => $size) {
	$image = canvas($size, $size);
	$ink = imagecolorallocate($image, 25, 25, 24);
	$font_size = $size * .54;
	$box = imagettfbbox($font_size, 0, $font, 'd');
	$x = (int) (($size - ($box[2] - $box[0])) / 2 - $box[0]);
	$y = (int) (($size - ($box[7] - $box[1])) / 2 - $box[1]);
	imagettftext($image, $font_size, 0, $x, $y, $ink, $font, 'd');
	imagetruecolortopalette($image, false, 64);
	if (!imagepng($image, $directory . '/' . $name, 9)) throw new RuntimeException('Cannot write app icon');
}
$image = imagecreatetruecolor(96, 96);
imagealphablending($image, false); imagesavealpha($image, true);
imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
imagettftext($image, 60, 0, 19, 76, imagecolorallocatealpha($image, 255, 255, 255, 0), $font, 'd');
if (!imagepng($image, $directory . '/badge.png', 9)) throw new RuntimeException('Cannot write notification badge');
echo "Generated sharing artwork, app icons and notification badge.\n";

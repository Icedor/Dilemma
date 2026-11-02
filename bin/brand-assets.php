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
		$glow = max(0, 1 - hypot(($x / $width - .82) * 1.1, ($y / $height - .35) * .85));
		$grain = mt_rand(-3, 3);
		$color = imagecolorallocate($image, min(255, 248 + (int) ($glow * 7) + $grain), max(0, min(255, 190 + (int) ($glow * 41) + $grain)), max(0, min(255, 38 + (int) ($glow * 48) + $grain)));
		imagesetpixel($image, $x, $y, $color);
	}
	return $image;
}
$image = canvas(1200, 630);
$ink = imagecolorallocate($image, 25, 25, 24);
imagettftext($image, 20, 0, 65, 68, $ink, $font, 'DILEMMA.SE');
imagettftext($image, 38, 0, 65, 167, $ink, $font, 'Your AI argues.');
imagettftext($image, 38, 0, 65, 224, $ink, $font, 'You decide.');
imagettftext($image, 167, 0, 53, 560, $ink, $font, 'dilemma');
if (!imagepng($image, $directory . '/share.png', 9)) throw new RuntimeException('Cannot write share image');
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

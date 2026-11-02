<?php
declare(strict_types=1);
// Brand typography over the controlled bright gradient asset.
if (PHP_SAPI !== 'cli') exit(1);
$font = $argv[1] ?? 'C:/Windows/Fonts/arialbd.ttf';
if (!is_file($font) || !extension_loaded('gd')) throw new RuntimeException('GD and a local bold sans-serif font are required');
$directory = dirname(__DIR__) . '/public_html/assets';
function canvas(int $width, int $height): GdImage {
	$image = imagecreatetruecolor($width, $height);
	$texture = imagecreatefromwebp(dirname(__DIR__) . '/public_html/assets/gradient-yellow-v2.webp');
	if (!$texture) throw new RuntimeException('Missing yellow texture');
	imagecopyresampled($image, $texture, 0, 0, 0, 0, $width, $height, imagesx($texture), imagesy($texture));
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

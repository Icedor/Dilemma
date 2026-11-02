<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
if (count($argv) !== 3) throw new RuntimeException('Pass the generated yellow and red PNG paths');
foreach (['yellow' => $argv[1], 'red' => $argv[2]] as $name => $path) {
	$source = imagecreatefrompng($path);
	if (!$source) throw new RuntimeException('Cannot read generated texture');
	$image = imagecreatetruecolor(1440, 720);
	imagecopyresampled($image, $source, 0, 0, 0, 0, 1440, 720, imagesx($source), imagesy($source));
	$destination = dirname(__DIR__) . '/public_html/assets/gradient-' . $name . '.webp';
	if (!imagewebp($image, $destination, 82)) throw new RuntimeException('Cannot export texture');
	echo $name . ': 1440x720, ' . filesize($destination) . ' bytes' . PHP_EOL;
}

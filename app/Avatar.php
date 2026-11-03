<?php
declare(strict_types=1);
namespace Dilemma;

final class Avatar
{
	public static function normalize(mixed $value): string
	{
		if (!is_string($value) || strlen($value) > 512) throw new Failure(422, 'invalid_field', ['field' => 'avatar']);
		if (in_array($value, ['', 'circle', 'leaf', 'spark', 'wave'], true)) return $value;
		$points = json_decode($value, true);
		if (!is_array($points) || !array_is_list($points) || count($points) < 1 || count($points) > 3) throw new Failure(422, 'invalid_field', ['field' => 'avatar']);
		$clean = [];
		foreach ($points as $point) {
			if (!is_array($point) || array_diff(array_keys($point), ['color', 'x', 'y']) || !is_string($point['color'] ?? null) || !preg_match('/^#[a-f0-9]{6}$/iD', $point['color']) || !is_int($point['x'] ?? null) || !is_int($point['y'] ?? null) || $point['x'] < 0 || $point['x'] > 100 || $point['y'] < 0 || $point['y'] > 100) throw new Failure(422, 'invalid_field', ['field' => 'avatar']);
			$clean[] = ['color' => strtolower($point['color']), 'x' => $point['x'], 'y' => $point['y']];
		}
		return Support::json($clean);
	}
	public static function points(string $value): array
	{
		try { $normalized = self::normalize($value); } catch (Failure) { $normalized = ''; }
		return str_starts_with($normalized, '[') ? json_decode($normalized, true) : [['color' => '#ffcf24', 'x' => 50, 'y' => 50]];
	}
	public static function svg(string $value): string
	{
		$points = self::points($value); $id = 'avatar-' . bin2hex(random_bytes(6));
		$defs = ''; $layers = '<rect width="100" height="100" fill="' . $points[0]['color'] . '"/>';
		foreach (array_slice($points, 1) as $i => $point) {
			$key = $id . '-' . $i;
			$defs .= '<radialGradient id="' . $key . '" cx="' . $point['x'] . '%" cy="' . $point['y'] . '%" r="85%"><stop offset="0" stop-color="' . $point['color'] . '"/><stop offset="1" stop-color="' . $point['color'] . '" stop-opacity="0"/></radialGradient>';
			$layers .= '<rect width="100" height="100" fill="url(#' . $key . ')"/>';
		}
		return '<svg class="avatar gradient-avatar" viewBox="0 0 100 100" aria-hidden="true"><defs>' . $defs . '</defs>' . $layers . '</svg>';
	}
}

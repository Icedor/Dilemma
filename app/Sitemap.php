<?php

declare(strict_types=1);

namespace Dilemma;

final class Sitemap
{
	public static function render(bool $index, int $page = 1): string
	{
		$e = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		if (!Config::production()) return $xml . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>';
		if ($index) {
			$count = (int) Db::one('SELECT COUNT(*) AS n FROM debates d WHERE ' . Debates::PUBLIC_SQL)['n'];
			$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			for ($number = 1; $number <= max(1, (int) ceil($count / 1000)); $number++) $xml .= '<sitemap><loc>' . $e(Config::url('/sitemap-pages.xml?page=' . $number)) . '</loc></sitemap>';
			return $xml . '</sitemapindex>';
		}
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
		$paths = $page === 1 ? ['/', '/explore', '/about', '/how'] : [];
		foreach (Db::all('SELECT d.id FROM debates d WHERE ' . Debates::PUBLIC_SQL . ' ORDER BY d.id LIMIT 1000 OFFSET ' . (($page - 1) * 1000)) as $row) $paths[] = '/debate/' . $row['id'];
		foreach ($paths as $path) {
			$sv = $e(Config::url($path)); $en = $e(Config::url($path) . '?lang=en');
			foreach ([$sv, $en] as $url) $xml .= '<url><loc>' . $url . '</loc><xhtml:link rel="alternate" hreflang="sv" href="' . $sv . '"/><xhtml:link rel="alternate" hreflang="en" href="' . $en . '"/></url>';
		}
		return $xml . '</urlset>';
	}
}

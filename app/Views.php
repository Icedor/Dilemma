<?php

declare(strict_types=1);

namespace Dilemma;

final class Views
{
	public static function page(string $path, ?array $user, ?array $actor): string
	{
		$template = match ($path) { '/' => 'home', '/explore' => 'archive', '/app' => 'dashboard', '/new' => 'new', '/login' => 'login', '/verify' => 'verify', '/invite' => 'invite', '/settings' => 'settings', '/connect' => 'connect', '/admin' => 'admin', '/oauth/authorize' => 'authorize', '/about', '/how', '/privacy', '/terms' => 'information', default => '' };
		$data = [];
		if (preg_match('~^/debate/([a-f0-9-]{36})$~D', $path, $match)) { $template = 'debate'; $data['debate'] = ReadModel::debate($match[1], $actor, Support::integer($_GET['page'] ?? 1, 1, 100000, 'page'), Support::integer($_GET['positions_page'] ?? 1, 1, 100000, 'positions_page')); }
		if (preg_match('~^/profile/([a-f0-9-]{36})$~D', $path, $match)) { $template = 'profile'; $data['profile'] = Db::one("SELECT id, alias, bio, avatar FROM users WHERE id = ? AND status = 'active' AND verified_at IS NOT NULL", [$match[1]]); if (!$data['profile']) throw new Failure(404, 'not_found'); }
		if ($template === '') throw new Failure(404, 'not_found');
		if ($template === 'authorize') $data['authorization'] = OAuth::request($_GET);
		if (in_array($template, ['dashboard', 'new', 'settings', 'connect', 'admin', 'authorize'], true)) Auth::require_user($user);
		if ($template === 'admin') Moderation::administrator($actor);
		$title = match ($template) { 'home' => I18n::t('seo_home_title'), 'archive' => I18n::locale() === 'en' ? 'Public debates — follow the arguments' : 'Offentliga debatter — följ argumenten', 'dashboard' => I18n::t('mine'), 'new' => I18n::t('new_debate'), 'debate' => $data['debate']['title'], 'profile' => $data['profile']['alias'], 'information' => I18n::t(ltrim($path, '/')), default => I18n::t($template) };
		$content = self::render($template, $data + ['path' => $path, 'user' => $user, 'actor' => $actor]);
		$indexable = in_array($template, ['home', 'archive', 'profile', 'information'], true) || ($template === 'debate' && $data['debate']['is_public']);
		$description = match (true) {
			$template === 'debate' => mb_substr($data['debate']['thesis'], 0, 160),
			$template === 'profile' => mb_substr($data['profile']['bio'] ?: $data['profile']['alias'] . (I18n::locale() === 'en' ? ' on Dilemma. Explore their public debates and arguments.' : ' på Dilemma. Läs deltagarens offentliga debatter och argument.'), 0, 160),
			in_array($path, ['/explore', '/how', '/about'], true) => I18n::t('seo_' . ltrim($path, '/')),
			$path === '/privacy' => I18n::locale() === 'en' ? 'How Dilemma handles your account, debate contributions, privacy choices and optional notifications. Your data, your choices.' : 'Så hanterar Dilemma ditt konto, dina debattbidrag, integritetsval och valfria notiser.',
			$path === '/terms' => I18n::locale() === 'en' ? 'The terms for taking part in Dilemma: responsible debate, publication choices, AI connections and moderation.' : 'Villkor för att delta i Dilemma: ansvar för debattinlägg, publiceringsval, AI-anslutningar och moderering.',
			default => I18n::t('seo_home'),
		};
		return self::layout($title, $content, $user, $path, $indexable, $description);
	}

	public static function render(string $template, array $data): string
	{
		extract($data, EXTR_SKIP);
		$e = Support::escape(...);
		$t = static fn (string $key): string => Support::escape(I18n::t($key));
		ob_start();
		try { require __DIR__ . '/views/' . $template . '.php'; return ob_get_clean(); }
		catch (\Throwable $error) { ob_end_clean(); throw $error; }
	}

	public static function layout(string $title, string $content, ?array $user, string $path = '/', bool $indexable = false, string $description = ''): string
	{
		return self::render('layout', compact('title', 'content', 'user', 'path', 'indexable', 'description'));
	}

	public static function error(int $status, string $key, ?array $user): string
	{
		$return = $_SERVER['REQUEST_URI'] ?? '';
		$attribute = $status === 401 && str_starts_with($return, '/oauth/authorize?') ? ' data-auth-return="' . Support::escape($return) . '"' : '';
		return self::layout((string) $status, '<section class="reading"><h1>' . $status . '</h1><p>' . Support::escape(I18n::t($key)) . '</p><a class="button"' . $attribute . ' href="' . ($status === 401 ? '/login' : '/') . '">' . Support::escape(I18n::t($status === 401 ? 'login' : 'explore')) . '</a></section>', $user);
	}

	public static function form(string $endpoint, string $result = 'reload', string $extra = ''): string
	{
		return '<form method="post" action="' . Support::escape($endpoint) . '" data-api data-result="' . Support::escape($result) . '" ' . $extra . '>' . self::hidden('idempotency_key', Support::token());
	}

	public static function hidden(string $name, mixed $value): string { return '<input type="hidden" name="' . Support::escape($name) . '" value="' . Support::escape($value) . '">'; }

	public static function field(string $name, string $label, string $value = '', string $type = 'text', bool $required = false, int $max = 255): string
	{
		return '<label class="field"><span>' . Support::escape(I18n::t($label)) . '</span><input type="' . Support::escape($type) . '" name="' . Support::escape($name) . '" value="' . Support::escape($value) . '" maxlength="' . $max . '"' . ($required ? ' required' : '') . ($type === 'email' ? ' autocomplete="email"' : '') . '></label>';
	}

	public static function area(string $name, string $label, string $value = '', bool $required = false, int $max = 4000): string
	{
		return '<label class="field"><span>' . Support::escape(I18n::t($label)) . '</span><textarea name="' . Support::escape($name) . '" rows="4" maxlength="' . $max . '"' . ($required ? ' required' : '') . '>' . Support::escape($value) . '</textarea></label>';
	}

	public static function check(string $name, string $label, bool $checked = false, bool $required = false): string
	{
		return '<label class="check"><input type="checkbox" name="' . Support::escape($name) . '"' . ($checked ? ' checked' : '') . ($required ? ' required' : '') . '><span>' . Support::escape(I18n::t($label)) . '</span></label>';
	}

	public static function submit(string $label, string $class = ''): string
	{
		return '<p class="form-feedback" role="status" aria-live="polite"></p><button class="button ' . Support::escape($class) . '" type="submit">' . Support::escape(I18n::t($label)) . '</button>';
	}

	public static function scale(string $left = '', string $right = ''): string
	{
		return '<fieldset class="position-input"><legend>' . Support::escape(I18n::t('position')) . '</legend>' . self::check('position_enabled', 'position_enable') . '<div class="scale-labels"><span>' . Support::escape($left) . '</span><span>' . Support::escape($right) . '</span></div><div class="range-row"><input type="range" name="position" min="0" max="100" value="50" disabled aria-label="' . Support::escape(I18n::t('position')) . '"><output>' . Support::escape(I18n::t('not_given')) . '</output></div>' . self::field('position_note', 'position_note', '', 'text', false, 500) . '</fieldset>';
	}

	public static function consent(?array $participant = null): string
	{
		return '<fieldset><legend>' . Support::escape(I18n::t('public')) . '</legend><p class="muted">' . Support::escape(I18n::t('public_rule')) . ' ' . Support::escape(I18n::t('consent_help')) . '</p>' . ($participant === null ? self::check('processing_consent', 'processing_consent', false, true) : '') . self::check('public_consent', 'public_consent', (bool) ($participant['public_consent'] ?? false)) . self::check('position_consent', 'position_consent', (bool) ($participant['position_consent'] ?? false)) . '</fieldset>';
	}

	public static function debate_list(array $list, string $empty = 'empty_archive'): string
	{
		return self::render('debate-list', compact('list', 'empty'));
	}

	public static function date(string $value): string
	{
		return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Europe/Stockholm'))->format('Y-m-d H:i');
	}

	public static function pagination(int $page, int $pages, string $key = 'page'): string
	{
		if ($pages <= 1) return '';
		$html = '<nav class="pagination" aria-label="' . Support::escape(I18n::t('page')) . '">';
		foreach ([$page - 1 => 'previous', $page + 1 => 'next'] as $target => $label) if ($target >= 1 && $target <= $pages) $html .= '<a class="button secondary" href="?' . Support::escape(http_build_query(array_replace($_GET, [$key => $target]))) . ($key === 'positions_page' ? '#positions' : '') . '">' . Support::escape(I18n::t($label)) . '</a>';
		return $html . '<span>' . $page . ' / ' . $pages . '</span></nav>';
	}

	public static function avatar(string $name): string
	{
		$shape = match ($name) {
			'circle' => '<circle cx="24" cy="24" r="17"/><circle cx="29" cy="18" r="5" class="avatar-cutout"/>',
			'leaf' => '<path d="M8 40C3 12 20 7 40 8C42 31 27 46 8 40Z"/><path d="M12 36L33 15" class="avatar-line"/>',
			'spark' => '<path d="M24 3L29 17L43 12L35 25L45 35L30 34L24 46L18 34L3 35L13 25L5 12L19 17Z"/>',
			'wave' => '<path d="M5 18Q14 3 24 18T43 18V33Q33 48 24 33T5 33Z"/>',
			default => '',
		};
		return $shape === '' ? '' : '<svg class="avatar" viewBox="0 0 48 48" aria-hidden="true">' . $shape . '</svg>';
	}
}

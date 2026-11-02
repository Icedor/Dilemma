<?php
use Dilemma\I18n;
use Dilemma\Support;
use Dilemma\Config;
$language = I18n::locale();
$query = $_GET;
unset($query['lang']);
$index_query = $indexable ? array_intersect_key($query, array_flip(['page', 'positions_page', 'q', 'topic', 'status', 'outcome', 'sort'])) : [];
$localized_url = static fn (string $lang): string => Config::url($path) . (($values = $index_query + ($lang === 'en' ? ['lang' => 'en'] : [])) ? '?' . http_build_query($values) : '');
$canonical = $localized_url($language);
?>
<!doctype html>
<html lang="<?= $language ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light dark">
	<meta name="description" content="<?= $e($description ?: I18n::t('home_intro')) ?>">
	<meta name="csrf-token" content="<?= $e($user['csrf'] ?? '') ?>">
	<meta name="robots" content="<?= $indexable && Config::production() ? 'index,follow' : 'noindex,nofollow' ?>">
	<title><?= $e($title) ?> · Dilemma.se</title>
	<link rel="canonical" href="<?= $e($canonical) ?>">
	<link rel="alternate" hreflang="sv" href="<?= $e($localized_url('sv')) ?>">
	<link rel="alternate" hreflang="en" href="<?= $e($localized_url('en')) ?>">
	<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
	<link rel="stylesheet" href="/assets/style.css">
	<script type="module" src="/assets/app.js"></script>
</head>
<body data-user="<?= $e($user['id'] ?? '') ?>">
	<a href="#main" class="skip-link"><?= $t('skip') ?></a>
	<header class="site-header">
		<a class="wordmark" href="/" aria-label="Dilemma.se">dilemma<span class="wordmark-dot">.</span></a>
		<nav class="main-nav" aria-label="Dilemma">
			<a href="/explore"><?= $t('explore') ?></a><a href="/how"><?= $t('how') ?></a>
			<?php if ($user): ?><a href="/app"><?= $t('mine') ?></a><?php endif; ?>
		</nav>
		<div class="header-actions">
			<nav class="language-switch" aria-label="<?= $t('language') ?>"><a href="?<?= $e(http_build_query($query + ['lang' => 'sv'])) ?>" lang="sv" <?= $language === 'sv' ? 'aria-current="true"' : '' ?>>SV</a><span aria-hidden="true">/</span><a href="?<?= $e(http_build_query($query + ['lang' => 'en'])) ?>" lang="en" <?= $language === 'en' ? 'aria-current="true"' : '' ?>>EN</a></nav>
			<?php if ($user): ?><details class="account-menu"><summary><?= $e($user['alias']) ?></summary><nav><a href="/app"><?= $t('mine') ?></a><a href="/connect"><?= $t('connect') ?></a><a href="/settings"><?= $t('settings') ?></a><?php if ($user['is_admin']): ?><a href="/admin"><?= $t('admin') ?></a><?php endif; ?><button type="button" data-logout><?= $t('logout') ?></button></nav></details><?php else: ?><a class="button small" href="/login"><?= $t('login') ?></a><?php endif; ?>
		</div>
	</header>
	<noscript><p class="notice"><?= $t('no_script') ?></p></noscript>
	<?php if (!Config::production()): ?><p class="notice"><?= $t('local_preview') ?></p><?php endif; ?>
	<?php if (!Config::participation_open()): ?><p class="notice"><?= $t('opening_soon') ?></p><?php endif; ?>
	<main id="main"><?= $content ?></main>
	<footer class="site-footer"><a class="wordmark" href="/">dilemma.</a><nav><a href="/about"><?= $t('about') ?></a><a href="/privacy"><?= $t('privacy') ?></a><a href="/terms"><?= $t('terms') ?></a></nav></footer>
	<div id="toast" class="toast" role="status" aria-live="polite"></div>
	<template id="translations"><?= $e(Support::json(I18n::all())) ?></template>
</body>
</html>

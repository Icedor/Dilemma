<?php
use Dilemma\I18n;
use Dilemma\Support;
use Dilemma\Config;
$language = I18n::locale();
$query = $_GET;
unset($query['lang']);
$index_query = $indexable ? array_intersect_key($query, array_flip(['page', 'positions_page', 'q', 'topic', 'status', 'outcome', 'sort'])) : [];
$localized_url = static fn (string $lang): string => Config::url($path) . '?' . http_build_query($index_query + ['lang' => $lang]);
$canonical = $localized_url($language);
?>
<!doctype html>
<html lang="<?= $language ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light dark">
	<meta name="theme-color" content="#ffda3d">
	<meta name="application-name" content="Dilemma">
	<meta name="description" content="<?= $e($description ?: I18n::t('home_intro')) ?>">
	<meta name="csrf-token" content="<?= $e($user['csrf'] ?? '') ?>">
	<meta name="robots" content="<?= $indexable && Config::production() ? 'index,follow' : 'noindex,nofollow' ?>">
	<title><?= $e($title) ?> · Dilemma.se</title>
	<link rel="canonical" href="<?= $e($canonical) ?>">
	<link rel="alternate" hreflang="sv" href="<?= $e($localized_url('sv')) ?>">
	<link rel="alternate" hreflang="en" href="<?= $e($localized_url('en')) ?>">
	<link rel="alternate" hreflang="x-default" href="<?= $e(Config::url($path)) ?>">
	<meta property="og:type" content="website">
	<meta property="og:site_name" content="Dilemma">
	<meta property="og:title" content="<?= $e($indexable ? $title : I18n::t('seo_home_title')) ?>">
	<meta property="og:description" content="<?= $e($indexable ? $description : I18n::t('seo_home')) ?>">
	<meta property="og:url" content="<?= $e($indexable ? $canonical : Config::url()) ?>">
	<meta property="og:locale" content="<?= $language === 'en' ? 'en_GB' : 'sv_SE' ?>">
	<meta property="og:image" content="<?= $e(Config::url('/assets/share-opinion.png?v=20261102-9')) ?>">
	<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
	<meta property="og:image:alt" content="Dilemma. What would change your mind?">
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:image" content="<?= $e(Config::url('/assets/share-opinion.png?v=20261102-9')) ?>">
	<link rel="manifest" href="/manifest.webmanifest?v=20261102-9">
	<link rel="apple-touch-icon" href="/assets/icon-192.png?v=20261102-9">
	<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
	<script src="/assets/language.js?v=20261102-9"></script>
	<link rel="stylesheet" href="/assets/style.css?v=20261102-9">
	<script type="module" src="/assets/app.js?v=20261102-9"></script>
</head>
<body class="<?= $path === '/' ? 'home-page' : 'inner-page' ?>" data-user="<?= $e($user['id'] ?? '') ?>">
	<a href="#main" class="skip-link"><?= $t('skip') ?></a>
	<div class="site-shell">
	<header class="site-header">
		<a class="wordmark" href="/" aria-label="Dilemma.se">dilemma</a>
		<nav class="main-nav" aria-label="Dilemma">
			<a href="/explore"><?= $t('explore') ?></a><a href="/how"><?= $t('how') ?></a>
			<?php if ($user): ?><a href="/app"><?= $t('mine') ?></a><?php endif; ?>
		</nav>
		<div class="header-actions">
			<nav class="language-switch" aria-label="<?= $t('language') ?>"><a href="?<?= $e(http_build_query($query + ['lang' => 'sv'])) ?>" lang="sv" <?= $language === 'sv' ? 'aria-current="true"' : '' ?>>SV</a><span aria-hidden="true">/</span><a href="?<?= $e(http_build_query($query + ['lang' => 'en'])) ?>" lang="en" <?= $language === 'en' ? 'aria-current="true"' : '' ?>>EN</a></nav>
			<?php if ($user): ?><details class="account-menu"><summary><?= $e($user['alias']) ?></summary><nav><a href="/app"><?= $t('mine') ?></a><a href="/settings"><?= $t('settings') ?></a><?php if ($user['is_admin']): ?><a href="/admin"><?= $t('admin') ?></a><?php endif; ?><button type="button" data-logout><?= $t('logout') ?></button></nav></details><?php else: ?><a class="button small" href="/login"><?= $t('login') ?></a><?php endif; ?>
		</div>
	</header>
	<noscript><p class="notice"><?= $t('no_script') ?></p></noscript>
	<?php if (!Config::production()): ?><p class="notice preview-notice"><?= $t('local_preview') ?></p><?php endif; ?>
	<main id="main"><?= $content ?></main>
	</div>
	<footer class="site-footer"><a class="wordmark" href="/">dilemma</a><nav><a href="/about"><?= $t('about') ?></a><a href="/privacy" target="_blank" rel="noopener noreferrer"><?= $t('privacy') ?><span class="visually-hidden"> — <?= $t('opens_new_tab') ?></span></a><a href="/terms" target="_blank" rel="noopener noreferrer"><?= $t('terms') ?><span class="visually-hidden"> — <?= $t('opens_new_tab') ?></span></a></nav></footer>
	<div id="toast" class="toast" role="status" aria-live="polite"></div>
	<template id="translations"><?= $e(Support::json(I18n::all())) ?></template>
</body>
</html>

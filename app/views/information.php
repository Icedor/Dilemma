<?php use Dilemma\Content; use Dilemma\Config; $name = ltrim($path, '/'); ?>
<?php if (in_array($name, ['how', 'about'], true)): ?>
<article class="information-page <?= $name === 'about' ? 'about-page' : '' ?>"><header><h1><?= $t($name) ?></h1><p><?= $t($name === 'about' ? 'home_purpose' : 'home_pitch') ?></p></header>
<ol class="explanation-list"><?php foreach (Content::sections($name) as $index => $section): ?><li><span class="step-number" aria-hidden="true"><?= $index + 1 ?></span><h2><?= $e($section['title']) ?></h2><p><?= $e($section['text']) ?></p></li><?php endforeach; ?></ol>
<div class="actions"><a class="button" href="<?= $user ? '/new' : '/login?register=1' ?>"><?= $t('get_started') ?></a><a class="text-link" href="/explore"><?= $t('explore') ?></a></div></article>
<?php else: ?>
<article class="reading"><h1><?= $t($name) ?></h1><?php if (Config::get('LEGAL_REVIEWED') !== 'true'): ?><p class="notice"><?= $t('legal_review') ?></p><?php endif; ?><?php if (Config::get('LEGAL_OWNER') !== ''): ?><p><?= $e(Config::get('LEGAL_OWNER')) ?></p><?php endif; ?><?php if (Config::get('PRIVACY_CONTACT') !== ''): ?><p><a href="mailto:<?= $e(Config::get('PRIVACY_CONTACT')) ?>"><?= $e(Config::get('PRIVACY_CONTACT')) ?></a></p><?php endif; ?>
<?php foreach (Content::sections($name) as $section): ?><section><h2><?= $e($section['title']) ?></h2><p><?= $e($section['text']) ?></p></section><?php endforeach; ?></article>
<?php endif; ?>

<?php use Dilemma\Views; use Dilemma\I18n; $register = ($_GET['register'] ?? '') === '1'; ?>
<section class="auth-layout">
<div class="auth-intro"><p class="auth-question"><?= $t('home_pitch') ?></p><span class="auth-wordmark" aria-hidden="true">dilemma</span></div>
<div class="auth-form">
<header><h1><?= $t($register ? 'register' : 'login') ?></h1><p><?= $t('auth_link_help') ?></p></header>
<?= Views::form('/api/login', 'login') ?><?= Views::hidden('locale', I18n::locale()) ?>
<?php if ($register): ?><?= Views::field('alias', 'alias', '', 'text', true, 60) ?><?php endif; ?>
<?= Views::field('email', 'email', '', 'email', true, 254) ?>
<?php if ($register): ?><p class="auth-privacy-hint"><?= $t('alias_help') ?></p>
<div class="auth-consent"><?= Views::check('accept_terms', 'accept_terms', false, true) ?>
<p class="auth-legal"><a href="/terms" target="_blank" rel="noopener noreferrer"><?= $t('terms') ?><span aria-hidden="true"> ↗</span><span class="visually-hidden"> — <?= $t('opens_new_tab') ?></span></a><a href="/privacy" target="_blank" rel="noopener noreferrer"><?= $t('privacy') ?><span aria-hidden="true"> ↗</span><span class="visually-hidden"> — <?= $t('opens_new_tab') ?></span></a></p></div><?php endif; ?>
<?= Views::submit('send_link') ?></form><p class="auth-switch"><?= $t($register ? 'already_member' : 'new_member') ?> <a href="/login<?= $register ? '' : '?register=1' ?>"><?= $t($register ? 'login' : 'register') ?></a></p>
</div></section>

<?php use Dilemma\Views; use Dilemma\I18n; use Dilemma\Config; $register = ($_GET['register'] ?? '') === '1'; ?>
<section class="auth-layout"><div class="auth-intro"><h1><?= $t($register ? 'register' : 'login') ?></h1><p><?= $t('login_intro') ?></p><img src="/assets/people.svg" width="440" height="340" alt=""></div><div class="auth-form">
<?php if (Config::participation_open()): ?>
<?= Views::form('/api/login', 'login') ?><?= Views::hidden('locale', I18n::locale()) ?><?= Views::field('email', 'email', '', 'email', true, 254) ?>
<?php if ($register): ?><?= Views::field('alias', 'alias', '', 'text', true, 60) ?><p class="muted"><?= $t('alias_help') ?></p><?= Views::check('accept_terms', 'accept_terms', false, true) ?><p><a href="/terms"><?= $t('terms') ?></a> · <a href="/privacy"><?= $t('privacy') ?></a></p><?php endif; ?>
<?= Views::submit('send_link') ?></form><p class="auth-switch"><a href="/login<?= $register ? '' : '?register=1' ?>"><?= $t($register ? 'login' : 'register') ?> ↗</a></p>
<?php else: ?><p><?= $t('opening_soon') ?></p><a class="button" href="/how"><?= $t('how') ?></a><?php endif; ?>
</div></section>

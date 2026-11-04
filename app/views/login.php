<?php use Dilemma\Views; use Dilemma\I18n; ?>
<section class="auth-layout">
<div class="auth-intro"><p class="auth-question"><?= $t('home_pitch') ?></p><span class="auth-wordmark" aria-hidden="true">dilemma</span></div>
<div class="auth-form">
<header><?php if (isset($oauth_return)): ?><p class="eyebrow"><?= $t('oauth_login_intro') ?></p><?php endif; ?><h1><?= $t('auth_heading') ?></h1><p><?= $t('auth_link_help') ?></p></header>
<?= Views::form('/api/login', 'login') ?><?= Views::hidden('locale', I18n::locale()) ?><?= isset($oauth_return) ? Views::hidden('return_to', $oauth_return) : '' ?>
<?= Views::field('email', 'email', '', 'email', true, 254) ?>
<p class="muted auth-help"><?= $t('auth_new_help') ?></p>
<?= Views::submit('send_link') ?></form>
<section class="auth-sent" data-auth-sent hidden aria-labelledby="auth-sent-title">
	<h2 id="auth-sent-title" tabindex="-1"><?= $t('auth_sent_title') ?></h2>
	<p><?= $t('check_mail') ?></p><strong data-auth-address></strong>
	<p class="muted"><?= $t('auth_delivery_help') ?></p>
	<button type="button" class="button secondary" data-auth-edit><?= $t('auth_edit_email') ?></button>
</section>
</div></section>

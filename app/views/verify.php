<?php use Dilemma\Views; ?>
<section class="auth-layout">
	<div class="auth-intro"><p class="auth-question"><?= $t('home_pitch') ?></p><span class="auth-wordmark" aria-hidden="true">dilemma</span></div>
	<div class="auth-form">
		<header><h1 data-auth-title><?= $t('verify') ?></h1><p data-auth-intro><?= $t('verify_intro') ?></p></header>
		<?= Views::form('/api/verify', 'verify', 'data-fragment="token"') ?><?= Views::hidden('token', '') ?>
			<fieldset class="auth-registration" data-registration hidden disabled>
				<legend class="visually-hidden"><?= $t('register') ?></legend>
				<?= Views::field('alias', 'alias', '', 'text', true, 60) ?>
				<div class="auth-consent"><?= Views::check('accept_terms', 'accept_terms', false, true) ?>
					<p class="auth-legal"><a href="/terms" target="_blank" rel="noopener noreferrer"><?= $t('terms') ?><span class="visually-hidden"> — <?= $t('opens_new_tab') ?></span></a><a href="/privacy" target="_blank" rel="noopener noreferrer"><?= $t('privacy') ?><span class="visually-hidden"> — <?= $t('opens_new_tab') ?></span></a></p>
				</div>
			</fieldset>
			<?= Views::submit('verify') ?>
		</form>
		<a class="text-button" href="/login"><?= $t('auth_new_link') ?></a>
	</div>
</section>

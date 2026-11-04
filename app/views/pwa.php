<section class="pwa-controls" aria-label="<?= $t('app_options') ?>">
	<div class="actions"><button type="button" class="button secondary small" data-install><?= \Dilemma\Views::icon('download') ?><span data-control-label><?= $t('install_app') ?></span></button><button type="button" class="button secondary small" data-push-enable><?= \Dilemma\Views::icon('settings-2') ?><span data-control-label><?= $t('push_enable') ?></span></button><button type="button" class="text-button" data-push-disable hidden><?= \Dilemma\Views::icon('rotate-ccw') ?><span data-control-label><?= $t('push_disable') ?></span></button></div>
	<p class="muted"><?= $t('push_help') ?></p><p class="form-feedback" data-pwa-status role="status"></p>
</section>

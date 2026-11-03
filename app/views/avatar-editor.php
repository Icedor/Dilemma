<?php use Dilemma\{Avatar, Support}; $points = Avatar::points($user['avatar']); ?>
<fieldset class="gradient-editor" data-avatar-editor>
	<legend><?= $t('avatar') ?></legend>
	<p class="muted"><?= $t('gradient_help') ?></p>
	<input type="hidden" name="avatar" value="<?= $e(Support::json($points)) ?>">
	<div class="gradient-workspace">
		<div class="gradient-preview">
			<svg viewBox="0 0 100 100" role="img" aria-label="<?= $t('avatar_preview') ?>" data-avatar-canvas><defs></defs><g data-avatar-colors></g></svg>
			<div class="gradient-handles" data-avatar-handles></div>
		</div>
		<div class="gradient-controls">
			<div data-avatar-swatches></div>
			<button type="button" class="button secondary small" data-avatar-add><?= $t('add_color') ?> <span aria-hidden="true">+</span></button>
			<p class="muted gradient-tip"><?= $t('gradient_keyboard') ?></p>
		</div>
	</div>
	<p class="visually-hidden" role="status" data-avatar-status></p>
</fieldset>

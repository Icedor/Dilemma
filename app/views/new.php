<?php use Dilemma\Views; use Dilemma\I18n; ?>
<section class="reading wide"><h1><?= $t('new_debate') ?></h1><p class="lead"><?= $t('new_intro') ?></p>
<?= Views::form('/api/debates', 'created', 'data-draft="new"') ?>
<?= Views::field('title', 'title', '', 'text', true, 160) ?><div class="form-columns"><?= Views::field('topic', 'topic', '', 'text', true, 60) ?><label class="field"><span><?= $t('language') ?></span><select name="locale"><option value="sv" <?= I18n::locale() === 'sv' ? 'selected' : '' ?>>Svenska</option><option value="en" <?= I18n::locale() === 'en' ? 'selected' : '' ?>>English</option></select></label></div>
<?= Views::area('thesis', 'thesis', '', true) ?><?= Views::area('stance', 'stance', '', true) ?><?= Views::area('introduction', 'introduction') ?>
<p class="muted"><?= $t('scale_help') ?></p><div class="form-columns"><?= Views::field('left_label', 'left_label', '', 'text', true, 140) ?><?= Views::field('right_label', 'right_label', '', 'text', true, 140) ?></div>
<?= Views::scale() ?><?= Views::consent() ?><?= Views::submit('create_draft') ?></form></section>

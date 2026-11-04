<?php use Dilemma\Views; use Dilemma\I18n; ?>
<section class="new-debate-layout"><header><h1><?= $t('get_started') ?></h1><p class="lead"><?= $t('new_intro') ?></p></header><div>
<?= Views::form('/api/debates', 'created', 'data-draft="new"') ?>
<?= Views::hidden('locale', I18n::locale()) ?>
<div class="primary-opinion"><?= Views::field('title', 'opinion', '', 'text', true, 160) ?></div>
<?= Views::area('stance', 'opinion_reason', '', true) ?>
<fieldset class="opponent-choices"><legend><?= $t('choose_opponent') ?></legend>
<label class="opponent-choice"><input type="radio" name="opponent_mode" value="invite" checked><span><strong><?= $t('friend_mode') ?></strong><span><?= $t('friend_mode_help') ?></span></span></label>
<label class="opponent-choice"><input type="radio" name="opponent_mode" value="open"><span><strong><?= $t('open_mode') ?></strong><span><?= $t('open_mode_help') ?></span></span></label>
</fieldset>
<details class="disclosure"><summary><?= $t('optional_details') ?></summary>
<?= Views::field('topic', 'topic', '', 'text', false, 60) ?><?= Views::area('introduction', 'introduction') ?>
<div class="form-columns"><?= Views::field('left_label', 'left_label', '', 'text', false, 140) ?><?= Views::field('right_label', 'right_label', '', 'text', false, 140) ?></div>
<?= Views::scale() ?></details>
<?= Views::consent() ?>
<?= Views::check('notify', 'notify_debate', true) ?><p class="muted"><?= $t('notify_debate_help') ?></p>
<?= Views::submit('create_draft') ?></form></div></section>

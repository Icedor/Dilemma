<?php use Dilemma\Views; ?>
<section class="reading"><h1><?= $t('verify') ?></h1><p><?= $t('verify_intro') ?></p><?= Views::form('/api/verify', 'verify', 'data-fragment="token"') ?><?= Views::hidden('token', '') ?><?= Views::submit('verify') ?></form></section>

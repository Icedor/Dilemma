<?php use Dilemma\Views; use Dilemma\ReadModel; ?>
<section class="section"><div class="page-heading"><?= Views::avatar($profile['avatar']) ?><h1><?= $e($profile['alias']) ?></h1><p class="user-text"><?= $e($profile['bio']) ?></p></div><h2><?= $t('archive') ?></h2><?= Views::debate_list(ReadModel::list(array_replace($_GET, ['profile' => $profile['id']]))) ?></section>

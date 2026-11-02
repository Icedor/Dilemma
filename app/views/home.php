<?php use Dilemma\ReadModel; use Dilemma\Views; ?>
<section class="hero" aria-labelledby="home-title">
	<div class="hero-intro"><p><?= $t('home_pitch') ?></p><div class="actions"><a class="button" href="<?= $user ? '/new' : '/login?register=1' ?>"><?= $t('get_started') ?></a><a class="text-link" href="/how"><?= $t('how') ?></a></div></div>
	<h1 class="hero-wordmark" id="home-title">dilemma</h1>
	<img class="hero-art" src="/assets/white-flag.webp" alt="" width="1122" height="1402" fetchpriority="high">
	<p class="hero-caption"><?= $t('flag_caption') ?></p>
</section>
<section class="project-story"><h2><?= $t('home_story_title') ?></h2><div class="story-copy"><p><?= $t('home_story') ?></p><p><?= $t('home_purpose') ?></p><a class="text-link" href="/about"><?= $t('about') ?></a></div></section>
<section class="process" aria-labelledby="process-title"><header class="process-header"><h2 id="process-title"><?= $t('how') ?></h2></header><ol class="process-list">
<?php foreach ([1, 2, 3] as $step): ?><li><span class="step-number" aria-hidden="true"><?= $step ?></span><h3><?= $t('step_' . $step . '_title') ?></h3><p><?= $t('step_' . $step) ?></p></li><?php endforeach; ?>
</ol></section>
<section class="section home-archive" aria-labelledby="archive-title"><div class="section-heading"><h2 id="archive-title"><?= $t('archive') ?></h2><a class="text-link" href="/explore"><?= $t('explore') ?></a></div><?= Views::debate_list(ReadModel::list([])) ?></section>

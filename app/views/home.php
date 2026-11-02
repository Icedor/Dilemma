<?php use Dilemma\ReadModel; use Dilemma\Views; ?>
<section class="hero">
	<div class="hero-copy"><h1>Dilemma<span>.</span></h1><p><?= $t('home_intro') ?></p><div class="actions"><a class="button" href="<?= $user ? '/new' : '/login?register=1' ?>"><?= $t('new_debate') ?><span aria-hidden="true">↗</span></a><a class="text-link" href="/how"><?= $t('how') ?></a></div></div>
	<img class="hero-art" src="/assets/people.svg" alt="" width="660" height="510">
</section>
<section class="section" aria-labelledby="archive-title"><div class="section-heading"><h2 id="archive-title"><?= $t('archive') ?></h2><a class="text-link" href="/explore"><?= $t('explore') ?> <span aria-hidden="true">↗</span></a></div><?= Views::debate_list(ReadModel::list([])) ?></section>

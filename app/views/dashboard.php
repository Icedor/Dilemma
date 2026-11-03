<?php use Dilemma\{Views, ReadModel}; $list = ReadModel::list($_GET, $actor); ?>
<section class="section dashboard">
	<div class="page-heading split"><div><h1><?= $t('mine') ?></h1><p><?= $t('overview_intro') ?></p></div><a class="button" href="/new"><?= $t('new_debate') ?> <span aria-hidden="true">+</span></a></div>
	<?php if (!$list['items']): ?><p class="empty-message"><?= $t('empty_mine') ?></p><?php endif; ?>
	<div class="my-debates">
	<?php foreach ($list['items'] as $item): $mine = null; $opponent = null; foreach ($item['participants'] as $person) { if ($person['user_id'] === $user['id']) $mine = $person; else if ($person['left_at'] === null) $opponent = $person; } ?>
		<article class="my-debate" data-my-debate="<?= $e($item['id']) ?>">
			<div class="debate-heading-meta"><span class="status"><?= $t($mine['left_at'] !== null ? 'flow_observe' : ($item['status'] === 'open' ? 'looking_for_opponent' : $item['status'])) ?></span><span><?= $e($item['topic']) ?></span></div>
			<h2><a href="/debate/<?= $e($item['id']) ?>"><?= $e($item['title']) ?></a></h2>
			<div class="my-participants"><?php foreach (array_filter($item['participants'], static fn ($p) => $p['left_at'] === null) as $person): ?><span><?= Views::avatar($person['avatar']) ?><?= $e($person['alias']) ?></span><?php endforeach; ?></div>
			<?php if ($mine['left_at'] === null && $item['status'] === 'active'): ?><p class="my-turn"><?= (int) $mine['side'] === (int) $item['turn_side'] ? $t('your_turn') : $t('waiting_turn') . ' ' . $e($opponent['alias'] ?? '') ?></p><?php endif; ?>
			<div class="actions"><a class="button secondary" href="/debate/<?= $e($item['id']) ?>"><?= $t('open_debate') ?></a><button type="button" class="text-button" data-overview-setup><?= $t('copy_setup') ?></button></div>
			<p class="form-feedback" role="status"></p>
			<label class="overview-setup" hidden><span><?= $t('copy_setup') ?></span><textarea readonly rows="6" autocomplete="off" spellcheck="false" data-private-setup></textarea></label>
		</article>
	<?php endforeach; ?>
	</div>
	<?= Views::pagination($list['page'], $list['pages']) ?>
</section>
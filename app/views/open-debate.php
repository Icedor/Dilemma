<?php use Dilemma\{Views, Config}; $id = $debate['id']; $member = null; $creator = array_values(array_filter($debate['participants'], static fn ($p) => $p['left_at'] === null))[0]; ?>
<article class="open-debate-page" data-debate="<?= $e($id) ?>">
<header class="page-heading"><span class="status status-open"><?= $t('looking_for_opponent') ?></span><h1><?= $e($debate['title']) ?></h1><p class="lead"><?= $t('open_join_intro') ?></p></header>
<div class="open-debate-columns">
<section class="open-position"><div class="participant-identity"><?= Views::avatar($creator['avatar']) ?><h2><?= $e($creator['alias']) ?></h2></div><p class="user-text"><?= $e($creator['stance']) ?></p><?php if ($debate['introduction'] !== ''): ?><p class="user-text"><?= $e($debate['introduction']) ?></p><?php endif; ?><?= Views::render('sharing', compact('member', 'debate', 'id')) ?></section>
<section class="join-panel" id="join"><h2><?= $t('claim_opponent') ?></h2><p><?= $t('claim_help') ?></p>
<?php if (!$user): ?><a class="button" href="/login?register=1" data-auth-return="/debate/<?= $e($id) ?>#join"><?= $t('claim_signin') ?></a><p class="muted"><?= $t('claim_signin_help') ?></p>
<?php else: ?>
<?= Views::form('/api/debates/' . $id . '/claim', 'accepted', 'data-draft="claim-' . $e($id) . '"') ?>
<?= Views::area('stance', 'your_counter_position', '', true) ?>
<?= Views::check('processing_consent', 'processing_consent', false, true) ?>
<?= Views::check('public_consent', 'public_consent', false, true) ?>
<?= Views::check('accept_terms', 'accept_terms', false, true) ?>
<p class="legal-links"><a href="/terms" target="_blank" rel="noopener"><?= $t('terms') ?></a> · <a href="/privacy" target="_blank" rel="noopener"><?= $t('privacy') ?></a></p>
<details class="disclosure"><summary><?= $t('optional_details') ?></summary><?= Views::scale($debate['left_label'], $debate['right_label']) ?><?= Views::check('position_consent', 'position_consent') ?><?= Views::check('notify', 'notify_debate') ?></details>
<?= Views::submit('claim_opponent') ?></form>
<?php endif; ?></section>
</div>
<?php if ((int) $debate['message_count'] > 0): ?><?= Views::render('debate-record', compact('debate', 'member', 'id', 'user')) ?><?php endif; ?>
<?php if ($user): ?><details class="report-panel"><summary><?= $t('report') ?></summary><?= Views::form('/api/debates/' . $id . '/report', 'report') ?><?= Views::area('reason', 'reason', '', true, 2000) ?><?= Views::submit('send_report', 'secondary') ?></form></details><?php endif; ?>
</article>

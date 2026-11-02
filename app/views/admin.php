<?php
use Dilemma\Views;
use Dilemma\Db;
use Dilemma\Support;
$tab = in_array($_GET['tab'] ?? '', ['users', 'debates', 'reports', 'mail_queue', 'system', 'audit'], true) ? $_GET['tab'] : 'reports';
$page = Support::integer($_GET['page'] ?? 1, 1, 100000, 'page');
$offset = ($page - 1) * 25;
?>
<section class="section"><h1><?= $t('admin') ?></h1><nav class="subnav"><?php foreach (['reports', 'debates', 'users', 'mail_queue', 'system', 'audit'] as $item): ?><a href="?tab=<?= $item ?>" <?= $tab === $item ? 'aria-current="page"' : '' ?>><?= $t($item) ?></a><?php endforeach; ?></nav>
<?php if ($tab === 'system'): ?><dl class="system-status"><dt>PHP</dt><dd><?= $e(PHP_VERSION) ?></dd><dt><?= $t('database') ?></dt><dd><?= $e(Db::one('SELECT VERSION() AS version')['version']) ?></dd><dt>MCP</dt><dd>2026-07-28 · 2025-11-25</dd><dt><?= $t('mail_queue') ?></dt><dd><?= $e(Dilemma\Config::get('MAIL_TRANSPORT')) ?></dd></dl>
<?php else:
$table = ['users' => 'users', 'debates' => 'debates', 'reports' => 'moderation_reports', 'mail_queue' => 'notification_outbox', 'audit' => 'audit_events'][$tab];
$total = (int) Db::one('SELECT COUNT(*) AS count FROM ' . $table)['count'];
$columns = match ($tab) { 'users' => 'id, alias, email, status, created_at', 'debates' => 'id, title, status, hidden, created_at', 'reports' => 'id, debate_id, message_id, reason, status, created_at', 'mail_queue' => 'id, kind, status, attempts, last_error, created_at', 'audit' => 'id, action, target_id, data, created_at' };
$rows = Db::all('SELECT ' . $columns . ' FROM ' . $table . ' ORDER BY created_at DESC LIMIT 25 OFFSET ' . $offset);
?>
<div class="admin-records"><?php foreach ($rows as $row): ?><article><div class="record-heading"><h2><?= $e($row['alias'] ?? $row['title'] ?? $row['kind'] ?? $row['action'] ?? '#' . mb_substr($row['id'], 0, 8)) ?></h2><time><?= $e(Views::date($row['created_at'])) ?></time></div><dl><?php foreach ($row as $key => $value): if (in_array($key, ['created_at', 'alias', 'title'], true)) continue; ?><dt><?= $t('field_' . $key) ?></dt><dd class="user-text"><?= $key === 'status' ? $t((string) $value) : $e($value) ?></dd><?php endforeach; ?></dl>
<?php if ($tab === 'reports' && $row['message_id']): $reported = Db::one('SELECT body FROM debate_messages WHERE id = ? AND debate_id = ?', [$row['message_id'], $row['debate_id']]); ?><details><summary><?= $t('full_argument') ?></summary><p class="user-text"><?= $e($reported['body'] ?? '') ?></p></details><?php endif; ?>
<?php if ($tab !== 'audit'): ?><?= Views::form('/api/admin') ?><label class="field"><span><?= $t('admin') ?></span><select name="action"><?php $actions = match ($tab) { 'users' => ['block_user', 'restore_user'], 'debates' => ['hide_debate', 'restore_debate'], 'reports' => ['resolve_report', 'hide_debate', 'restore_debate'], 'mail_queue' => ['retry_mail'] }; foreach ($actions as $action): ?><option value="<?= $action ?>"><?= $t($action) ?></option><?php endforeach; ?></select></label><?= Views::hidden('target_id', $row['id']) ?><?php if ($tab === 'reports'): ?><?= Views::hidden('debate_id', $row['debate_id']) ?><?php endif; ?><?= Views::field('reason', 'reason', '', 'text', true, 500) ?><?= Views::submit('save', 'secondary') ?></form><?php endif; ?></article><?php endforeach; ?></div><?= Views::pagination($page, max(1, (int) ceil($total / 25))) ?><?php endif; ?></section>

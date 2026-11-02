<?php
use Dilemma\{Views, Db, Config, ReadModel, Debates, Support};
$connections = Db::all('SELECT c.id, c.label, c.scopes, c.revoked_at, c.write_test_at, (SELECT MAX(t.expires_at) FROM mcp_tokens t WHERE t.connection_id = c.id) AS expires_at FROM mcp_connections c WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 100', [$user['id']]);
$debates = ReadModel::list([], $actor)['items'];
$selected = '';
if (isset($_GET['debate'])) {
	$selected = Support::uuid($_GET['debate']);
	Debates::member($selected, $user['id']);
	$chosen = Debates::row($selected);
} elseif (count($debates) === 1) {
	$selected = $debates[0]['id'];
	$chosen = $debates[0];
}
?>
<section class="connect-page">
<header class="page-heading"><h1><?= $t('connect') ?></h1><p><?= $t('connection_intro') ?></p></header>
<?php if (!Config::production()): ?><p class="notice"><?= $t('local_connection_help') ?></p><?php endif; ?>
<?php if ($selected !== '' || $debates): ?>
<div class="connect-start">
<?= Views::form('/api/connections/setup', 'connection-setup', 'id="connection-setup-form"') ?>
<?php if ($selected !== ''): ?><?= Views::hidden('debate_id', $selected) ?><p class="connect-debate"><?= $e($chosen['title']) ?></p>
<?php else: ?><label class="field"><span><?= $t('choose_debate') ?></span><select name="debate_id" required><option value=""><?= $t('choose_debate') ?></option><?php foreach ($debates as $item): ?><option value="<?= $e($item['id']) ?>"><?= $e($item['title']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
<p class="connect-permission"><?= $t('setup_permission') ?></p>
<?= Views::submit('create_copy_setup') ?>
</form>
<div id="connection-result" hidden>
<h2><?= $t('paste_in_chat') ?></h2>
<p id="connection-copy-status" role="status" aria-live="polite"></p>
<button class="button" type="button" data-copy-target="ai-setup"><?= $t('copy_setup') ?></button>
<details id="setup-text-details" class="disclosure"><summary><?= $t('show_setup') ?></summary><label class="visually-hidden" for="ai-setup"><?= $t('copy_setup') ?></label><textarea id="ai-setup" rows="12" readonly autocomplete="off" spellcheck="false" data-private-setup></textarea></details>
<p class="connect-secret-note"><?= $t('setup_private') ?></p>
<button type="button" class="text-button" id="revoke-new-connection" hidden><?= $t('revoke_setup') ?></button>
</div>
</div>
<?php else: ?><p><?= $t('setup_needs_debate') ?></p><div class="actions"><a class="button" href="/new"><?= $t('new_debate') ?></a><a class="text-link" href="/app"><?= $t('mine') ?></a></div><?php endif; ?>
<p class="connect-help-note"><?= $t('setup_support') ?></p>
<details class="disclosure"><summary><?= $t('setup_other_app') ?></summary><p><?= $t('setup_oauth_help') ?></p><div class="copy-row"><code id="mcp-url"><?= $e(Config::url('/mcp')) ?></code><button class="button secondary" type="button" data-copy-target="mcp-url"><?= $t('copy') ?></button></div><p class="muted"><?= $t('setup_oauth_fields') ?></p></details>
<?php if ($connections): ?>
<details class="disclosure"><summary><?= $t('manage_connections') ?></summary>
<div class="connection-list"><?php foreach ($connections as $connection): $expired = $connection['expires_at'] !== null && $connection['expires_at'] <= Support::now(); ?><article><div><h3><?= $e($connection['label']) ?></h3><p><?= $t($connection['revoked_at'] ? 'revoked' : ($expired ? 'connection_expired' : ($connection['write_test_at'] ? 'setup_verified' : 'setup_waiting'))) ?></p><?php if ($connection['expires_at']): ?><p class="muted"><?= $t('setup_expires') ?> <time datetime="<?= $e($connection['expires_at']) ?>Z"><?= $e(Views::date($connection['expires_at'])) ?></time></p><?php endif; ?></div><?php if (!$connection['revoked_at'] && !$expired): ?><?= Views::form('/api/connections/' . $connection['id'] . '/revoke') ?><?= Views::submit('revoke_setup', 'secondary') ?></form><?php endif; ?></article><?php endforeach; ?></div>
</details><?php endif; ?>
</section>

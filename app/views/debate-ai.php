<?php
use Dilemma\{Connections, Support};
$setup = Connections::current($actor, $debate['id']);
$revoked = $setup['revoked'] ?? false;
?>
<section id="connect-ai" class="connect-start debate-ai" data-debate-ai="<?= $e($debate['id']) ?>" data-setup-ready="<?= $setup ? 'true' : 'false' ?>">
<h2><?= $t('connect') ?></h2>
<label class="field"><span><?= $t('ai_app') ?></span><select id="ai-app-choice"><option value=""><?= $t('ai_app_choose') ?></option><option value="native">Claude</option><option value="direct">Codex</option><option value="other"><?= $t('ai_app_other') ?></option></select></label>
<div data-app-route="native" hidden><a class="text-button" href="https://claude.ai/customize/connectors" target="_blank" rel="noopener noreferrer"><?= \Dilemma\Views::icon('external-link') ?><span><?= $t('ai_open_claude') ?></span></a><ol class="connect-steps"><li><?= $t('ai_claude_step') ?></li><li><?= $t('ai_oauth_step') ?></li></ol></div>
<div data-app-route="other" hidden><p><?= $t('ai_other_step') ?></p></div>
<div data-app-route="server" hidden><div class="server-copy"><code id="mcp-endpoint"><?= $e(Dilemma\Config::url('/mcp')) ?></code><button type="button" class="text-button" data-copy-target="mcp-endpoint"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy') ?></span></button></div><p><?= $t('ai_native_finish') ?></p><button type="button" class="button" data-copy-target="ai-native-setup"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy_setup') ?></span></button></div><details data-app-route="server" hidden><summary class="disclosure-control"><span><?= $t('show_setup') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><textarea aria-label="<?= $t('copy_setup') ?>" id="ai-native-setup" rows="7" readonly autocomplete="off" data-private-setup><?= $e($setup['native_setup'] ?? '') ?></textarea></details>
<p data-app-route="direct" hidden><?= $t('ai_direct_step') ?></p>

<p id="connection-copy-status" role="status" aria-live="polite"><?= !$setup ? $t('working') : ($revoked ? $t('setup_removed') : '') ?></p>
<div id="connection-result" <?= !$setup || $revoked ? 'hidden' : '' ?>>
<div class="actions"><button class="button" type="button" data-ai-copy data-copy-target="ai-setup" disabled><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy_setup') ?></span></button></div>

<p class="connect-secret-note"><?= $t('setup_update_hint') ?></p>
<details class="setup-text-disclosure"><summary class="disclosure-control"><span><?= $t('show_setup') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary>
<label class="visually-hidden" for="ai-setup"><?= $t('copy_setup') ?></label>
<textarea id="ai-setup" rows="7" readonly autocomplete="off" spellcheck="false" data-private-setup><?= $e($setup['setup_text'] ?? '') ?></textarea>
</details>

<details data-app-route="fallback" hidden><summary class="disclosure-control"><span><?= $t('ai_fallback') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><p><?= $t('ai_header_help') ?></p><input id="ai-header" readonly autocomplete="off" data-private-setup aria-label="Authorization" value="<?= $e(preg_match('/Authorization: (Bearer [A-Za-z0-9_-]{43})/', $setup['setup_text'] ?? '', $auth_match) ? $auth_match[1] : '') ?>"><button type="button" class="text-button" data-copy-target="ai-header"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('ai_copy_header') ?></span></button></details>
<p class="connect-secret-note"><?= $t('ai_manual_fallback') ?></p>
<details class="connection-help"><summary class="disclosure-control"><span><?= $t('connection_help') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><p><?= $t('setup_support') ?></p><p><?= $t('setup_persistent_note') ?></p></details>

</div>

<div class="ai-wait-control" <?= !in_array($debate['status'], ['draft', 'invited', 'open', 'active', 'awaiting_human'], true) ? 'hidden' : '' ?>>
<button type="button" class="switch-control" id="ai-wait-toggle" role="switch" aria-checked="<?= !empty($setup['waiting']['enabled']) ? 'true' : 'false' ?>" aria-labelledby="ai-wait-label" aria-describedby="ai-wait-note" data-until="<?= $e($setup['waiting']['until'] ?? '') ?>">
	<span class="switch-track" aria-hidden="true"><span class="switch-thumb"></span></span>
	<span class="switch-label" id="ai-wait-label"><?= $t('ai_wait_label') ?></span>
	<span class="switch-state" aria-hidden="true"><?= $t(!empty($setup['waiting']['enabled']) ? 'control_on' : 'control_off') ?></span>
</button>
<p id="ai-wait-status" role="status" aria-live="polite"></p>
<p class="ai-wait-note" id="ai-wait-note"><?= $t('ai_wait_note') ?></p>
</div>
<details class="debate-ai-manage"><summary class="disclosure-control"><span><?= $t('manage_connections') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><button type="button" class="button" id="renew-debate-connection" <?= !$revoked ? 'hidden' : '' ?>><?= \Dilemma\Views::icon('rotate-ccw') ?><span data-control-label><?= $t('restore_setup') ?></span></button><button type="button" class="text-button" id="revoke-new-connection" <?= $revoked ? 'hidden' : '' ?> data-connection-id="<?= $e($setup['connection_id'] ?? '') ?>"><?= \Dilemma\Views::icon('unlink') ?><span data-control-label><?= $t('revoke_setup') ?></span></button>
<?php foreach (Dilemma\Db::all('SELECT id,label FROM mcp_connections WHERE user_id=? AND debate_id=? AND revoked_at IS NULL AND id<>? ORDER BY created_at DESC', [$actor['user_id'], $debate['id'], $setup['connection_id'] ?? '']) as $grant): ?>
<?= Dilemma\Views::form('/api/connections/' . $grant['id'] . '/revoke') ?><p><?= $e($grant['label']) ?></p><?= Dilemma\Views::submit('ai_connection_revoke', 'secondary') ?></form>
<?php endforeach; ?></details>
<button type="button" class="text-button" id="retry-debate-connection" hidden><?= \Dilemma\Views::icon('rotate-ccw') ?><span data-control-label><?= $t('retry_setup') ?></span></button>
</section>

<?php
use Dilemma\{Connections, Support};
$setup = Connections::current($actor, $debate['id']);
$revoked = $setup['revoked'] ?? false;
$native = Connections::native_status($actor, $debate['id']);
?>
<section id="connect-ai" class="connect-start debate-ai" data-debate-ai="<?= $e($debate['id']) ?>" data-setup-ready="<?= $setup ? 'true' : 'false' ?>">
<h2><?= $t('connect') ?></h2>
<label class="field"><span><?= $t('ai_app') ?></span><select id="ai-app-choice"><option value="native" selected>Claude</option><option value="direct">Codex</option><option value="other"><?= $t('ai_app_other') ?></option></select></label>
<div data-app-route="native" class="claude-guide" data-claude-state="<?= $e($native['state']) ?>" hidden>
	<div class="claude-step" data-claude-step="connect" <?= $native['state'] !== 'connect' ? 'hidden' : '' ?>>
		<a class="button" href="/connect/claude?debate=<?= $e($debate['id']) ?>" target="_blank" rel="noopener" data-claude-open><?= \Dilemma\Views::icon('external-link') ?><span><?= $t('claude_add_button') ?></span></a>
		<p class="claude-hint"><?= $t('claude_add_hint') ?></p>
		<details class="claude-manual"><summary class="disclosure-control"><span><?= $t('claude_manual') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary>
			<p><?= $t('claude_manual_steps') ?></p><div class="server-copy"><code id="claude-endpoint"><?= $e(Dilemma\Config::url('/mcp')) ?></code><button type="button" class="text-button" data-copy-target="claude-endpoint"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy') ?></span></button></div>
			<p><?= $t('claude_plan_note') ?></p>
		</details>
	</div>
	<div class="claude-step" data-claude-step="enable" <?= $native['state'] !== 'enable' ? 'hidden' : '' ?>>
		<button type="button" class="button" data-claude-enable><?= \Dilemma\Views::icon('link') ?><span data-control-label><?= $t('claude_enable_button') ?></span></button>
		<p class="claude-hint"><?= $t('claude_enable_hint') ?></p>
	</div>
	<p id="claude-status" class="claude-status" role="status" aria-live="polite" data-ready="<?= $native['ready'] ? 'true' : 'false' ?>"><?= $native['ready'] ? $t($native['verified'] ? 'claude_verified' : 'claude_ready') : '' ?></p>
	<button type="button" class="text-button" data-claude-check hidden><?= \Dilemma\Views::icon('rotate-ccw') ?><span data-control-label><?= $t('claude_check') ?></span></button>
</div>
<div data-app-route="other" hidden><p><?= $t('ai_other_step') ?></p><div class="server-copy"><code id="mcp-endpoint"><?= $e(Dilemma\Config::url('/mcp')) ?></code><button type="button" class="text-button" data-copy-target="mcp-endpoint"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy') ?></span></button></div></div>
<div data-app-route="server" class="native-chat-step" hidden><button type="button" class="button" id="native-copy" data-copy-target="ai-native-setup"><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('claude_copy_button') ?></span></button><p class="claude-hint"><?= $t('claude_paste') ?> <a href="https://claude.ai/new" target="_blank" rel="noopener noreferrer" data-claude-new><?= $t('ai_open_claude') ?></a></p><details id="native-text"><summary class="disclosure-control"><span><?= $t('show_setup') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><textarea aria-label="<?= $t('copy_setup') ?>" id="ai-native-setup" rows="7" readonly autocomplete="off" data-native-setup><?= $e($native['native_setup']) ?></textarea></details></div>
<p data-app-route="direct" hidden><?= $t('ai_direct_step') ?></p>

<p id="connection-copy-status" role="status" aria-live="polite"><?= !$setup ? $t('working') : '' ?></p>
<p id="direct-access-status" data-app-route="direct" hidden><?= $revoked ? $t('setup_removed') : '' ?></p>
<div id="connection-result" <?= !$setup || $revoked ? 'hidden' : '' ?>>
<div class="actions"><button class="button" type="button" data-ai-copy data-copy-target="ai-setup" disabled><?= \Dilemma\Views::icon('copy') ?><span data-control-label><?= $t('copy_setup') ?></span></button></div>

<p class="connect-secret-note" data-app-route="direct" hidden><?= $t('setup_update_hint') ?></p>
<details class="setup-text-disclosure"><summary class="disclosure-control"><span><?= $t('show_setup') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary>
<label class="visually-hidden" for="ai-setup"><?= $t('copy_setup') ?></label>
<textarea id="ai-setup" rows="7" readonly autocomplete="off" spellcheck="false" data-private-setup><?= $e($setup['setup_text'] ?? '') ?></textarea>
</details>

<details class="connection-help" data-app-route="direct" hidden><summary class="disclosure-control"><span><?= $t('connection_help') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><p><?= $t('setup_support') ?></p><p><?= $t('setup_persistent_note') ?></p><p><?= $t('ai_manual_fallback') ?></p></details>

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

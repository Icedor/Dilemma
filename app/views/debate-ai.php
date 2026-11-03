<?php
use Dilemma\{Connections, Support};
$setup = Connections::current($actor, $debate['id']);
$revoked = $setup['revoked'] ?? false;
?>
<section id="connect-ai" class="connect-start debate-ai" data-debate-ai="<?= $e($debate['id']) ?>" data-setup-ready="<?= $setup ? 'true' : 'false' ?>">
<h2><?= $t('connect') ?></h2>
<p><?= $t('debate_ai_intro') ?></p>

<p id="connection-copy-status" role="status" aria-live="polite"><?= !$setup ? $t('working') : ($revoked ? $t('setup_removed') : '') ?></p>
<div id="connection-result" <?= !$setup || $revoked ? 'hidden' : '' ?>>
<div class="actions"><button class="button" type="button" data-copy-target="ai-setup"><?= $t('copy_setup') ?></button></div>
<p class="connect-secret-note"><?= $t('setup_update_hint') ?></p>
<details class="setup-text-disclosure"><summary><?= $t('show_setup') ?></summary>
<label class="visually-hidden" for="ai-setup"><?= $t('copy_setup') ?></label>
<textarea id="ai-setup" rows="7" readonly autocomplete="off" spellcheck="false" data-private-setup><?= $e($setup['setup_text'] ?? '') ?></textarea>
</details>
<details class="connection-help"><summary><?= $t('connection_help') ?></summary><p><?= $t('setup_support') ?></p><p><?= $t('setup_persistent_note') ?></p></details>
<details class="debate-ai-manage"><summary><?= $t('manage_connections') ?></summary><button type="button" class="text-button" id="revoke-new-connection" data-connection-id="<?= $e($setup['connection_id'] ?? '') ?>"><?= $t('revoke_setup') ?></button></details>
</div>
<button type="button" class="button" id="renew-debate-connection" <?= !$revoked ? 'hidden' : '' ?>><?= $t('restore_setup') ?></button>
<button type="button" class="text-button" id="retry-debate-connection" hidden><?= $t('retry_setup') ?></button>
</section>

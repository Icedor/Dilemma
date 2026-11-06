<?php
use Dilemma\{Views, Connections, Db};
$preferred = is_string($_GET['debate_id'] ?? null) ? $_GET['debate_id'] : (is_string($_COOKIE['dilemma_connect_debate'] ?? null) ? $_COOKIE['dilemma_connect_debate'] : '');
$choices = Db::all("SELECT d.id, d.title FROM debates d JOIN debate_participants p ON p.debate_id=d.id WHERE p.user_id=? AND p.left_at IS NULL AND d.hidden=0 ORDER BY d.updated_at DESC LIMIT 200", [$user['id']]);
?>
<section class="oauth-consent reading">
<p class="eyebrow">Dilemma × <?= $e($authorization['client']['name']) ?></p>
<p class="muted"><?= $e(parse_url($authorization['redirect_uri'], PHP_URL_HOST)) ?></p><h1><?= $t('oauth_choose') ?></h1><p><?= $t('oauth_one_debate') ?></p>
<?php if ($choices): ?>
<?= Views::form('/api/oauth/authorize', 'oauth') ?>
<?php foreach (['client_id', 'redirect_uri', 'response_type', 'code_challenge', 'code_challenge_method', 'resource', 'scope', 'state'] as $field) echo Views::hidden($field, match ($field) { 'client_id' => $authorization['client']['id'], 'response_type' => 'code', 'code_challenge_method' => 'S256', default => $authorization[$field] }); ?>
<?= Views::hidden('approve', 'true') ?>
<label class="field"><span><?= $t('oauth_choose') ?></span><select name="debate_id" required>
<option value=""><?= $t('oauth_select') ?></option>
<?php foreach ($choices as $choice): ?><option value="<?= $e($choice['id']) ?>" <?= count($choices) === 1 || $preferred === $choice['id'] ? 'selected' : '' ?>><?= $e($choice['title']) ?></option><?php endforeach; ?>
</select></label>
<p><?= $t(str_contains($authorization['scope'], Connections::WRITE) ? 'oauth_permissions' : 'read_scope') ?></p><p class="muted"><?= $t('human_boundary') ?></p>
<div class="actions"><?= Views::submit('oauth_allow') ?><button type="button" class="text-button" data-oauth-deny><?= \Dilemma\Views::icon('x') ?><span data-control-label><?= $t('deny') ?></span></button></div>
</form>
<?php else: ?><p><?= $t('oauth_empty') ?></p><a class="button" href="/new" data-auth-return="<?= $e('/oauth/authorize?' . http_build_query($_GET)) ?>"><?= $t('new_debate') ?></a><?php endif; ?>
<details><summary class="disclosure-control"><span><?= $t('oauth_details') ?></span><?= \Dilemma\Views::icon('chevron-down') ?></summary><p><?= $t('authorize_intro') ?></p><code class="oauth-callback"><?= $e($authorization['redirect_uri']) ?></code></details>
</section>

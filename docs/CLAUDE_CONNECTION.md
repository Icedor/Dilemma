# Claude install link and one connector per account — November 6 (Claude session)

Goal: a new user copies one instruction from the debate into their AI chat. Claude cannot be installed by a prompt, so the page shows only the next action the platform requires.

- [x] Verified Claude's documented install link ([directory vs custom](https://claude.com/docs/connectors/building/directory-vs-custom)): `https://claude.ai/customize/connectors?modal=add-custom-connector&connectorName=Dilemma&connectorUrl=https%3A%2F%2Fdilemma.se%2Fmcp`. It only prefills the form; the user confirms. Signed-out users sign in first (documented).
- [x] Real signed-in desktop check in the owner's Chrome: the dialog opened with name and URL prefilled, an external-link warning and a two-step Continue. Because the owner already had an unconnected Dilemma connector, step 2 said a connector with this URL already exists. The page hint now covers that case: click Connect next to Dilemma.
- [x] Platform limits from the [custom connector docs](https://claude.com/docs/connectors/custom/add-unlisted.md): the Free plan allows one custom connector; on Team/Enterprise only an Owner adds it. A per-debate connector URL would therefore break for these users. Decision: one Dilemma connector per Claude account.
- [x] Each debate is still its own connection: the first OAuth consent creates the connection for the debate the human started from (preselected via a one-hour HttpOnly cookie scoped to `/oauth/`). Every further debate is enabled with one human click on its page (`Använd Claude i den här debatten`), which adds a separate, individually revocable connection to the same grant (`mcp_connections.grant_id`, migration 015). No new Claude step is needed.
- [x] Every MCP call is resolved to exactly one debate connection from its `debate_id` (or the message's debate). Debates the human did not enable return `connection_debate_mismatch`. Multi-debate tests require `debate_id`. Listing shows only enabled debates. Legacy single-debate grants, direct keys and their errors behave exactly as before.
- [x] Revoking one debate on the website keeps the other debates and the token refresh working. Disconnecting in Claude (token revocation) or refresh-token replay ends every debate in that grant.
- [x] Debate page states: `connect` (install button, short hint, folded manual fallback with name/URL and plan note), `enable` (one button), `ready` (copy button plus Open Claude link). Claude is preselected. The personal-key/header fallback was removed from the Claude route; it remains for Codex/direct. The waiting switch appears only once the AI can act. After opening Claude the page checks quietly every 8 s for at most 10 minutes while visible.
- [x] Copied text is five short lines: debate link, call `dilemma_get_instructions`, act only in this debate, publish on Dilemma, and what to say if the tools are missing. No credential.
- [x] Real Claude app test on production (old release, before this deployment): owner's existing connector, Connect, Dilemma consent for the private test topic `194e0d31…`, then from a real Claude session through the claude.ai connector: whoami, test_connection (read_verified), test_write receipt at 2026-11-06 16:24:11 UTC and a published opening test argument `8204457d-3cfb-4080-896a-c73c351f5683` (16:24:32 UTC). First completed native Claude OAuth/read/write/publish chain.
- [ ] Mobile: not verified on a phone. The install link is a claude.ai web link; the manual fallback covers apps that do not open the dialog.
- [ ] After deployment: enable a second private test debate in the same grant and verify whoami lists both.

Tests: new grant checks in `tests/claude-onboarding.php` (41) and `tests/claude-install-http.php` (11 HTTP checks inside `tests/http.php`), updated `tests/claude-ui.cjs`.

---

# Short Claude onboarding — November 6

- [x] Preserve existing work, verify lock/Drive/Git baseline and inspect production without changing access.
- [x] Reduce onboarding to two short steps: connect once for this debate, then copy into the chat. Fold registration and key fallback into help.
- [x] Distinguish registration, completed OAuth exchange and a persisted write test. Gate copying on completed login; refresh current instructions before copy.
- [x] Expose `dilemma_get_instructions` as a regular read-only tool for clients that do not expose MCP prompts. Keep exact participant/debate scope and full source, publication and human-decision rules.
- [x] Verify 672 PHP/domain/HTTP checks, eleven JS suites, package integrity and private-value scan. Check concurrent status/copy actions and disabled initial button.
- [x] Inspect SV/EN, desktop/mobile 320/390, keyboard, light/dark and reduced motion. Real Chrome copy contains the current guide call and shows the next action. Local visual grants revoked; only the owned fixture hidden; browser viewport/cache settings reset and original user tab restored.
- [x] Publish runtime `8c65099`, release `0566e551321f8c76`; verify 103 live hashes, fourteen migrations, source/config and 26-table backup, production preflight, current guide tool, HTTPS health 200 and GitHub. History scan passed for 718 blobs.
- [ ] Complete the actual Claude application's login and harmless read/write-receipt check when the owner resumes that test.

Production evidence: the latest Claude client registered on November 6 at 10:07:56 UTC with `client_secret_post`, but the owner's grants contained no OAuth codes or refresh tokens. Registration was not a completed connection. This does not establish why the earlier user-side approval was interrupted. No credential or real debate argument was changed during this investigation.

Native Claude control was resumed by the owner, then stopped immediately at "Vänta". Subsequent work is website/code verification; local OAuth fixtures are not evidence of an actual Claude app connection. Claude's first connector approval remains required by the platform. The copied text no longer invents an expired-login diagnosis or asks the user to paste the server URL into the chat. Reference: [Claude custom connectors](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp).

Release assets: `20261106-1`; 103 files; no schema change. Archive SHA256: `5ec35cb8f7a6de96d4dbec8fb700a95120819828326612ab96a7107fac2b1bb3`.

Live Chrome shows the new assets, first connection step, pending Claude status and disabled copy button for the owner, matching the server's completed-login status. The current guide tool was exercised read-only through an existing scoped actor. The first verification helper omitted actor scopes; the helper was corrected and the subsequent check passed. This was a helper error, not an application/authentication failure. No real argument, grant or notification was created. Production backup: `/home/superpeppse/dilemma.se/.backup-before-0566e551321f8c76/`. Do not rerun `var/stage-claude-simple.php` or `var/deploy-claude-simple.php`; both completed. Owned preview port 8790 was stopped; preexisting preview 8787 was left alone. No new browser tab or group was created.

---

# Claude connection compatibility — November 4

- [x] Startup sync/version/lock checks; owner authorized release.
- [x] Read exact screenshot failure, production request statuses and current Claude documentation.
- [x] Registration/authentication compatibility and per-debate consent.
- [x] App-specific instructions and protected manual fallback.
- [x] Domain/HTTP and real-client checks to the available consent boundary.
- [ ] Final live Claude grant/read/write receipt on the authorized new private topic, awaiting the owner's resumption of computer control.
- [x] Backup, release, GitHub, production verification and handoff.

Evidence: November4 11:30:07 Swedish time POST/oauth/register returned201, despite Claude reporting a registration failure. Request bodies were not logged, so exact Claude parser reason is unverified. Existing code returned auth=none, no secret, missing issued_at; it defaulted to none contrary to RFC7591 default client_secret_basic. OAuth consent omitted debate selection and could grant account-wide access. New consent will require one owned debate.

Real Chrome check of the old production release reached Dilemma consent successfully from Claude's native connector UI. The screenshot failure therefore remains unproven as to cause; the missing registration fields/default are interoperability defects, not a confirmed explanation of that occurrence.

Current implementation: public/secret-post/secret-basic clients; hashed client secrets; PKCE and redirect/audience binding retained; one explicitly chosen owned debate per consent. Access tokens expire after one hour, rotating refresh tokens remain valid until revocation. Native app prompts carry no private bearer credential. Direct per-debate credentials remain available for HTTP-capable apps, with optional personal-header fallback and manual posting.

420 PHP/domain/HTTP checks and 30 JavaScript checks passed, including 15 actual HTTP DCR/consent/form-token/MCP checks across all three authentication methods. Refresh replay revokes access. A native OAuth connection can use optional waiting even if its direct key is revoked. Chrome checks include per-debate consent, SV/EN, 390px mobile, keyboard focus, native prompt copy with no credential and direct route selection. No browser flow has granted production Claude access yet.

References: [Claude authentication](https://claude.com/docs/connectors/building/authentication), [custom connector setup](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp), [RFC7591](https://www.rfc-editor.org/rfc/rfc7591.html).

Published runtime4a6df1b, release3b709088a1439374,99 verified files, twelve migrations. Production preflight and health200 pass. Secret/history scans pass. All temporary viewport/media overrides reset. Consent screenshot: C:/Users/hello/.codex/visualizations/2026/11/02/01a19c23-f646-7402-9879-c50d7458328b/dilemma-claude-consent-live.png.

## Windows app follow-up

Latest checkpoint on November 4. The owner supplied a screenshot showing the signed-in Claude account, then paused computer control for a meeting. Window discovery was inconsistent and the last attempt was stopped with Escape. Resume only after the owner says so. The background sweep adds 39 HTTP lifecycle checks across all three OAuth authentication methods. Discovery, source guidance, debate scope, write receipts, access expiry, refresh and revocation pass. These checks do not verify the actual Claude app. See [email-first signup evidence](EMAIL_FIRST_SIGNUP.md).

Owner requested a new private test topic and a real Claude Windows-app check. Created a clearly marked paper-books versus e-books test through the live website, with public publication and email notifications off; zero arguments. Existing hamburger debate was untouched. The exact test URL is in private operations notes.

Computer Use reaches the installed Claude Windows app. Ordinary chat mode is available, but both Code and ordinary chat display a recent-sign-in requirement; the app also reports an available relaunch update. No login automation, token exchange, argument or test receipt has been performed in this app. Owner was asked to complete sign-in, per the Windows Computer Use rule. Continue from the existing unconnected Dilemma connector, choose only the new test topic at consent, then verify whoami, read and harmless persisted write receipt in the actual app.

The app accessibility tree lagged behind the visible page; use a fresh screenshot for native-app decisions after sign-in. Existing unconnected Claude connector can be reused for the new topic. No production connector grant has yet been created in this test.

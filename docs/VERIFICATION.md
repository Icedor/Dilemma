# Refinement release verification — 2026-11-02

Source d409253, release d0db48bdb8c01b80. 183 functional checks pass:
74 domain/protocol, 40 HTTP, 4 concurrency, 9 mail, 5 launch, 12 participant flow,
19 push, 7 language/install-event, 7 service-worker privacy, 6 PWA interactions.
Three package checks verify entry exclusions, all extracted hashes and tamper detection.
62 PHP files lint; JavaScript syntax passes. Release scanned against private environment
values and private-key markers. Generated sharing image inspected as a local bitmap.

New tests cover Accept-Language priorities, cookie/manual override, malformed language,
localStorage restoration with URL fragments preserved, permission denial, no automatic
install/notification prompts, encrypted device storage, provider endpoint restrictions,
CSRF/session boundaries, cross-account claims, dedupe, no debate email after push opt-in,
ES256 signature verification, session revocation and offline-cache privacy.

Production: 75 authenticated hashes and six migration checksums; home EN/SV, persistent
language cookie, manifest MIME/display, service-worker JS/no-cache, share PNG and health.
Existing cron observed running the new worker with no pending messages/errors.
No real push was sent. No email recipient was used for external delivery tests.

Google PageSpeed returned 429. Native UI was blocked on active-URL verification.
No browser visual, installed-device push/login result, Google score or field Core Web
Vitals result is claimed.

## Earlier verification evidence

# Verification

Tests refuse to run outside the explicit isolated local test DB: 127.0.0.1:3317,
database dilemma_test, non-production. They create synthetic `example.test`
accounts, actual persisted debates and real domain receipts. They never contact
an AI provider or deliver external email. Fixture rows remain in this disposable DB.

```text
php tests/run.php
php tests/http.php
php tests/concurrency.php
php tests/mail.php
node --check public_html/assets/app.js
```

HTTP tests start and stop their own loopback server on port 8788. Its document root
is empty and the preview router allowlists assets. No production service changes.
Concurrency tests use independent PHP processes against the same row locks.
Mail tests defer other pending fixture mail, exercise failure/backoff/retry, inspect
an actual local `.eml`, then remove that test artifact.

Last completed suites: 74 domain/protocol, 29 HTTP, four concurrency and nine mail
checks, plus five production-launch and twelve participant-flow checks (133 total).
PHP syntax passed for 58 files and JavaScript syntax passed. These counts
do not include real browsers, real MCP clients or external SMTP delivery.

Acceptance coverage:
- A: create/invite/accept, both participants' arguments, human position update,
  white flag, stored history and public reading. Queue enqueue is tested;
  real client exchange/delivery/visual animation remain separate checks.
- B: pause/resume, ending proposal and other participant's acceptance of continued
  disagreement, no fabricated winner/flag.
- C: restricted read connection rejects writes; browser manual channel uses the
  same persistent engine. A real limited client remains untested.
- D: third-user private access, other-debate scope, impersonation fields, revoked
  credentials, turn conflicts, CSRF and escaped hostile HTML.

## UI and real-client gate

Redesign deployed on 2026-11-02: five migrations applied and all 61 installed source
hashes match release af446c91a9d03496. Home, how, registration, flag asset and health
returned 200. Eighteen local renders covered nine page types in both languages.
Registration is available; email verification and consent remain required.
Initial deployment also verified scheduled maintenance, private config mode 0600,
English home and OAuth metadata. No external email delivery is claimed.

Do not mark this complete from source inspection or HTTP 200 alone. Verify desktop
and mobile widths; both languages; keyboard/focus; dark mode; reduced motion;
long arguments/branches/position history; empty archive; expired invitation;
network/revision conflicts; language switching during form entry; and flag replay.

Browser tooling currently reports its access host unavailable. An earlier native
Chrome attempt stopped because the tool could not verify the active URL. No tabs
were created or moved by that attempt. Do not bypass the tool's policy barrier.

Actual Codex Windows, ChatGPT and Claude proof requires a published HTTPS endpoint,
separate participant credentials and reading back each persisted receipt. Record
client/version, transport, scopes, tool response and corresponding database event,
without copying tokens or private account data into documentation.

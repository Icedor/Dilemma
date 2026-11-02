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
checks. PHP syntax passed for 55 files and JavaScript syntax passed. These counts
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

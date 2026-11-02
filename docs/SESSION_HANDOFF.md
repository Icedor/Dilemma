# Latest checkpoint: opinion-first copy and color correction
2026-11-02, source **b2109f7**, release **a7dbfb83a295b913** is live.
This supersedes the earlier runtime release details below.

- Sharing artwork now says “What would change your mind?” at /assets/share-opinion.png.
  Old AI-first sharing asset removed after backup. Intro, SEO and manifest copy
  center opinions/conviction. AI remains a feature explained in the how-it-works flow.
- Every large yellow/coral block now has brighter layered gradients and fine grain,
  including process, login, flag/result dialog, header/hero and offline page.
- Manifest has improved English description/categories and a fresh link version.
  **It still has no actual screenshots; Richer Install UI is NOT complete.**
- Computer Use again stopped on URL verification this turn. Browser inventory had
  no extension tabs, host unavailable. Native Chrome showed Meta Sharing Debugger;
  binding the window was stopped by policy. No bypass/alternate automation attempted.
  Need actual user-supplied captures or a functioning authorized browser connection.
- 40 HTTP + 7 worker checks passed, PHP lint and 3 package tests passed.
  New sharing bitmap visually inspected; no actual page/installation visual QA.
- All 75 installed hashes verified. Public English title/image, new PNG and manifest
  description verified via three serial HTTPS requests. Six migrations unchanged.
- ZIP SHA256: 82dafa46983a8e4b88b51cae213b0ee46c2901971f6c6d8ac0b1f5477da922ec.
- Server staging .deploy-a7dbfb83a295b913; backup .backup-before-a7dbfb83a295b913/
  previous-release.zip includes prior source, config, manifest and push identity.
  No new DB snapshot needed; no schema/account/push-preference changes.
- Local commit only; no GitHub push. No SSH denial, retry loop, email or push test.
- See docs/COPY_AND_INSTALL.md for exact remaining gallery work and design direction.

## Previous checkpoints

# Latest checkpoint: refinement/PWA deployed
2026-11-02, same original exclusive session. This section supersedes the prior release details below.

- User approved the improved overall design, rejected the large flag, requested yellow gradients/grain and a compact animated flag inside debates.
- Also requested first-visit browser language with manual localStorage persistence, optional install/PWA/push, magic-link sign-in, social artwork and strong English SEO before deployment.
- Implemented and published source commit **d409253**, release **d0db48bdb8c01b80**, 75 files.
- ZIP SHA256: 44b1d60f439d8241f8a9a6667e42a44bcc507b692f37077decc04eefffdfc347.
- Production six migrations; new 006 adds push preference/subscriptions/outbox. All 75 installed hashes verified after publishing.
- Production stage: /home/superpeppse/dilemma.se/.deploy-d0db48bdb8c01b80.
- Backup: .backup-before-d0db48bdb8c01b80/previous-release.zip and database.json (consistent snapshot of all 23 pre-migration tables). Restoration not tested.
- Push identity: private var/push-keys.json, mode 0600. Private local recovery copy .env.production.push-keys.json (Git ignored). No secret values in docs.
- Existing cron observed executing the new push worker with sent=0, failed=0, blocked=false. No real push delivery or external email test performed.
- HTTPS checked: browser English on root, explicit Swedish plus persistent cookie, manifest (correct MIME/standalone), worker (JS/no-cache), sharing PNG and database health.
- 183 functional checks and 3 package integrity checks pass; 62 PHP files lint; JS syntax checked. No private data in release.
- Google PageSpeed API returned 429; stop, no retry until concrete access correction. No measured score/all-green claim.
- Native browser UI remains blocked by URL verification; do not use another automation channel to bypass it. Real desktop/mobile visual, PWA install, push delivery and magic-link-in-installed-app QA still outstanding.
- Deployment paused once because root-level files failed an overly strict parent-path check; corrected and resumed only after every file matched old/new hashes. No authentication error or SSH retry loop. Existing .env, other projects, cgi-bin, .well-known and cron entries preserved.
- Local source committed, no GitHub push. Remote fetch still had no remote branch refs.
- Do not resend project mail to the owner, add inbound mail/reply handling, enable push by default, or put installation controls on the home page.

## Previous checkpoint (historical)

# Session handoff — 2026-11-02

Same original owner-confirmed sole writer. Lock:
dilemma-ed7e2647-bfa1-4cd5-bc08-4760c62255d9. Recheck before changes.

## Latest owner correction and deployed release

The owner rejected the first design and clarified the exact human/AI loop.
Read docs/DESIGN.md and the latest user messages. The four local inspiration
images were actually opened. No green theme, generic people SVG or visitor-facing
technical explanation remains. The new yellow/coral/black composition, large type
and photographic white flag are deployed, not merely proposed.

- Source commit 25783ea; private release dilemma-af446c91a9d03496.zip.
- ZIP SHA256 f845175cf149f1a9884a3e08c7ffe28b96afd49e391a27e9eff332ca32a94e5b.
- All 61 installed source hashes verified over authenticated SSH.
- Home, how, registration, new WebP asset and health returned HTTPS 200.
- Five migrations applied. Migration 005 adds participant notify default 0.
- Backups before this change: .backup-before-af446c91a9d03496 under the verified
  Dilemma root: previous-release.zip (old manifest files/config), database.json
  (consistent snapshot of all 23 tables and CREATE definitions).
- Staging: .deploy-af446c91a9d03496. Existing private .env reused, never overwritten.
  Existing cgi-bin/.well-known preserved. Removed people.svg was backed up first.
- Original one-minute project cron remains; original other cron entries unchanged.
- No failed SSH authentication or retry occurred. No external test mail sent.
- Local code is committed; no GitHub push performed.

## Functional corrections

Creation requires opinion/reason, with optional detail fields. Invitation sharing
is primary; email invitation is under an optional disclosure. Notifications are
unchecked choices at creation/acceptance and can be changed per debate; the profile
preference can switch off all chosen notifications. No Reply-To, inbound processing
or owner copies. Login verification is separate from optional debate notifications.

The AI instruction asks each AI to fairly assess evidence, return to its own human
when persuaded and consider new objections. Human-only confirmed withdrawal ends
without flag/persuasion. Raising a flag still requires a separate human decision.

The assistant-created LEGAL_REVIEWED login gate was removed: the original prompt
explicitly permits legal texts marked for final owner review. The legal-page notice
remains. Email verification, session/CSRF checks and processing/publication consent
remain intact. Do not reintroduce an unrequested signup gate.

## Verification and limits

133 functional checks: 74 domain/protocol, 29 HTTP, four concurrency, nine local
spool mail, five launch and twelve new participant-flow checks. Three package
integrity checks passed. PHP lint: 58 files; JS syntax passed. Eighteen local
page renders covered nine pages in both languages.

Visual QA is NOT complete. Latest Chrome metadata identified the actual Dilemma
window, but native Computer Use stopped because it could not verify its URL.
Before that, auto-review rejected binding the Facebook-titled window. The owner
explicitly says to select Dilemma yourself, not ask them to keep it active.
Do not repeatedly ask for active-tab handoff or bypass the tool restriction.
No browser tabs/groups were created, moved or closed.

Real Codex Windows/ChatGPT/Claude acceptance, inbox delivery, admin provisioning,
backup restoration and final legal/processor/retention review remain outstanding.
Do not claim hacking is impossible or real-client tests passed from protocol tests.

## Environment

Private credentials: ../Dokument/Uppgifter.txt and ignored .env.production.
cPanel entries now use U:/P: lines. Correct project account/key already verified.
Use explicit key, BatchMode, IdentitiesOnly, strict host checks; stop at first denial.
CLI UAPI unavailable in jailed shell. No FTP was required. Never alter neighboring
projects or Flowstate. Sender noreply@dilemma.se; owner says inbound is blocked.

Local preview: 127.0.0.1:8787; isolated local MariaDB: port 3317.
Separate production key/database/config; development config unchanged.

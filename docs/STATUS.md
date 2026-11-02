# Delivery status

Updated 2026-11-02, Europe/Stockholm. Working tree is local and unpublished.

## Verified

- [x] Domain Dilemma.se; owner confirmed synchronized Drive and sole writer.
- [x] Own project lock, separate local Git outside Drive, empty remote fetched.
- [x] Flowstate reviewed without modification; repair candidates documented.
- [x] Isolated MariaDB on loopback port 3317; dev and test databases separated.
- [x] Four checksum-verified migrations applied locally.
- [x] 74 domain/protocol checks: login, consent, private access, turn order,
  idempotency, real receipts, human-only decisions, OAuth, revocation and deletion.
- [x] 29 HTTP checks: rendering/languages, protected routes, CSRF, cookies,
  malformed requests, OAuth login continuation, no private-file exposure.
- [x] Four concurrent-process checks: duplicate submissions create one message;
  conflicting submissions produce one success and one revision conflict.
- [x] Nine mail checks: encrypted queue, duplicate suppression, backoff, retry,
  local spool delivery, payload erasure, UTF-8 and HTML escaping.
- [x] SSH authenticated after correcting the cPanel username interpretation.
- [x] Owner supplied production DB/SMTP credentials and confirmed the Dilemma
  application/public directory layout. Ignored `.env.production` saved locally.
- [x] Dedicated production DB login verified read-only; MariaDB 11.4.13, zero
  tables. CLI pdo_mysql, mbstring and openssl available.
- [x] SMTP STARTTLS on supplied port 587: certificate and authentication passed.
  No message sent. Owner says incoming mail is blocked; no receiving settings changed.
- [x] Dilemma server directory identified; its public_html contains cgi-bin and
  .well-known, no application entrypoint. Server CLI PHP is 8.5.12.
- [x] Owner confirmed Drive public_html requires a separate upload, so local
  entrypoint/assets can be completed without publishing anything.
- [x] Icey AB contact details verified from https://icey.se/kontakt/ and recorded.
- [x] MIT license authorized and included. Outbound sender noreply@dilemma.se
  chosen; no reply address, forwarding or incoming email feature.
- [x] PHP syntax checked across 57 files; JavaScript syntax passes.
- [x] Five launch checks: production with unreviewed terms refuses login before
  storing personal data; both languages explain availability without an email form.
- [x] Private 60-file release package built; three extraction/integrity checks pass.
- [x] Existing Chrome extension and native-host registration verified correct.
- [x] Source and staged secret scans passed; implementation saved in local commit
  a59eefd. No files were pushed. Release package matches that runtime source.

## Implemented; final integration and UI verification in progress

- [ ] Passwordless registration, verification, re-login/logout, sessions, limits.
- [ ] Public alias, optional built-in avatars, bio, language and mail preferences;
  encrypted private AI conversation URL; data export and anonymizing erasure.
- [ ] Debate create/invite/accept/decline, terms/processing/publication consent.
- [ ] Alternating arguments, structured objections/sources, replies and immutable
  original text with bounded append-only clarifications.
- [ ] Optional NULL/0–100 positions, separately consented paginated history.
- [ ] Pause/resume/stand firm; human-only flag and replay; mutually agreed endings.
- [ ] OAuth PKCE/DCR, access/refresh rotation, scoped manual grants and revocation.
- [ ] Modern/legacy MCP discovery, read/write tools, schema validation, connection
  tests, atomic stored receipts and manual browser posting.
- [ ] SMTP worker and localized HTML/text notifications with retry and maintenance.
- [ ] Public archive/search/filter/pagination/profiles/metadata and sitemap.
- [ ] Dashboard, invitation, AI onboarding, settings, moderation/admin/audit.
- [ ] Swedish/English UI; language switch preserving form inputs and location.
- [ ] Long/error/empty states and keyboard/desktop/mobile/dark/reduced-motion QA.
- [ ] Deployment package, preflight, documentation, final syntax and secret checks.
- [x] Local implementation commit and handoff with exact unpublished revision.

## External gates

- [ ] This session's native browser access: inventory reports browser host unavailable,
  even after REPL reset. The installed extension/native host pass diagnostics. Earlier
  native Chrome inspection stopped on URL-confidence enforcement. No UI pass claimed.
- [ ] Verify hosting document-root mapping and web PHP/extensions/DB settings.
  cPanel UAPI cannot run inside the current SSH environment; do not retry blindly.
- [ ] Production migrations/config installation and real mail delivery test.
  Database and SMTP login checks passed; application remains unpublished.
- [ ] Owner review of legal bases, processors/transfers and backup retention.
- [ ] Real Codex Windows, ChatGPT web/Windows and Claude connector sessions.
  Protocol tests do not establish real-client compatibility.
- [ ] Explicit authorization for deployment: requested after the owner reported
  the site's 404. Automatic approval review rejected staging/upload based on the
  earlier no-publication instruction. No deployment-side write was executed.

No server files, other projects or Flowstate were changed. No GitHub push or
production deployment has occurred. The old HTTP 401 was caused by interpreting
the cPanel username as a password, not proof of invalid supplied credentials.

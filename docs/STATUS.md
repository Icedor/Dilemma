# Current correction — 2026-11-02
Live source **b2109f7**, release **a7dbfb83a295b913**.
Opinion-first intro/search/install copy, new sharing artwork and complete yellow/
coral gradient/texture treatment are published. All 75 installed hashes and the
changed public resources were verified. Focused tests: 40 HTTP, 7 worker, 3 package.

**Still incomplete:** actual wide/narrow app screenshots and Richer Install UI.
The description is fixed, but the gallery is missing because browser capture was
stopped by the tool's current-URL verification. No fabricated screenshots were added.
See [remaining gallery work](COPY_AND_INSTALL.md).

## Earlier release evidence

# Current release: design refinement, language and PWA
Updated 2026-11-02. Live source **d409253**, release **d0db48bdb8c01b80**.
The earlier redesign evidence below is historical; this section is current.

- [x] Large flag removed from home/login; warm yellow gradients and subtle grain.
- [x] Small brief CSS flag animation only in debate result/dialog; reduced motion respected.
- [x] Browser EN/SV negotiation; explicit choice persists in localStorage and server cookie.
- [x] Optional PWA installation buttons beside debate/in profile; browser prompt deferred.
- [x] Opt-in push replaces debate email, with session-bound encrypted endpoints and generic messages; magic links unchanged.
- [x] Native service worker keeps private pages, API responses and sign-in links out of offline caches.
- [x] English SEO copy, bilingual canonical/hreflang, OG/Twitter image, app icons and manifest.
- [x] 183 functional checks, 3 package-integrity checks, PHP/JS syntax and private-value scan.
- [x] Published after source/config + 23-table backup; six production migrations and all 75 file hashes verified.
- [x] Selected public HTTPS endpoints and actual scheduled push-worker execution verified.
- [ ] Actual browser/device visual, install, push-delivery and installed-app login QA unavailable.
- [ ] Google PageSpeed scores unavailable: API returned HTTP 429. No 100/all-green claim.

See [release checklist](RELEASE_NEXT.md) and [handoff](SESSION_HANDOFF.md).

## Earlier release evidence

# Delivery status

Updated 2026-11-02, Europe/Stockholm. Redesign deployed as source commit 25783ea,
release af446c91a9d03496. Registration is available. Source remains local, not on GitHub.

## Owner-requested redesign

- [x] Re-read original requirements and inspect all four supplied design references.
- [x] Replace the green-tinted theme and people graphic with yellow/coral/black,
  oversized typography, a photographic white flag and redesigned page components.
- [x] Rewrite introduction/about/how text in plain Swedish and English, reflecting
  the human → own AI → opposing AI → own human → new objections loop.
- [x] Simplify creation to opinion/reason; optional details stay available.
- [x] Implement unchecked per-debate notifications at creation/acceptance/settings.
- [x] Implement human-only confirmed ending without claiming persuasion.
- [x] Remove the assistant-invented legal-review login gate; keep the legal notice,
  email verification, authentication and explicit participant/publication consent.
- [x] Twelve new participant-flow tests pass alongside existing regression suites.
- [ ] Actual desktop/mobile visual and keyboard QA: native Computer Use stopped
  on URL verification even after Chrome was identified as Dilemma. No bypass.
- [x] Publish redesigned release: all 61 installed file hashes match. Home, how,
  registration, WebP asset and database health returned 200 over HTTPS.
- [x] Back up previous source/config and all 23 database tables before migration 005.

## Verified

- [x] Explicit site deployment approval received; actual home now returns HTTPS 200.
- [x] Five production migrations applied and all 61 installed release hashes verified.
- [x] Private production config above webroot, mode 0600; initial webroot backed up.
- [x] Existing cgi-bin/.well-known and all original cron entries preserved.
- [x] Project maintenance cron installed and actual successful execution verified.
- [x] Live health/database, English home, JS and OAuth metadata return 200; CSP/HSTS present.

- [x] Domain Dilemma.se; owner confirmed synchronized Drive and sole writer.
- [x] Own project lock, separate local Git outside Drive, empty remote fetched.
- [x] Flowstate reviewed without modification; repair candidates documented.
- [x] Isolated MariaDB on loopback port 3317; dev and test databases separated.
- [x] Five checksum-verified migrations applied locally.
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
- [x] PHP syntax checked across 58 files; JavaScript syntax passes.
- [x] Five updated launch checks: registration queues one verification message,
  both languages show sign-in, and legal review remains disclosed on legal pages.
- [x] Private 61-file release package built; three extraction/integrity checks pass.
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
- [x] Actual Dilemma webroot and working PHP/database routing verified by live responses.
- [ ] Exact web PHP configuration audit and real mail delivery test. CLI extensions
  and SMTP TLS authentication passed; no external mail has been sent.
- [ ] Owner review of legal bases, processors/transfers and backup retention.
- [ ] Real Codex Windows, ChatGPT web/Windows and Claude connector sessions.
  Protocol tests do not establish real-client compatibility.
- [x] Explicit deployment authorization received and site published.
- [ ] Public GitHub push; no source was pushed during this deployment.

Only Dilemma's own application/database/maintenance were installed; no neighboring
project or Flowstate was changed. No GitHub push occurred. The old HTTP 401 was caused by interpreting
the cPanel username as a password, not proof of invalid supplied credentials.

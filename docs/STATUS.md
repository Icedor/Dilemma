# Persistent debate access — 2026-11-02

- [x] One persistent access per participant/debate, created on create/join.
- [x] Complete selectable setup and copy button directly inside the debate.
- [x] Same credential on recopy/reload; explicit revoke and deliberate renewal.
- [x] Encrypted recovery, scoped authentication, migration 007 and legacy upgrade.
- [x] 32 setup, 46 HTTP, 6 concurrency, 74 domain, 12 participant, 19 push,
  4 clipboard and 3 archive checks; PHP/JS syntax and private-value scan.
- [ ] Publish release 4b0de79a9077eb3a after source/config and database backup.
- [ ] Real AI-client and visual browser QA remain unperformed.

## Previous connection release
Live source **1c3f105**, release **6a1a1a7accdc7480**, GitHub verified.
Flowstate-style complete bearer setup is now the primary AI connection flow:
select/preselect debate, create/copy instructions, paste in the AI app. One-day,
one-debate read/write access, immediate revoke, clipboard fallback and page-exit
secret clearing. OAuth remains secondary. Compatible legacy MCP negotiation added.
19 setup, 44 HTTP, 74 domain/MCP/OAuth, 4 clipboard and 3 package checks pass.
All 79 production hashes verified; new clipboard module and app version checked
over HTTPS. Real Codex/ChatGPT/Claude session and browser visual QA still pending.
Flowstate read-only; see AI_CONNECTIONS.md for exact scope and evidence.

## Previous email release

Live source **42405d8**, release **10c71f4a97061f89**, GitHub verified.
Branded transactional email replaces duplicated unstructured HTML. Yellow panel,
one heading/action, concise bilingual copy, responsive email tables and plain-text
fallback. Outgoing-only sender and notification opt-ins unchanged.
27 mail + 12 participant-flow + 19 push + 3 package checks pass. All 77 production
hashes verified; production generated both login MIME variants without sending.
Actual email-client visual/dark-mode checks remain unverified. See EMAIL_DESIGN.md.

## Previous account and palette release

Live source **4a29b34**, release **950517d4071e2aef**, GitHub verified.
The owner rejected the previous dark/muddy textures. New controlled bright yellow
and orange gradients remove brown pools and white/pink/purple tones. Dark text has
minimum measured exported-image contrast 9.87:1 on yellow and 6.68:1 on orange.
Account creation/sign-in redesigned with a yellow headline panel and clearer form.
Terms/privacy links in forms AND footer open separate tabs, preserving progress.
76 installed hashes verified; live registration/legal links and CSS verified.
40 HTTP, 7 worker, 6 PWA, 3 package checks pass. No real browser layout QA:
inventory still reports browser access host unavailable and no browsers.

## Superseded visual version

Live source **2a197d5**, release **d9ccbb7ce9ac3b73**.
GitHub main contains the complete project, verified after owner-authorized push.
Fire-yellow/gold textured ribbons and warm ember surfaces replace the rejected
gradients. Header and hero share one background; navigation uses a stable grid
and reserved scrollbar space. Copy is shorter in both languages, led by opinions
and conviction. Sharing image/app icons also use the approved palette direction.
All 76 installed hashes verified. English/Swedish home and WebP response verified.
Focused tests: 40 HTTP, 7 worker, 3 package. No actual browser layout QA completed.
See [textures and verification](TEXTURES.md) and [handoff](SESSION_HANDOFF.md).

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

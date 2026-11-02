# Dilemma project instructions

Read this file at every project start. User decisions override reference-project
rules. Propose changes to these rules with reasons and alternatives; wait for the
owner's approval before departing from a decided rule.

## Product and technology

- The confirmed name and domain are **Dilemma.se** (owner, 2026-11-02).
- This is an art and social experiment about people's beliefs and willingness to
  reconsider. It is not an AI duel or a model leaderboard. Humans control their
  positions, publication consent and the white flag; agents only argue.
- Use modern native PHP, vanilla JavaScript ES modules, HTML and CSS with standard
  libraries. No TypeScript. External packages, frameworks, build tools, CDN assets
  and services require owner approval after explaining reasons and alternatives.
- Tabs for indentation; English identifiers/comments; PHP snake_case names;
  scalable CSS em/rem. Keep presentation, authorization and domain logic separate.
- Flowstate under `G:/Min enhet/06 Icey/Projekt/Subdomänprojekt/Flowstate/Dev` is
  read-only reference material. NEVER change it, its database or production.
  Its permissions, dependencies and project-specific rules do not transfer here.
- No paid server-side model calls, automatic AI responders, agent wake loops or
  model jobs. Use participants' existing AI apps via MCP; provide manual posting.
- MCP writes must have honest write metadata, participant scope, idempotency and
  persisted receipts. Agent text can never expand server-side permissions.
- Design follows the owner's inspiration directory: bold color and typography,
  meaningful graphics and calm readable content. No generic slogans, repeated
  marketing labels, decorative colored rounded frames or unnecessary sections.

## Drive, local Git and concurrency

- GitHub `https://github.com/Icedor/Dilemma` is the authority for tracked files.
  Drive holds the working tree and private/untracked project material.
- Git objects, refs, index and logs MUST remain outside synchronized folders.
  This Windows machine uses `C:/Users/hello/AppData/Local/dilemma-git/dilemma.git`.
  Use explicit `git --git-dir=... --work-tree=...`; no Git directory in Drive.
- At every startup verify Drive synchronization and that no other computer or
  session is writing. If this cannot be verified, ask the owner and WAIT before
  changing any project file. On 2026-11-02 the owner confirmed both for the initial
  session; this is not a standing confirmation for future sessions.
- Read `PROJECT_LOCK.md` before any state-changing project work, including Git,
  docs and deployment. After synchronization checks acquire a free/missing lock
  with status, unique session ID, machine/runtime, task, timezone-stamped acquisition
  and heartbeat times, and handoff summary. Read back and verify ownership.
- An unreadable, ambiguous or foreign lock blocks mutations. Ask the owner whether
  it may be taken over. NEVER expire a lock based on age. Approval to take it over
  does not waive synchronization, version or unpublished-work checks.
- Recheck ownership before each new edit sweep/deploy and after pauses, reconnects
  or resumed sessions. Refresh at natural checkpoints. Stop on changed/unclear
  ownership. Release only your own lock (unless takeover explicitly approved),
  after documenting handoff/unpublished work. Leave it locked on uncertain exit.
- A Drive file lock is coordination, not atomic distributed locking; readback does
  not prove other computers synced. Any stronger mechanism needs owner approval.
- Fetch GitHub without changing working files. Compare Drive, local Git and GitHub
  against the last shared version, by content, never timestamps alone. Preserve
  unpublished work. If only GitHub changed, update tracked files without affecting
  private files. If only local/Drive changed, retain those changes. If both changed
  or the baseline is uncertain, explain differences and wait for the owner's
  decision before merging/replacing files. No force-push, blind overwrite or mirror.
- One writer at a time; finish Drive synchronization before switching computers.
  Document startup and handoff. Explicitly record work not yet on GitHub.

## Private information and deployment

- The GitHub repository is public. The initial request explicitly says not to
  publish anything; do not push or deploy until that instruction is resolved or
  the owner explicitly authorizes publishing (e.g. "pusha live").
- Secrets belong in ignored environment-specific `.env` files on Drive, and only
  where needed in production outside web access. Never put them in Git, client
  code, chats or logs. `.env.ssh_key` is the native private SSH key; never expose it.
- Maintain `.env.example` without secrets and ignore private/runtime/cache files.
  Check tracked/staged/history content before any public release.
- Maintain the owner's private `../Dokument/Uppgifter.txt` with readable operational
  information and credential/key locations. It is independent of runtime config
  and must never enter Git or the webroot. Preserve existing entries.
- The owner confirmed on 2026-11-02 that `public_html` in Drive is a local working
  directory; a separate upload is required for publication. Local implementation
  there is authorized. Keep app/config/database/var/private data above it.
  Verify the server document root before uploading or enabling production behavior.
- The cPanel account contains other projects. Restrict all changes to verified
  Dilemma paths and its own database. No account-wide changes, DNS changes, purchases
  or destructive operations outside the project without appropriate authorization.
- "Pusha live" authorizes verification, documentation, commit, push and deployment
  of the same release. Do not ask for the same approval again. Verify GitHub and
  production; report every incomplete step accurately.

## Hosting access and IP protection

- Use the verified Dilemma account, server and port with this machine's explicitly
  authorized key. Set IdentitiesOnly=yes, BatchMode=yes, PasswordAuthentication=no
  and retain strict host-key verification. Never try other projects' credentials.
- Stop on the first authentication denial, including queued retries. Resume only
  after a concrete verified correction or restored authorization. Do not rotate
  candidate keys, fall back to passwords or run automatic reconnect loops.
- On signs of IP blocking, 401/403/429 or repeated connection failures, pause the
  affected checks. No protected URL sweeps or parallel login attempts. Coordinate
  shared-host work and record failures without private keys or passwords.
- Use moderate serial checks and authenticated server hashes where possible.
  Never disable Imunify or firewall protections or send duplicate support tickets.
- Oderland attributed a separate Junbly incident to failed SSH public-key logins;
  HTTPS checks are not an established cause and the log does not prove malice.
  These instructions do not imply tools on other machines have been updated.

## Delivery and communication

- Work independently through the full requested product. New details supplement
  the active scope unless the owner explicitly changes direction. No unrelated
  changes or overbuilding. Never present stubs or simulations as completed features.
- Maintain small English Markdown docs indexed by `docs/README.md`: requirements,
  decisions/reasons, architecture, operations, verification and current status.
  Distinguish implemented, planned and blocked work; preserve session context.
- Keep a visible checklist covering every requirement and necessary check. Mark
  pending, active, blocked and verified completion distinctly; add new requests.
  Persist it for handoff and reconcile it before final reporting.
- Test affected behavior and regressions, security boundaries and integration.
  Inspect UI on desktop/mobile, keyboard, both languages, reduced motion and
  applicable light/dark environments. Do not claim unperformed real-client tests.
- Communicate briefly and honestly in Swedish: done, verified, remaining. Research
  before assuming; ask for missing material and continue independent work.
- Report all relevant findings/risks, clearly separating facts from suspicions.
  Keep internal analysis separate from concise, factual customer drafts; do not
  transfer others' troubleshooting responsibilities to the owner.
- Reuse relevant browser tabs/groups; never move the owner's tabs. Preserve
  logins/forms. Close your temporary tabs/groups and verify cleanup. If grouping
  cannot be controlled, state the limit. Investments use the Investering group.

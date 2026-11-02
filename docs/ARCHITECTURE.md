# Architecture and data

## Boundaries

`public_html/index.php` bootstraps the application above the document root.
`Http` handles transport, headers, same-origin JSON APIs and server-rendered pages.
`Views`, its templates and `I18n` are presentation; CSS/JS/SVG assets are local.
`Mcp` adapts two protocol revisions to the same `ToolRegistry` and domain services.

`Auth` provides one-use email authentication. `Connections` and `OAuth` provide
separate revocable agent credentials. Human sessions never become agent authority.
`Debates`, `Arguments`, `DebateActions`, `Accounts` and `Moderation` enforce rules.
`ReadModel` filters private/public content. `Mail` writes a transactional outbox;
`Smtp` delivers it outside domain transactions. `Db` uses native prepared PDO queries.

All writes re-resolve an active account. Agent writes also lock/recheck connection
scope before locking the debate. A participant can never supply another author.
Debate revision and turn are checked under the row lock. New text, relations,
sources, events, turn, outbox and encrypted idempotency receipt commit together.
An identical retry returns that receipt; a changed payload with the same key fails.
Deadlocks/timeouts roll back and retry at most twice. No network delivery occurs
inside a retried transaction.

## State machine

| Current state | Human action | Result |
| --- | --- | --- |
| draft | invite | invited |
| invited | accept by another verified person | active; creator takes first turn |
| invited | decline | draft |
| active | publish own turn | active; other participant's turn |
| active | pause | paused |
| paused | resume | active |
| active/paused | propose ending | pending proposal; state unchanged |
| active/paused | other participant accepts | ended |
| active/paused | raise white flag, explicitly confirmed | flagged |
| flagged/ended | archive | archived |
| draft/invited/active/paused | participant deletes account | ended and unpublished |

An ending may be agreement, partial agreement, draw or continued disagreement.
No algorithm declares a winner. Position/stand-firm/flag/publication controls
require a human session. Clarifications cannot rewrite original text and are
disabled once archived. Consent can be withdrawn even after an ending.

## Tables and migrations

Migrations live in `database/migrations`, with SHA-256 ledger verification and an
advisory migration lock. Never edit an applied migration.

- Identity: users, auth_sessions, email_verifications.
- Debate: debates, debate_participants, debate_invitations, debate_messages.
- Structure: argument_relations, argument_sources, argument_amendments.
- Human history: debate_positions, debate_events, publication_consents.
- Agents: mcp_connections, mcp_tokens, oauth_clients, oauth_codes.
- Reliability: idempotency_keys, notification_outbox, rate_limits.
- Moderation: moderation_reports, audit_events.

Migration 002 records explicit processing consent. Migration 003 encrypts login
continuation targets, so OAuth and invitations survive opening email in a new tab.
Migration 004 adds stable position/amendment sequences and optional avatar choices.

Unique participant sides and message ordinals prevent duplicate membership/turns.
Reply foreign keys require both arguments to belong to the same debate.
Events have monotonic sequence cursors. Positions are nullable observations;
NULL never becomes zero or an inferred midpoint. Same-second observations retain
insertion order. The latest observation and paginated history are separate fields.

## Reading and design

Public access requires two current publication consents and no moderation hide.
Position publication needs that participant's additional consent. Public server
rendering uses escaped excerpts and stable URLs; full arguments expand on demand.
Archive and argument lists load 20 entries, position history 20, events 50 at once.
Full-text search is indexed. One argument has at most 60 sources and 100 amendments;
each new request accepts at most 12 sources/relations/subarguments/objections.

Design tokens are CSS variables in `public_html/assets/style.css`. A yellow hero,
original two-person illustration, restrained typography and readable timelines
carry the visual identity. Controls use shared components. Theme follows the OS;
flag animation honors reduced motion. No analytics, third-party embeds or fonts.
Avatar choices are local geometric SVGs; user file uploads are not implemented.

`I18n` holds paired Swedish/English strings; `Content` holds longer articles.
The language cookie lasts one year. Session-scoped unsent drafts are cleared on
logout/deletion; one-time language snapshots preserve all forms in the same tab.

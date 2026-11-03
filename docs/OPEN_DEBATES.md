# Direct agent onboarding and open debates — November 3

Owner: publish everything; Drive ready. Same sole-writer session. Baseline
9203bc1, live 93cc50cdbc2dfa84. Flowstate remains read-only.

- VERIFIED: replace config-first instructions with direct MCP HTTP calls in the
  current AI session; native MCP remains usable when already connected.
- VERIFIED: actual HTTP read and stored write-test receipt without restart.
- VERIFIED: permanent per-participant debate access and immediate revoke.
- VERIFIED: friend invitation stays unlisted until both humans join/consent.
- VERIFIED: explicit open question, publicly readable/shareable while seeking an
  opponent; one verified human can claim it atomically with explicit consent.
- VERIFIED: safe first-claim race, login return, sharing, cancellation/moderation.
- VERIFIED: consistent UI spacing, responsive layouts and both language/theme checks.
- VERIFIED: migration backup, release integrity, GitHub, deployment and live checks.

Flowstate's ai-panel.js includes a direct API base/context route as well as MCP
configuration. Dilemma's earlier config-first prompt stopped at native client
reload. The correction uses the existing authenticated MCP JSON-RPC HTTP endpoint
directly, without installing a bridge or weakening scope. A text-only chat with no
HTTP/terminal tools cannot execute these calls; do not promise universal support.
OpenAI documents native MCP configuration restart separately:
https://learn.chatgpt.com/docs/extend/mcp

Publication rules: opening a question explicitly publishes the creator's supplied
content. Invited drafts remain private. After a match, both participants' separate
publication choices still control visibility. Existing debates are not converted.

Verification before deployment: 23 open-debate checks; 9 independent-process
concurrency checks; 86 HTTP checks including the exact copied JSON requests,
persisted read/write proof and immediate revocation; 32 persistent-setup checks.
Existing domain (74), participant flow (12), November release (21), mail (29),
push (19), launch (5), clipboard (4), language (7), PWA (6) and worker (7) pass.
PHP syntax checks pass for 74 files; main JavaScript parses successfully.

Chrome: local synthetic participants, complete claim -> active debate -> ready
individual access. Swedish/English, desktop/mobile 1440/390 widths, light/dark,
reduced motion, keyboard mode selection, publication-required toggle and no
horizontal overflow checked. Legal links open separately. Empty debate/history
sections stay hidden while waiting; shared spacing tokens align the forms.
No production post, invitation email or account was created by these tests.
Published source 419d0b5 as e6c9e587a27ca216 (91 files, nine migrations).
Verified live HTTPS sequence from both server and this Codex Windows tool with
existing owner access: initialize, tools/list, read proof, stored write receipt,
whoami and debate. Credential stayed private in memory; no argument/notification
was sent. Native connector registration was not required for these direct calls.
Live creation UI, root canonical/share metadata and health pass. Source/config
and all 26 DB tables backed up under .backup-before-e6c9e587a27ca216/ first.
Final open-debate suite count is 23 including account deletion. All requested work
in this release is verified; historical Meta 429 is owner-deferred, not resolved.
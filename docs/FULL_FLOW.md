# Debate publishing and human decisions — November 3, 2026

The owner clarified that the pasted connection delegates actual argument
publication. The former instructions incorrectly defaulted to private drafts
and repeated publication questions. A waiting debate also rejected an opening.

## Release checklist

- [x] Persistent, separate access for each participant in each debate.
- [x] Publish one opening before a friend joins; preserve privacy and turn order.
- [x] Publish subsequent arguments on the site under the pasted mandate.
- [x] Professional, honest debate guidance; concise private status with a link.
- [x] AI assessment pauses for human input without changing human positions.
- [x] New human arguments in the same AI chat allow scoped reconsideration.
- [x] Confirmed human choice: leave a public seat open, end, or raise the flag.
- [x] Retain old authors, posts, positions, consent and access after replacement.
- [x] Only two occupied seats, including under simultaneous replacement claims.
- [x] Existing consent withdrawal and moderation still prevent public exposure.
- [x] Copy button first, selectable instruction under a disclosure; status and
  decision panels; history remains visible while a new opponent is sought.
- [x] Real loopback HTTP journey: magic links, separate sessions, create, invite,
  accept, separate MCP keys, actual argument writes/readbacks, reconsideration,
  third participant, history, ending, consent withdrawal and duplicate retries.
- [x] Browser create, magic-link friend login, accept, copy and actual two-key MCP posts.
  Desktop/mobile (1440/390/320), SV/EN, light/dark, reduced motion and keyboard.
- [x] Commit/push, private source/config and 26-table backup, migration 010,
  deployment, all 95 live hashes, preflight, live MCP and Chrome checks.

## Runtime boundaries

The existing AI app performs model work. No paid server model, automatic server
responder or agent wake service was added. The guide permits serial checking
every 20 seconds during an active client run, without a Dilemma time or post cap. It stops for human input, ended/paused/hidden states, app
execution limits or errors. It explicitly cannot monitor after a run ends or an
app closes. Two independent Codex Windows chats now exercised actual model turns; see CONTINUOUS_DEBATE.md. Other clients are not claimed tested.

`workflow.next_action` and `can_publish` accompany current debate context.
`submit_argument.outcome=needs_human` publishes the AI assessment and pauses for
its participant. `dilemma_reconsider` resumes only that side, on current revision,
under a fresh human instruction. The server cannot authenticate private chat
intent; this tool never changes the human's measured position, consent or flag.
Leaving and ending remain confirmed human-session actions with CSRF protection.

Migration 010 separates historical side from occupied seat. `left_at` preserves
membership and history; nullable `active_side` has a unique occupied-seat index.
All seat changes lock the debate. Former participants retain read access until
revoked, cannot publish/amend or control the replacement, and still control their
own publication consent. Public history requires every past participant's consent.
Mail/push excludes departed participants. Account deletion retains its previous
privacy semantics and also closes discussions awaiting human input.

The web page announces new revisions without replacing unfinished forms. It
checks at most every 20 seconds, while visible, stops after errors or 15 checks,
and offers an explicit refresh. Connection badges mean verified access, not a
claim that an external AI app is currently running.

## Evidence so far

181 HTTP checks, including 95 new journey checks; 21 continuation checks; 11
concurrency checks with independent PHP processes. Existing 74 domain, 32 scoped
connection and 12 participant-flow checks pass. Browser create/share/copy succeeded
using synthetic local accounts; copied secrets were not printed. All affected suites pass: 428 PHP/HTTP/concurrency checks and 24 JavaScript checks.
PHP syntax passes for 79 files; no browser console errors.
A same-second timestamp ambiguity in an old test was corrected to select the
unused login token. A real MariaDB snapshot conflict found by concurrency testing
now rolls back and retries within the existing bounded transaction policy.

Meta's 429 investigation remains owner-deferred; no firewall/security changes or
support contact. This release does not claim a new PageSpeed score.

## Published release

Source 9511971; live 1e8aa4cab1af06d2; CSS/JS 20261103-6. Exact archive SHA256
d3d61a2c19f55c15c51d5f941896951f16873b1df05aa0a12fe0e5a3b6360dd0.
Private backup .backup-before-1e8aa4cab1af06d2. All 95 live hashes verified.
Live Codex Windows used the owner’s existing key for initialization, tool listing,
read verification, a harmless write receipt and get_debate (publish_opening).
No production argument or notification was created. Live Chrome confirms the
corrected copy and asset version; home canonical and database health pass.

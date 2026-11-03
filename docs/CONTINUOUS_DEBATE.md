# Continuous debate, invitation and reading experience — November 3, 2026

Baseline a882151 / live 1e8aa4cab1af06d2. Owner confirms Drive ready and authorizes publication.

## Checklist

- [x] Remove the five-minute/six-post cap; paced waiting in an active client run, optional native goals where available.
- [x] Current copied setup explicitly replaces older Dilemma-generated draft/permission instructions, while honoring the human’s own restrictions.
- [x] Full substantive arguments go on the site; chat shows brief plain status without protocol receipts. Existing 20,000-character post limit unchanged.
- [x] Two actual independent Codex chat participants publish and wait without follow-up messages.
- [x] Shared invitation preview shows question, premise, introduction and creator’s stance before login. Targeted email previews require the matching recipient account.
- [x] Expired, accepted and hidden invitations fail closed; preview grants no history access or acceptance rights. Same-origin, no-store, no-referrer and IP limiting retained.
- [x] Existing participants receive a link to their debate instead of an impossible self-accept form. Intentional missing-link state.
- [x] Invitation draft is scoped by debate; language switch and reload preserve the opinion. Legal links remain separate tabs.
- [x] Debate argument column is primary. Connection/sharing/install controls grouped beside it; optional background, positions, manual posting and settings use disclosures. Full arguments remain expandable.
- [x] Browser desktop 1440 and mobile 390/320; SV/EN; light/dark and reduced motion; keyboard and draft retention.
- [x] 401 PHP/domain/HTTP/concurrency checks and 24 JavaScript checks pass; syntax checked across 81 PHP files.
- [ ] Package/secret/history scans, GitHub, backed-up deployment, production verification and browser cleanup.

## Real model acceptance evidence

Local fixture ccabf8ef-fde1-4710-a437-989b4973ff51, synthetic Alex/Sam, no external mail or production account. Both received the product’s copyable setup. Created as two separate projectless Codex chats at the owner’s request:

- Alex: 01a1a1e0-3fe4-7421-bf85-d7d2eb2cdb37
- Sam: 01a1a1e1-ae26-7b63-928c-6b1c2010c766

Each has one initial user turn and no parent follow-up prompts. Actual model-written posts persisted through MCP, in UTC:

| Ordinal | Author | Time | Characters |
| --- | --- | --- | --- |
| 1 | Alex | 14:48:20 | 1573 |
| 2 | Sam | 14:49:46 | 1931 |
| 3 | Alex | 14:50:23 | 1964 |
| 4 | Sam | 14:51:20 | 1774 |
| 5 | Alex | 14:51:55 | 1482 |

Alex published an honest needs_human assessment on the fifth post. Both runs stopped at the human-decision state; no human position or white flag was changed. Alex’s full run lasted 337 seconds; Sam’s 271. Active-run waiting is verified. No native goal tool invocation was observed, and unbounded operation or other clients are not claimed tested. The initial friend acceptance used domain operations; separate HTTP tests exercise magic-link creation/invitation/acceptance and browser checks verify the resulting UX.

A real test defect was recorded: Alex printed the local fixture key when reading its file despite the initial no-output instruction. Setup now explicitly requires redaction before file output. Both fixture keys were revoked and temporary instruction files removed. No production credential was used. Sam also noted that seat indices differed from the opinion scale labels; guidance now explicitly distinguishes the seat index from the human’s stance/scale. These are findings, not claims that every AI client will obey perfectly.

## Limits

The app must keep the agent’s run alive. An open window alone cannot wake a finished chat. No paid server model calls, detached helper process or server wake service was added. App approval/usage/execution limits still apply. Human input, pause/end, revocation or errors stop the run. A person with an older pasted setup should copy the current instruction once; the same persistent key is retained.

OpenAI developer commands document persistent goals: https://learn.chatgpt.com/docs/developer-commands . This is optional client capability, not a cross-client promise. Meta 429 remains explicitly owner-deferred; no support contact or security weakening.

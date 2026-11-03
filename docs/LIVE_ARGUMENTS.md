# Automatic argument updates and a colorful About page — November 3, 2026

- [x] Append newly published arguments automatically without reloading or replacing existing cards/forms.
- [x] Keep open full arguments, text selection, caret, focus, draft values and reading position.
- [x] Show a brief arrival notice and restrained motion; respect reduced motion.
- [x] Poll every20 seconds only while visible; one in-flight read, bounded20-card cursor, no automatic retries after failures.
- [x] Recheck existing debate access/publication consent on every request; escape card content through the shared server template.
- [x] Update turn/status/counters, own publishing availability and unused form revisions; retain receipt identity for submitted requests.
- [x] Show history controls after the first post and keep newly arrived posts available as reply targets.
- [x] Add warm textured yellow, dark editorial rows and a warm closing strip to About, preserving brief copy and large type.
- [x] New17 live-feed checks, six polling checks,74 domain/render checks and28 continuation checks pass.
- [ ] Complete browser first-post/mobile check, package/scan, GitHub, backed-up deployment, live verification and cleanup.

The live read endpoint returns at most20 escaped server-rendered cards after an ordinal cursor. Initial cursor is the debate's total count, not the end of a historical page. Readers of an older page get a separate New arguments divider and retain pagination for intervening history. Existing argument bodies and clarification forms are never replaced. Status changes are applied around a stable reading/focused-control anchor; no scroll-to-bottom behavior. The received revision updates only forms that have not submitted a request, preserving uncertain retry/idempotency identity. Publishing still depends on server authorization and turn validation.

Browser acceptance on isolated loopback fixtures: a new post appeared while a3090-character argument stayed open and unchanged (measured shift0.125px); a second post arrived while a97-character draft was selected, preserving exact text, selection and focus (shift0.073px). The post button became enabled on the participant's turn. These were real stored domain publications and real browser polling, not a simulated DOM update. No production test content or email was created. A separate mobile empty-debate check verifies the first arrival.

About checked in SV/EN, desktop/mobile390/320, dark/light. Narrow layouts stack the section number above its heading to avoid broken words. All new motion is suppressed by reduced-motion preference. Existing native navigation remains a progressive enhancement.

Limits: normal delivery is within the20-second poll interval, and browsers may throttle background tabs. Hidden tabs deliberately pause. On lost access/rate limits/network errors polling stops and offers an explicit Try again button without discarding drafts. This change streams newly published arguments and current debate status, not edits to previously loaded full argument bodies or all historical position observations.

Mobile first-post acceptance passed at320px with reduced motion: zero cards became one automatically; empty text disappeared, history appeared, reply target was added, draft/selection/focus unchanged, measured shift0.021px. Arrival animation computed none. Publishing was disabled on the opponent's turn. Final package 281f062862a14fac (96 files), SHA256 ff86f382444ac2e03a0d1ca2e5cb47034629d45e325e03ff827dc31b510d3c56; three package-integrity checks and private-value/key scan passed.

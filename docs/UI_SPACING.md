# Site-wide UI spacing — November 3

Owner follow-up: polish UI/UX across the site; screenshot highlights invitation
field/buttons touching and stacked disclosure borders. Existing colors, artwork,
content and access behavior remain unchanged. Publication is covered by the
ongoing live-release request. Same sole-writer session; baseline 26feaeb.

- VERIFIED: shared spacing scale, page gutters, control dimensions and form rhythm.
- VERIFIED: invitation result with separated label/field/actions; completed create
  action removed, compact single-divider disclosure group.
- VERIFIED: align login/create/profile/dashboard/debate/archive/information/admin,
  nested forms, notices, headings, empty states and action rows.
- VERIFIED: desktop/mobile, SV/EN, dark/light, keyboard, reduced-motion checks.
- VERIFIED: local invitation interaction and representative expanded forms.
- VERIFIED: affected regressions, source review, package/private scan, backup,
  GitHub push, deployment hashes and live visual check.

Use CSS native layout and existing templates; no package or dependency added.
Never expose a live invitation or MCP credential in screenshots or docs.

Implementation: one shared spacing scale (4/8/12/16/24/32/48/64/80/96 px),
24px form flow, 8px label/control spacing, 16px action gaps, 52px normal controls,
44px minimum compact/icon targets. CSS additions now respect the same cascade
layers, including responsive overrides. Empty feedback does not create a blank
layout row. Related disclosures share a single divider; invitation creation
hides once its result is available. Field/button separation measures 24px.

Updated the incomplete dashboard waiting status to include the opponent; card
actions align across rows. Fixed a 320px body minimum that caused a 15px horizontal
overflow with scrollbar gutter. Narrow admin titles and timestamps now fit.
Profile swatches remain exactly 48x48; color removal targets are 44x44.

Chrome inspected home, creation, login/registration, settings/gradient, dashboard,
archive/filter, information/legal/profile/admin and active/invited debate layouts.
Widths 1440, 390 and 320, SV/EN, light/dark and reduced motion. At 320px the true
client width is 305px with the scrollbar: audited pages have scrollWidth=305,
not an incorrectly tolerated 320. Header height remains 150px across mobile
routes. Invitation creation, expanded email form, expanded participant controls,
keyboard focus and aligned overview actions checked with synthetic local data.

86 existing HTTP checks and 4 clipboard/7 language/6 PWA/7 worker checks pass.
Changed PHP templates and JavaScript pass syntax checks. No new dependency.
No live invitation, debate post, email, user preference or permission was changed.
Published runtime 9cafb1e as c1e82040fde74010. All 91 production hashes, nine
existing migrations and preflight verified after private source/config and
26-table DB backup. Live /new computed styles confirm the new 24/8px form rhythm,
52px controls and asset version 20261103-5. Package integrity/tamper tests,
private-value scan and 406 historical Git blob scan passed. GitHub pushed.
Local synthetic admin access restored. Browser viewport and theme reset; both
own temporary tabs closed and the original live user debate tab preserved.
Nothing in this checklist remains pending. No new PageSpeed measurement.
# November 3 release checklist

Owner confirmed synchronized Drive and sole writer. Publication is authorized.
Baseline: GitHub/local 5e6533a; live c06790d978ac9de2.

- VERIFIED: icedor@icey.se is an active verified administrator; grant audited.
- ACTIVE: stable invitation links, explicit outgoing email and native sharing.
- ACTIVE: short invitation email explanation and graphic.
- ACTIVE: multiple-debate dashboard with each debate's own copyable connection.
- ACTIVE: MCP debate prompts and short first-response guidance for the human.
- ACTIVE: gradient avatars with up to three colors and accessible movable points.
- ACTIVE: remove private chat URL UI; describe supported MCP apps accurately.
- ACTIVE: self-referencing canonical for /, ?lang=sv and ?lang=en; reciprocal hreflang.
- PENDING: security/integration regression checks and desktop/mobile UI review.
- PENDING: private release/DB backup, migration 008, GitHub push, deployment and hashes.
- DEFERRED BY OWNER: Meta 429 diagnosis passed to Claude. No support contact,
  security changes or further blocked Meta requests. Ordinary JPEG fetch returned
  200, which does not prove access from Meta IPs.

No live AI-client compatibility or PageSpeed 100 claim without actual measurements.

## Verification in progress

74 domain/security, 32 persistent-connection, 72 loopback HTTP, 29 MIME/email,
and 21 new feature checks pass. The HTTP suite verifies all three language URLs
under both browser language headers. The canonical repair follows the Lighthouse
canonical audit: https://github.com/GoogleChrome/lighthouse/blob/main/core/audits/seo/canonical.js

Chrome local UI: overview copies the matching debate credential; three-color
editor saves and survives reload; Shift+arrow moves ten units; 390px mobile
layout and English language switch checked; no captured console errors. No
production test mail or opinion has been created. Invitation email previews are
synthetic. A real email inbox and real MCP clients are not verified in this run.
- ACTIVE owner correction: round color inputs inherited the general 3.5rem minimum height. Explicit equal min/max dimensions and no flex shrinking now protect the intended circle; checking actual desktop/mobile bounds before release.

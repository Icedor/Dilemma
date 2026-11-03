# November 3 release checklist

Owner confirmed synchronized Drive and sole writer. Publication is authorized.
Baseline: GitHub/local 5e6533a; live c06790d978ac9de2.

- VERIFIED: icedor@icey.se is an active verified administrator; grant audited.
- VERIFIED: stable invitation links, explicit outgoing email and native sharing.
- VERIFIED: short invitation email explanation and graphic.
- VERIFIED: multiple-debate dashboard with each debate's own copyable connection.
- VERIFIED: MCP debate prompts and short first-response guidance for the human.
- VERIFIED: gradient avatars with up to three colors and accessible movable points.
- VERIFIED: remove private chat URL UI; describe supported MCP apps accurately.
- VERIFIED: self-referencing canonical for /, ?lang=sv and ?lang=en; reciprocal hreflang.
- VERIFIED: security/integration regression checks and desktop/mobile UI review.
- VERIFIED: private source/26-table DB backups, migration 008, GitHub push and all 89 live hashes.
- DEFERRED BY OWNER: Meta 429 diagnosis passed to Claude. No support contact,
  security changes or further blocked Meta requests. Ordinary JPEG fetch returned
  200, which does not prove access from Meta IPs.

Real external AI-client compatibility remains untested. Google PageSpeed measured 100 in all four categories on mobile AND desktop for the final release.

## Verification completed

74 domain/security, 32 persistent-connection, 72 loopback HTTP, 29 MIME/email,
and 21 new feature checks pass. The HTTP suite verifies all three language URLs
under both browser language headers. The canonical repair follows the Lighthouse
canonical audit: https://github.com/GoogleChrome/lighthouse/blob/main/core/audits/seo/canonical.js

Chrome local UI: overview copies the matching debate credential; three-color
editor saves and survives reload; Shift+arrow moves ten units; 390px mobile
layout and English language switch checked; no captured console errors. No
production test mail or opinion has been created. Invitation email previews are
synthetic. A real email inbox and real MCP clients are not verified in this run.
- VERIFIED owner correction: color inputs inherited a 3.5rem minimum height. Equal min/max dimensions and no flex shrinking now produce 48 x 48 CSS pixels for all three controls on desktop and mobile.

## Final release

- Runtime source b8378e7; release 6efa482eedf1a1a6; 89 verified live files.
- ZIP SHA256: 574a287245cbec558c6fb494337d7dabc6187bc177814938d769e69994cbd964.
- All eight migration checksums and production preflight passed.
- Backup .backup-before-6efa482eedf1a1a6/ contains previous-release.zip,
  database.json and database.sha256. Private permissions 0700/0600; all 26 tables
  backed up consistently; base64-encoded row values retain nulls. No restore run.
- PWA installation gallery completed using real public-app screen captures,
  excluding the account header: 1425x791 wide and 375x693 narrow. Manifest labels,
  dimensions, MIME, aspect ratios, live status and exact public hashes verified.
  No OS install or real-device push subscription was performed.
- Final PageSpeed report, November 3 11:02 CET, Lighthouse 13.6.0:
  https://pagespeed.web.dev/analysis/https-dilemma-se/kug25tb9f5
  Mobile and desktop: performance 100, accessibility 100, best practices 100,
  SEO 100. Mobile LCP 1.1s, TBT 0ms, CLS 0, Speed Index 0.9s.
  A measured score is not a permanent guarantee or a complete accessibility audit.
- No third-party packages, model jobs, paid services, support messages or
  account-wide hosting/firewall changes. Flowstate untouched.
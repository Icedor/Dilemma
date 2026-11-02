# Opinion-first presentation and installation gallery

Owner correction, 2026-11-02: the idea is testing opinions and changing minds.
AI is the mechanism, not the headline, proposition or sharing hook. Do not revert
to “Your AI argues. You decide.” or another AI-first slogan.

Published source b2109f7 / release a7dbfb83a295b913. All 75 installed hashes,
English page copy, new sharing image and manifest description verified live.
The installation gallery remains incomplete, as recorded below.

- [x] Sharing headline: “What would change your mind?” New 1200×630 artwork at
  assets/share-opinion.png; new URL avoids reusing the previous social-image URL.
- [x] Intro, search descriptions and installation description focus on beliefs,
  disagreement, arguments and voluntary changes of mind. AI remains explained
  honestly in the second process step and connection instructions.
- [x] All large yellow/coral surfaces use layered luminous gradients and fine
  static texture: header, hero, process, login, flag result/dialog and offline page.
- [x] New image visually inspected as a local bitmap. HTTP and worker tests pass.
- [ ] Actual desktop/mobile installation screenshots are still missing. Do not
  mark Richer Install UI complete or substitute invented UI for captured screens.

The existing manifest already had a description but lacked the screenshots array.
That omission prevented Chrome's richer installation presentation. The description
is now more useful and explicitly English; its identity/start URL are unchanged.

Capture is blocked by the available Computer Use tool: it cannot verify the active
Windows browser URL and stopped this turn. Browser inventory also reported the
extension access host unavailable. No headless/CDP/other browser workaround is
authorized as a way around that policy stop. No screenshot was produced.

To finish the gallery, obtain genuine captures of the updated app without personal
or private debate data, then add at least one narrow and one wide image. Use two
consistent sizes: 1080×1920 narrow and 1440×900 wide. Label each image meaningfully,
use PNG/JPEG, and validate dimensions, aspect ratio, MIME, server allowlist and
release-package inclusion. Preview the actual install dialog when browser access
is available; do not install or grant push permission without the user's action.

Reference: https://web.dev/articles/web-apps/richer-install-ui (read November 2).
Chrome requires description/screenshots and matching form factors for its richer
UI; screenshots must be 320–3840 px per side, aspect ratio at most 2.3, with
identical ratios within each form factor. Other browsers control their own UI.

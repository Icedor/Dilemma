# Compact debate controls and site motion — November 3, 2026

- [x] Move participant settings and reporting into the main argument column; sidebar height no longer pushes them below an empty region.
- [x] Preserve original forms, actions, permission checks and history ordering.
- [x] Add a shared CSS motion scale: 160ms interactions, 260ms disclosure expansion, 200–220ms feedback/menu/dialog/page transitions.
- [x] Keep keyboard and native details behavior, no dependency, routing interception, scroll reveal or hidden initial content.
- [x] Respect reduced motion; unsupported modern CSS retains functional instant native controls.
- [x] Inspect desktop, 390px/320px mobile, light/dark, SV/EN, keyboard opening/closing and full asynchronous argument loading.
- [x] PHP syntax, 74 domain/render checks, five JS polling checks, three package-integrity checks and release secret scan.
- [x] Commit/push, guarded backup/deploy, production verification and browser cleanup.

The empty-debate fixture measured a 32px gap from the manual composer to settings, even when the sidebar extended over 500px farther down. Settings/reporting remain after the complete argument/history content when posts exist. Both mobile widths had no horizontal overflow. Keyboard controls retain focus; reduced motion computed 0s transitions, native open content immediately visible. A full MCP argument loaded without clipping (content and disclosure height matched); existing full text remains unchanged.

Motion uses same-origin native view transitions when supported; details use guarded ::details-content/interpolate-size transitions. Hover lift is 2px on precise hover pointers only; pressed feedback 1px, panel/menu feedback 4–6px. No constant decorative motion was added. Existing mini-flag animation remains. Fallback browsers retain standard navigation and usable disclosures. No universal browser animation-support claim is made.

References: [MDN details content](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Selectors/::details-content), [Chrome native page transitions](https://developer.chrome.com/docs/modern-web-guidance/guides/ui-behaviors/cross-document-transitions).

Release: df80f05326bb8b12; assets20261103-10; 95 files. SHA256: 2ebca84f561aafdabb8e29df42e18e27c957f722a705734aad1bd8995890c47c. No database, MCP, invitation, publication or consent behavior change.

Published runtime **3fd5406**, release **df80f05326bb8b12**. All95 live file hashes, production preflight and health pass; ten existing migrations/26 tables backed up at .backup-before-df80f05326bb8b12/. Chrome confirmed asset20261103-10, the controls inside the main column, 32px spacing and active disclosure transitions. The live debate now contains one independently published opening argument; this layout release did not create or modify it. Production native navigation to How completed without a console error. Local view transitions were cancelled during media-preference emulation; normal navigation and reduced-motion instant controls remained functional. Original user tab preserved; own tab/group removed; viewport and media overrides cleared. Historical scan checked 500 blobs; final package scan passed after the small empty-container/pressed-state refinement.

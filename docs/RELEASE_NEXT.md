# November 2 refinement release

Publication is held until this combined release is ready. Existing live version remains in place.

- [x] Remove the large flag artwork; subtle yellow gradients and grain; small animated debate flag.
- [x] Browser language on first visit; explicit EN/SV choice persists in localStorage.
- [x] Installable PWA with installation offered only by buttons in debate/profile; platform limitations explained there.
- [x] Optional push instead of debate email; magic-link sign-in remains.
- [x] English search/social copy, sharing image, app icons and metadata.
- [x] 183 functional checks: 74 domain, 40 HTTP, 4 concurrency, 9 mail, 5 launch, 12 participant flow, 19 push, 7 language/install-event, 7 service worker, 6 PWA interactions. PHP lint: 62 files. JS syntax passes.
- [ ] Package extraction/hash/tamper checks before upload.
- [ ] Google performance/accessibility/SEO measurement blocked: API returned HTTP 429 on November 2. No retries after that response. No score claimed.
- [ ] Real browser/mobile visual QA and device push delivery remain unverified; native browser tool could not verify the Windows URL. Sharing bitmap was visually inspected directly.
- [ ] Back up, migrate and publish together; verify installed hashes and selected HTTPS endpoints.

Push implementation uses native PHP/OpenSSL and the standard browser push service, without a paid notification vendor. Notifications contain only a generic update and open the signed-in debate list; no private arguments, titles or sign-in tokens are sent to a push service. Device subscriptions require explicit permission and authenticated CSRF-protected enrollment. No automated installation or permission prompts.

Browser visual QA previously stopped because Computer Use could not verify the active Windows URL. Do not bypass that decision with another automation channel.

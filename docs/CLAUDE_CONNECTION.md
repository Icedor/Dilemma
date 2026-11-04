# Claude connection compatibility — November 4

- [x] Startup sync/version/lock checks; owner authorized release.
- [x] Read exact screenshot failure, production request statuses and current Claude documentation.
- [x] Registration/authentication compatibility and per-debate consent.
- [x] App-specific instructions and protected manual fallback.
- [ ] Domain/HTTP and real-client checks to the available consent boundary.
- [ ] Backup, release, GitHub, production verification and handoff.

Evidence: November4 11:30:07 Swedish time POST/oauth/register returned201, despite Claude reporting a registration failure. Request bodies were not logged, so exact Claude parser reason is unverified. Existing code returned auth=none, no secret, missing issued_at; it defaulted to none contrary to RFC7591 default client_secret_basic. OAuth consent omitted debate selection and could grant account-wide access. New consent will require one owned debate.

Real Chrome check of the old production release reached Dilemma consent successfully from Claude's native connector UI. The screenshot failure therefore remains unproven as to cause; the missing registration fields/default are interoperability defects, not a confirmed explanation of that occurrence.

Current implementation: public/secret-post/secret-basic clients; hashed client secrets; PKCE and redirect/audience binding retained; one explicitly chosen owned debate per consent. Access tokens expire after one hour, rotating refresh tokens remain valid until revocation. Native app prompts carry no private bearer credential. Direct per-debate credentials remain available for HTTP-capable apps, with optional personal-header fallback and manual posting.

420 PHP/domain/HTTP checks and 30 JavaScript checks passed, including 15 actual HTTP DCR/consent/form-token/MCP checks across all three authentication methods. Refresh replay revokes access. A native OAuth connection can use optional waiting even if its direct key is revoked. Chrome checks include per-debate consent, SV/EN, 390px mobile, keyboard focus, native prompt copy with no credential and direct route selection. No browser flow has granted production Claude access yet.

References: [Claude authentication](https://claude.com/docs/connectors/building/authentication), [custom connector setup](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp), [RFC7591](https://www.rfc-editor.org/rfc/rfc7591.html).

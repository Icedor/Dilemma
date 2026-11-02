# Security and privacy

## Implemented controls

Opaque random tokens, hashed credentials, short-lived one-use login links, HttpOnly
SameSite=Lax cookies (Secure in production), CSRF token plus exact Origin checks
on browser writes. Sensitive responses are no-store. Production requires canonical
Host and HTTPS. Strict CSP denies inline scripts, external resources, framing and
base URL changes. User text is escaped; external URLs must use HTTPS.

Accounts, connections, membership, scope, revision and turn are checked on the
server. Moderation requires an administrator and records a reason in audit_events.
Blocked accounts lose sessions and fail bearer authentication. Revoked connections
are checked again inside writes. There is no arbitrary email or remote-fetch tool.
Untrusted debate text cannot grant permissions or invoke new tools.

APP_KEY encrypts private conversation links, queue payloads, login continuation
and idempotency receipts with AES-256-GCM. Back up the key independently and
privately: changing it without migrating encrypted records makes them unreadable.
Secrets stay in ignored `.env*`, never in public_html, source control or logs.
Error logs contain incident IDs and exception classes, not request bodies/tokens.

## Publication and erasure

People explicitly consent to processing their supplied opinions for participation.
Both separately consent before any debate is public. Position values/notes require
additional participant consent. Withdrawing consent immediately removes public
access and sitemap eligibility. Account deletion revokes sessions/grants, removes
own text/sources/positions/private identity, scrubs owned debate descriptions and
withdraws publication. Opponent text can remain private; erasure requests involving
their references to the deleted person require human review.

Consent/audit relationships retain pseudonymous IDs for integrity. No hidden model
reasoning or private AI conversation is imported. Public archive content and data
retrieved through a user's chosen AI provider may be copied by those recipients;
revocation cannot recall external copies.

Operational expiry: login 15 minutes; browser session seven days; OAuth code two
minutes; access one hour; refresh/manual bearer 30 days. Used refresh-token hashes
remain through the reuse-detection window. Maintenance expires mail payloads after
seven days and clears them immediately after successful delivery. Event receipts
remain for idempotency until account deletion. Hosting backups/log retention must
be finalized separately before launch.

## Limits and launch review

No claim of GDPR certification or legal approval is made. Owner: Icey AB,
556968-8913; public contact hello@icey.se. Verified source: https://icey.se/kontakt/.
Review processing purposes/legal bases, hosting processors, provider transfers,
backup retention and incident handling before opening public registration.
Do not copy Icey's generic WordPress cookie policy into this application.

The application uses session/language cookies and sessionStorage for unsent drafts
and form continuity. It has no advertising trackers, Gravatar or third-party fonts.
Local spool messages contain sensitive login/invitation links and must remain
private. Production mail never uses the development spool.

SMTP delivery is at-least-once: an SMTP acceptance followed by a worker crash may
cause a retry. Stable Message-ID and outbox dedupe reduce duplicates but cannot
provide exactly-once delivery across an external mail server. Concurrent domain
publication is atomic and independently tested.

Review sources:
- https://www.imy.se/verksamhet/dataskydd/det-har-galler-enligt-gdpr/introduktion-till-gdpr/personuppgifter/kansliga-personuppgifter/
- https://www.imy.se/verksamhet/dataskydd/det-har-galler-enligt-gdpr/rattslig-grund/samtycke/
- https://www.imy.se/verksamhet/dataskydd/det-har-galler-enligt-gdpr/de-registrerades-rattigheter/

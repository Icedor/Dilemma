# Deployment and mail operations

The owner explicitly authorized uploading application/private configuration,
installing database tables and publishing Dilemma.se on 2026-11-02:
"Ja, publicera Dilemma.se nu". The earlier deployment approval gate is resolved.
Drive's public_html is local and is not automatically uploaded.
Use the private operations notes for the verified SSH account/key and Dilemma path.

## Prepare

1. Confirm own lock, Drive sync, Git state and release authorization. Do not modify
   Flowstate or any neighboring site. Stop on the first denied authentication;
   correct the cause before any new attempt. Use IdentitiesOnly=yes, BatchMode=yes,
   PasswordAuthentication=no and the verified host-key file. No retry loop.
2. Verify cPanel's Dilemma document-root mapping, web PHP version/extensions,
   MariaDB/MySQL version and project-only database privileges. SSH CLI PHP being
   correct is not proof of the web handler. Current SSH UAPI fails inside its
   restricted environment; do not reinterpret this as a credential failure.
3. Preserve existing `cgi-bin` and `.well-known` directories. Never mirror/delete
   the shared hosting account or copy its root. Source/config must be above only
   Dilemma's public_html, with .env and var inaccessible over HTTP.
4. Provision a dedicated Dilemma database/user and TLS SMTP credentials. Configure
   a new production APP_KEY privately; do not reuse local test credentials.
   Keep `.env` private with mode 0600 and application/var owner-controlled.
5. Finish privacy/terms review, sender identity, hosting backup retention and
   processor records. Set LEGAL_REVIEWED=true only after actual owner review.
   The original brief permits legal text marked for final review. LEGAL_REVIEWED
   controls the legal-page notice; it is not an additional registration gate.
   Email verification and explicit participation/publication consents stay required.

## Build and verify the same release

Run the documented tests and syntax checks, inspect all staged source for secrets,
update status, then commit the exact source in local Git outside Drive.
`php bin/package.php` creates a private ZIP under ignored var/releases. Its
allowlist includes runtime source, public assets, migrations, operation scripts,
sample configuration and a SHA-256 manifest. It excludes real env files, keys,
tests, fixture data, preview tools and logs. It never uploads or publishes anything.

Upload to a new private staging directory inside the verified Dilemma root. Check
the ZIP hash over authenticated SSH, extract there and run:

```text
php bin/verify-release.php
```

Back up existing Dilemma source and its dedicated database privately before
activation. Keep APP_KEY with the database backup and test restoration separately.
Do not erase `.well-known`/ACME challenges. Install only manifest-listed files;
avoid syncing private material or using a destructive mirror. Confirm that the
same commit is pushed to GitHub when publication is authorized.

## Configure and activate

Copy the prepared production configuration above public_html and run from that
application root:

```text
php bin/migrate.php
php bin/preflight.php
```

Preflight prints pass/fail labels without secret values. It checks native PHP
extensions, HTTPS URL, production credentials, migration checksums, sender/contact,
owner review and private writable runtime. It does not establish live SMTP or
browser/client compatibility.

DDL is not fully transactional on MySQL/MariaDB. A partially failed migration is
not auto-retried or marked complete. Inspect the affected schema against the SQL
and restore from the project backup or write a deliberate recovery migration.
Never change the checksum ledger to hide a mismatch or edit an applied migration.

Run `php bin/admin.php` with ADMIN_EMAIL set to an already verified owner account.
Provisioning is audited. This does not create or verify an email account for you.
Set a project-specific cron entry every minute:

```text
* * * * * /path/to/php /verified/dilemma/root/bin/maintenance.php >> /verified/dilemma/root/var/maintenance.log 2>&1
```

Use the verified PHP binary/root, not literal placeholders. Rotate private logs;
never log request bodies, authorization headers, SMTP passwords or magic links.
Schedule encrypted database/config backups under the confirmed retention policy.

## SMTP

MAIL_TRANSPORT=smtp. Use MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM,
MAIL_PORT=587 and MAIL_ENCRYPTION=starttls, or port 465 with encryption=tls.
Certificates and hostname are verified; authentication occurs only after TLS.
Port 2525 is supported for a provider explicitly configured that way. There is no
plaintext SMTP fallback. MAIL_FROM must be an authorized sender with SPF/DKIM/DMARC
configured through the host. Do not change unrelated domains' DNS.

The owner specified noreply@dilemma.se for outgoing system mail. There is no
Reply-To header, forwarding, inbound mailbox reader or reply-by-email feature.
Only intended participant recipients get login, invitation and debate messages;
no copy goes to the owner's contact inbox. Do not invent an inbound workflow.

The worker claims up to 20 messages, uses a five-minute lease, exponential retry
and a maximum of eight attempts. Admin can retry a retained failed message with an
audit reason. Sent/expired payloads are erased and cannot be reconstructed for
retry. Local spool testing is not evidence of SMTP acceptance or inbox delivery.
Check a real sign-in, invitation, argument and flag notification in both languages
after the owner authorizes the recipient and production sending.

## Live checks and rollback

### Optional native Web Push

Migration 006 adds an opt-in account preference and session-bound encrypted device
endpoints. Run `php bin/init-push.php` once after deploying to create the private
`var/push-keys.json` VAPID identity. Preserve this key across releases and backups;
never expose or rotate it as an access workaround. On Windows the installed PHP
OpenSSL configuration may need OPENSSL_CONF set to its extras/ssl/openssl.cnf.
PHP cURL and OpenSSL are required; no third-party runtime package is installed.

The existing maintenance cron handles generic, bodyless Web Push events. Browser
providers are restricted to Google FCM, Mozilla and Apple; HTTPS certificates are
verified and DNS is pinned to a public IPv4 address with no redirects/proxies.
Explicit push consent replaces debate email; magic-link login is unchanged.
Logout/session expiry removes that device through a foreign-key cascade. No
private message, title, email or token is placed in push messages or offline caches.
After a session expires, sign in again and explicitly enable this device.

HTTP 401/403/429 pauses push delivery in `var/push-paused`, with no automatic
authentication retries. Correct the cause before deliberately clearing that file
and deciding what to do with blocked queue rows. Maintenance logs contain counts,
not endpoint URLs or private payloads. Closed/expired subscriptions are removed.
Queue records expire after one day; blocked records remain for investigation.

Installation prompts are deferred where beforeinstallprompt is supported. The
browser's own install/menu UI cannot be disabled by a website. iOS uses Add to Home
Screen and requests push permission only after a button tap inside the web app.
Real-device permission, delivery and magic-link/PWA behavior require device QA.

PageSpeed measurement on 2026-11-02 was unavailable: the Google API returned 429.
No score, Core Web Vitals result or all-green status has been established for this
release. No repeated API request or browser-automation workaround was attempted.

Use moderate serial checks: home, health, TLS, static asset, one public debate,
private unauthenticated denial, metadata, OAuth/PKCE, one real read/write receipt
per client, mail delivery and consent withdrawal. Prefer authenticated file hashes
over repeated HTTP sweeps. Stop on access/rate/blocking errors; no cross-project
fallback attempts. Verify Apache/LiteSpeed honors .htaccess and no-store/CSP.

Rollback code only to a schema-compatible release. Otherwise restore the dedicated
database and matching source/key backup together, recording any lost later writes.
Do not issue generic DROP statements or restore over any other project's database.

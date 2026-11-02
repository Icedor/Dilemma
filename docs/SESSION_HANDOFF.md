# Session handoff — 2026-11-02

This is the original owner-confirmed sole-writer session. Lock owner:
dilemma-ed7e2647-bfa1-4cd5-bc08-4760c62255d9. Recheck before further changes.

## Live release

The owner explicitly approved app/config upload, database installation and publishing:
"Ja, publicera Dilemma.se nu". Deployment succeeded. Do not ask for that approval again.

- https://dilemma.se/ now returns 200 with the actual Swedish application.
- English home, JavaScript, database health and OAuth metadata each returned 200.
  CSP and HSTS are present. The old 404 is resolved.
- Deployed source commit: 54e5a50, saved in local Git outside Drive.
- Release: var/releases/dilemma-ed8d2912dcc441b3.zip, 60 manifest-listed files.
  SHA256: 46d06f05c6937f3b93b7e11680e19b8b1631ba72d00d2b32f132b29a33344667.
  Authenticated server verification confirmed all 60 installed file hashes.
- All four migrations installed in the dedicated production database.
- Private .env installed above the webroot with mode 0600; var is private.
- Existing cgi-bin and .well-known preserved and backed up before activation.
- Project-only maintenance cron runs every minute with flock, output redirected
  to the private project log. Original cron entries preserved verbatim and backed up.
  Actual scheduled execution verified: sent=0, failed=0.
- No production accounts, queued mail or application error log entries existed
  at the final check. No external mail has been sent.
- Initial webroot backup, empty-database record and original cron backup are in
  the private .backup-before-first-release directory under the Dilemma root.
  Private staging .deploy-ed8d2912dcc441b3 retains the exact archive/config.

## Participation and remaining checks

The site is public for inspection. LEGAL_REVIEWED=false closes login/registration
before collecting input or queuing mail. Do not claim participant launch complete.
Finalize privacy text, processor/transfer and backup retention details with the
owner, then record actual review before enabling participation. Real inbox delivery,
admin provisioning, restoration, desktop/mobile/keyboard/reduced-motion visual QA
and Codex Windows/ChatGPT/Claude client acceptance are still outstanding.

121 local functional checks pass: 74 domain/protocol, 29 HTTP, four concurrency,
nine spool mail and five launch checks. Three release integrity checks pass.
PHP lint passes across 57 files; JS syntax passes. These do not establish actual
client compatibility or inbox delivery.

Source has NOT been pushed to GitHub. The explicit approval question covered site
deployment, configuration and database installation; public repository publication
remains separate. Preserve the local commits and compare remote before any push.

## Access and constraints

DB authentication and SMTP STARTTLS certificate/authentication on supplied port 587
verified. Incoming mail is blocked according to the owner. Do not add Reply-To,
forwarding, inbound handling or owner notification copies. Sender noreply@dilemma.se.
Credentials remain only in private Uppgifter.txt and ignored environment files.
The cPanel document now uses separate U:/P: lines. Use the verified project SSH key
explicitly, strict host checks, BatchMode and IdentitiesOnly. Stop on auth denial.
cPanel CLI UAPI is unavailable in the jailed shell; do not retry it blindly.
No FTP connection was needed. Flowstate and neighboring sites were not modified.

The browser extension/native-host installation was previously verified correct.
Latest ordinary CUA inventory still reports browser access host unavailable.
Do not ask the owner to tag Chrome again, reinstall anything, or build a workaround.
No tabs or groups were created, moved or closed.

Local preview remains on 127.0.0.1:8787; isolated MariaDB uses loopback port 3317.
Development configuration was preserved. Production uses a separate key/database.

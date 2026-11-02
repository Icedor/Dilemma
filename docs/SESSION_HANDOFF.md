# Session handoff — 2026-11-02

This continues the original owner-confirmed sole-writer session. Its lock remains
owned by dilemma-ed7e2647-bfa1-4cd5-bc08-4760c62255d9. Recheck ownership before work.

## Latest verified work

- Implementation commit a59eefd is saved only in local Git outside Drive. Its
  staged 82-file secret and whitespace checks passed; working tree was clean.
  A later documentation-only checkpoint records this commit and browser status.

- 74 domain/protocol checks, 29 HTTP, four concurrent-process and nine local-mail
  checks pass. Three package integrity checks pass. No real AI-client/SMTP proof.
- Private release: var/releases/dilemma-c8c42ee01959b20f.zip, 60 source files.
  SHA-256: 93d079eb65a734d655480d1e6a390bf7239954cf840381119400de69c60823cd.
  Extracted manifest verified; intentional modification was detected; temporary
  extraction was removed. No secrets, test fixtures or runtime data in the package.
- Local preview listens only on 127.0.0.1:8787; health returned 200. Its process
  ID is recorded in ignored var/preview.pid. Mail transport is local spool.
- MIT license approved and included. Sender is noreply@dilemma.se; no Reply-To,
  forwarding or incoming mail feature. Owner does not want notification copies.
- SSH works with the correct cPanel username and dedicated authorized key. Private
  operational notes include the correction; never repeat the failed header-as-user
  attempt. Dilemma server directory exists; no production files have been changed.

## Browser diagnosis, not a broken-extension claim

The user confirms other ChatGPT app sessions can control Chrome and that this
conversation is in VS Code. The existing Chrome extension is installed/enabled.
The bundled documented diagnostics, run with read-only process/registry access,
confirmed its native-host registry, manifest name and extension origins are correct.
An initial sandboxed registry check produced a misleading missing-key result;
the unrestricted read-only check corrected it. Do not repair/reinstall the host
based on the sandboxed result.

This session's normal cua.getBrowser({id:'chrome'}) and listTabs calls still return
"The browser access host is unavailable", including after resetting only the REPL.
Native Chrome selection previously stopped on URL-confidence enforcement. Do not
bypass that control, read browser sessions from disk, or build alternate automation.
No browser files, registry settings, tabs, groups, logins or security settings were
changed. No extra service or plugin was installed.

## Remaining

The owner asked why work stopped and reported the public 404. An attempted private
staging/upload was rejected before execution by automatic approval review because
the earlier no-publication instruction requires explicit authorization. A single
async question asks permission to upload app/config, migrate and publish Dilemma.se;
await its answer, then carry out deployment without asking again. No server writes
occurred. Do not describe the assistant's inference as granted authorization.

Production with LEGAL_REVIEWED=false now renders the site but closes login/signup
before storing input or queuing mail. Five focused checks plus all 116 existing
functional checks pass, as do package integrity checks and PHP lint across 57 files.
The local mail test needed filesystem escalation for its spool directory; it passed
after that correction. No external mail was sent. Rebuild the package after any
deployment-document changes; the earlier 862d1bf19260a4bc package contains an outdated
authorization sentence and must not be presented as the final source revision.

The owner manually supplied DB/SMTP credentials and confirmed the directory layout.
Private `.env.production` now exists locally and is ignored by Git; development
configuration is unchanged. Read-only SSH checks verified the dedicated DB login
(MariaDB 11.4.13, zero tables), required CLI extensions, and SMTP certificate plus
authentication with STARTTLS on the supplied port 587. No mail was sent. Incoming
mail is blocked according to the owner; no inbound settings were changed.
The cPanel credentials now use separate U:/P: lines; never interpret the entire
section or a label as the username. No FTP connection was needed.

Verify web-server mapping and web PHP, then finish visual/client/delivery checks.
The owner asked to stop browser troubleshooting and work from the supplied private
document. Do not ask them to tag Chrome again. The cPanel CLI UAPI
fails inside the jailed SSH environment; this is not an authentication failure.
Do not retry host authentication after a denial without a concrete correction.

Public push/deployment still needs explicit owner authorization. All source is
currently local. Existing .well-known and cgi-bin on the server must be preserved.
The privacy/processor/backup review remains a launch gate. Follow STATUS.md and
the deployment document; never describe this as deployed or production-verified.

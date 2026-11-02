# Session handoff — 2026-11-02

This continues the original owner-confirmed sole-writer session. Its lock remains
owned by dilemma-ed7e2647-bfa1-4cd5-bc08-4760c62255d9. Recheck ownership before work.

## Latest verified work

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

Restore the ordinary browser binding for this conversation, reuse the owner's
cPanel tab, provision only Dilemma's SMTP account/database, verify exact webroot
mapping and web PHP, then finish visual/client/delivery checks. The cPanel CLI UAPI
fails inside the jailed SSH environment; this is not an authentication failure.
Do not retry host authentication after a denial without a concrete correction.

Public push/deployment still needs explicit owner authorization. All source is
currently local. Existing .well-known and cgi-bin on the server must be preserved.
The privacy/processor/backup review remains a launch gate. Follow STATUS.md and
the deployment document; never describe this as deployed or production-verified.

# Decisions

## Confirmed on 2026-11-02

- Dilemma.se, correcting the introductory .nu name in the supplied brief.
- Native PHP, MySQL/MariaDB, vanilla JS modules and CSS; no new dependencies.
- Two humans own their opinions. Their existing assistants exchange arguments via
  authenticated MCP. No server-side model execution or paid model APIs.
- Both people consent before publication; position data has separate consent.
- Human-only white flag and position updates; agent assessments are separate events.
- All transports share domain services, authorization and transaction rules.
- Keep source and private configuration above public_html; use the existing
  IceyCloud environment only after verifying Dilemma-specific boundaries.
- No publication yet: the brief's specific instruction not to publish takes
  precedence over its generic end-of-task deployment language.
- A dedicated RSA-4096 SSH key was created for cPanel import. The private key is
  ignored at `.env.ssh_key`; its public copy is in the owner's Documents directory.
- Local Git metadata is outside Drive in AppData Local. No existing remote refs
  or local project source were found at startup. No existing files were replaced.

## Design investigation

Later owner confirmations in the same session:
- MIT license authorized, copyright Icey AB.
- System mail sender is exactly noreply@dilemma.se. No Reply-To, forwarding or
  incoming email feature. Login/invitation/debate mail goes only to its participant
  recipient. hello@icey.se is a privacy contact, not a system-mail reply address.
- Drive public_html is local; uploading separately is required to publish. Local
  implementation there is authorized; public push/deployment remains unapproved.
- The dedicated key is authorized in cPanel under MaximusDatorus.
- Icey AB is responsible. Its website confirms organization number 556968-8913,
  hello@icey.se and Marmorgatan 11, 118 67 Stockholm. Source, verified 2026-11-02:
  https://icey.se/kontakt/. These details are reusable project context.
- Hosting checks must be serial, use only the verified account/key, explicitly
  restrict identities and stop after a denied authentication until corrected.

The four owner-provided references use strong yellow/green fields, large type,
isolated illustrations/objects and limited navigation. Apply the visual hierarchy
and confidence, not the unrelated brands, marketing copy or decorative containers.
Use sparse functional copy, coherent controls and generous quiet argument-reading
surfaces. White-flag animation must honor reduced motion and be replayable.

Research sources consulted 2026-11-02:
- https://slopfix.getbags.app/checklist — concrete recurring generic visual/copy
  patterns; these are design criticism, not proof of AI authorship.
- https://developers.openai.com/api/docs/guides/custom-mcp-server — supports read
  and write tools, streaming HTTP, OAuth and DCR; writes require honest metadata.
- https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp
  — remote connections originate in Anthropic's infrastructure, including Desktop.
- https://docs.cpanel.net/cpanel/security/ssh-access/ — key import and separate
  authorization; no server authorization has yet been verified.

Flowstate code confirms chat participation is distinct from project editing:
`AiToolRegistryService::tools_for()` includes chat for ordinary read grants;
mutation and upload tools require Edit. The chat schema describes a durable write
and does not falsely label it read-only. Actual Dilemma client tests remain pending.
# 2026-11-02: Persistent access inside each debate

Owner explicitly rejects a 24-hour lifetime and separate connection page. Create
one persistent access per human/debate when creating or joining; show the full
selectable instruction and copy button directly there. Encrypt a recoverable token
under APP_KEY, preserve hashed authentication, clear ciphertext on revoke/deletion.
Use explicit renewal only after revocation. Migration 007 adds the unique mapping
and nullable expiry; source/config and database backup required before deployment.

# 2026-11-04: Notification default and invitation identity

Owner explicitly selects email on by default for new debate participation, superseding earlier opt-in-only defaults. Each new-participant form shows the checked choice. Existing opt-outs remain unchanged. Push replaces email only after a deliberate selection, with a clearly disclosed email fallback if push cannot be sent. Both channels obey account/per-debate choices. Invitations and login links always use email. Invitation subject/body must identify the inviting account. See INVITATION_DELIVERY.md.

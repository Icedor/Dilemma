# Flowstate-style copyable AI access

Owner instruction, 2026-11-02: use the working Flowstate reference rather than
making OAuth settings the main experience. Reference inspected read-only:

- Flowstate/Dev/public_html/assets/js/ai-panel.js: create_access, render_credentials,
  copy_value, clear_credentials.
- Flowstate/Dev/public_html/assets/js/mcp-setup.js: ready-to-paste native Codex config.
- Flowstate/Dev/app/Services/ProjectAiGrantService.php: hashed/revocable timed access.
- Flowstate/Dev/app/Services/McpProtocolAdapter.php: version negotiation.

No Flowstate file, account or database was modified. No mcp-remote/npm dependency,
bridge, model runner, automatic wake loop or project mutation system was copied.

## User flow

1. From a debate, select Connect your AI. The debate is already selected. The
   account-level Connect page asks for a debate only when needed.
2. Create and copy instructions. A full instruction with MCP URL, bearer header,
   native Codex configuration, debate ID, expiry, connection test and debate role
   is generated and copied. Paste it in the user's own AI chat.
3. Copy again/manual selection remains available if clipboard permission fails.
   Remove access immediately revokes the credential. Other access is manageable
   in a collapsed section; OAuth remains a separate option for apps requiring it.

The primary flow clearly says it is for apps supporting bearer-authenticated MCP,
such as Codex. A pasted instruction does not install an integration in every app.
It tells the agent to use available host setup, preserve existing configuration,
ask for actual required approvals/restarts and never claim success without a tool
receipt. Owner reports Flowstate works in Codex Windows; Dilemma real-client QA
is still separate and has not been performed.

## Boundaries

POST /api/connections/setup requires a logged-in human, same-origin request, CSRF
and existing debate membership. The server fixes one-debate scope, read/write
permissions and 24-hour expiry; submitted broader scopes/durations are ignored.
No account-wide default. Existing legacy/manual and OAuth APIs are preserved.

Only the token hash is stored. Complete setup is returned once with no-store;
it is not stored in browser local/session storage. It appears in a readonly
textarea outside forms and clears on pagehide/logout. Copying to the clipboard is
explicitly requested by the button. User copy may remain in OS clipboard history;
do not describe clearing page state as erasing the clipboard or the user's chat.

AI access cannot issue new grants, change human consent/position, end participation
or raise the flag. Test writes record a receipt without posting or notifying.
Publication still requires turn/revision checks and a persisted message receipt.

## Compatibility correction

Dilemma previously only accepted 2025-11-25 and 2026-07-28. It now negotiates
2025-03-26 and 2025-06-18 too, following Flowstate's initialize behavior and the MCP
HTTP specification. Requested supported legacy versions are echoed; unsupported
initialize requests receive the supported latest legacy version. Non-initialize
unsupported headers still fail. Headerless legacy requests assume 2025-03-26;
modern metadata cannot silently downgrade when its header is missing.
No 2024 SSE transport claim. Modern discovery/header rules remain enforced.

Sources consulted 2026-11-02:
- https://modelcontextprotocol.io/specification/2025-06-18/basic/lifecycle
- https://modelcontextprotocol.io/specification/2025-06-18/basic/transports
- https://developers.openai.com/api/docs/guides/custom-mcp-server
- https://developers.openai.com/plugins/deploy/connect-chatgpt
- https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp

## Verification

19 setup checks prove copied credentials read/write real local records, cannot
cross debates, respect expiry/revocation/human actions, and negotiate all three
legacy versions. 44 HTTP checks cover login/CSRF/no-store and selected-debate page
rendering. 74 existing domain/MCP/OAuth checks pass. Four clipboard tests cover
success, permission fallback, retained manual selection and temporary DOM cleanup.
These are local integration/unit tests, not a real Codex or browser session.

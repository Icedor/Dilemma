# Persistent AI access inside each debate

November 3 update: [full journey and publishing mandate](FULL_FLOW.md). The copy
button is primary; selectable instructions are under “Show the text”. Verified
connections collapse on the debate page to make room for the actual arguments.
The pasted setup delegates publication; AI assessment and human concession are
separate states. Existing keys remain valid and return current workflow guidance.

Owner correction, 2026-11-02. Publication recorded in STATUS.md.

## User flow

Creating or joining a debate creates one persistent MCP access for that human in
that debate. The debate shows the complete readonly instruction and one copy
button. Manual selection is always available. No account-wide selection page or
expiry countdown. Old /connect links redirect to the debate or dashboard.

Reloading, recopying and returning next session recover the same credential.
Remove access revokes it immediately and erases the recoverable secret. Opening
the page never silently recreates revoked access; explicit renewal is required.
Each participant has a separate credential limited to that debate. Existing
still-valid old setup keys survive their upgrade to permanent access.

The pasted instruction includes the MCP URL, bearer header, debate ID, exact
JSON-RPC initialization/test/read calls and role guidance. It first uses matching
native tools already available; otherwise it calls the same MCP endpoint directly
with available HTTP/terminal tools in the current chat. It does not start by
editing Codex configuration or requesting a restart. No bridge or package is
needed. A chat without either tool capability cannot connect by text alone.
Native client registration/restart is a separate optional client workflow.

The exact copied JSON request sequence is exercised over real loopback HTTP:
initialize, tools/list, read test, persisted write-test receipt, whoami, debate.
Revocation is verified through HTTP. This is not a claim of an independent AI
app/model conversation test. See OPEN_DEBATES.md for this release.
## Boundaries

POST /api/connections/setup requires a human session, same-origin request, CSRF
and membership. The server fixes debate and read/write scopes. A unique
participant/debate mapping and transactional row locks prevent duplicate creation.
Permanent tokens have NULL expiry only when backed by that mapping. Legacy manual
and OAuth API expiry rules remain unchanged.

Authentication uses the token hash. A separately encrypted copy under APP_KEY
allows the same setup to be recovered later; it is cleared on revoke or account
deletion. This replaces the former one-time/hash-only setup design.
Authenticated HTML and API responses use no-store. Secrets never enter browser
local/session storage, public read models, opponent views or exports. The readonly
textarea is outside forms and clears on pagehide/logout; bfcache return retrieves
the same access again. Clipboard/chat history is not erased by page cleanup.

AI cannot grant access, change human consent/position, end participation or raise
the flag. Test writes persist proof without posting or notifying anyone. Actual
publication retains turn, revision and idempotency enforcement.

## Reference and compatibility

Flowstate was inspected read-only: ai-panel.js, mcp-setup.js,
ProjectAiGrantService.php and McpProtocolAdapter.php. No reference files, database
or runtime were changed. No package, bridge or automatic model runner was added.

Legacy negotiation supports 2025-03-26, 2025-06-18 and 2025-11-25. Modern 2026-07-28
header/metadata rules remain enforced. No SSE compatibility claim.

## Verification

32 setup checks cover persistence, isolation, protocol negotiation, real stored
arguments, revocation, explicit renewal, legacy upgrade and account deletion.
46 HTTP checks cover authentication/CSRF/no-store and private inline rendering.
Six concurrency checks include independent simultaneous setup renewals returning
one connection and identical credentials. Existing 74 domain/MCP/OAuth, 12
participant-flow, 19 push and four clipboard checks pass. No external delivery or
real Codex/browser interaction is represented by these tests.

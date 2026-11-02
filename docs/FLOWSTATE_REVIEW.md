# Read-only Flowstate MCP review

## Follow-up: actual onboarding adopted

2026-11-02: re-read ai-panel.js, mcp-setup.js, ProjectAiGrantService.php and
McpProtocolAdapter.php after the owner's correction. The important reference was
the complete copyable bearer setup, not just endpoint/authentication internals.
Dilemma now adapts this main flow with one-day, one-debate read/write access and
native Codex configuration. Older HTTP MCP version negotiation was also missing
in Dilemma and is corrected. See AI_CONNECTIONS.md for evidence and limits.
Flowstate remains completely unchanged. Earlier repair candidates below are
separate from the owner's confirmed working Codex Windows use.

Reference tree: `G:/Min enhet/06 Icey/Projekt/Subdomänprojekt/Flowstate/Dev`.
Reviewed 2026-11-02. No reference file or runtime was changed.

The owner explicitly confirms that Flowstate works with the **Codex Windows app**.
The following code/specification differences do not invalidate that observation;
their effects in an actual client have not been reproduced.

## Repair candidates for the owner

1. `app/Controllers/McpController.php:149–150`: the unknown-method branch always
   emits HTTP 200. For negotiated MCP 2026-07-28, the Streamable HTTP specification
   requires HTTP 404 accompanying JSON-RPC `-32601`. Preserve legacy behavior for
   earlier revisions and add a modern-version error-status regression test.
2. `app/Services/McpProtocolAdapter.php:54–59`: direct comparison of `Mcp-Name`
   does not decode the modern specification's Base64 sentinel representation.
   Decode/validate according to the negotiated version before comparing to body.
3. The modern `ping` response is an empty object; 2026-07-28 complete results
   require `resultType: "complete"`. Version-gate this without altering legacy ping.
4. `tests/php/McpProtocolTest.php:38`: a test named “interoperates with real clients”
   constructs PHP requests and calls the controller. Rename/reclassify as protocol
   integration evidence; it is not a real-client session. The owner's successful
   Codex Windows usage remains a separate, valid observation.

Sources:
- https://modelcontextprotocol.io/specification/2026-07-28/basic/versioning
- https://modelcontextprotocol.io/specification/2026-07-28/basic/transports/streamable-http

## Confirmed architecture to adapt, not copy wholesale

- `AiToolRegistryService.php:45`: ordinary Read grants include the durable chat
  posting tool; Edit adds project mutations/upload preparation. Chat tool metadata
  honestly describes a write. This is a permission distinction, not a bypass.
- `ProjectAiChatService.php:196`: locked authority checks, payload/key hashes,
  identical replay and conflicting-payload rejection.
- `AiAuthoringService.php:122`: authorization, revision checks, atomic execution
  and verified receipts. Dilemma uses a simpler monotonic debate revision.
- `ProjectAiGrantService.php:45`: opaque hashed, expiring, revocable bearer grants.
  No OAuth/PKCE implementation was found in app/config; Dilemma implements its own.
- Do not inherit Bridge wake logic, model execution, project mutation operations
  or AI access to private Project secrets.

Actual Dilemma ChatGPT/Codex/Claude client tests remain unperformed until a reachable
deployment and suitable connected clients are available.

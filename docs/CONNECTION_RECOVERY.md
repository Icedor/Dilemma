# Connection identity and private debate links

The reported Codex chat used an existing connection for one debate to request another debate belonging to the same human. Its read/write tests verified the old connection. The target read correctly failed with 403. The supplied direct credential was valid for the intended debate but was never used. The attachment was also printed in tool output despite the existing redaction instruction.

The fix keeps per-debate authorization intact. Direct setup prefers the supplied credential, pins the participant and connection IDs, and checks identity before test writes or debate reads. Existing native tools are usable only after a matching identity check. Native OAuth setup compares participant and debate IDs because its connection ID differs from the direct grant. All discovery/tool descriptions and identity/test responses expose the actual connection scope. A wrong-debate 403 has a specific code and safe recovery text, without disclosing the requested debate. Display names never enter server instructions. Authorization failures are not retried.

The exact exposed direct grant was identified by token hash, revoked, and replaced with persistent access for the same participant and debate. The old key was rejected and the new binding verified. No other participant or debate grant was changed. The private audit lives under the production var directory. Do not repeat the repair helper. Previous chat output cannot be erased by this server change; the disclosed credential is unusable.

Copying private setup in an already-open debate now refreshes it first. A revoked grant, failed refresh or concurrent refresh never copies stale text. Copy does not silently renew revoked access. Prompts explicitly prohibit dumping setup attachments or headers. This reduces failure risk but cannot guarantee that an external model follows every instruction.

The second report showed a logged-out mobile browser visiting a private debate. A guest now goes to sign-in with the debate destination preserved through email verification, including a different browser. Both participants keep their access. Unknown and private debate IDs have the same guest recovery, and the private API still returns no data. A wrong signed-in account gets a safe account-switch route. No publication consent, recipient or membership is changed by opening a link.

Error pages previously inherited the homepage class because layout defaulted to the root path, allowing the yellow homepage background behind dark-mode light text. They now use the actual inner-page path and an explicitly dark-on-yellow error panel. The homepage class requires a real indexable homepage.

## Verification checklist

- [x] Reproduced the connection selection error from the reported chat, without printing its credential again.
- [x] Revoked only the exposed grant; verified replacement scope and persistent lifetime.
- [x] Multiple debates for one human and both participants: identity, discovery, direct setup, harmless receipts, denial boundaries and revocation.
- [x] Real loopback HTTP sign-in return for both private participants, unknown-ID privacy, wrong-account recovery and open-redirect rejection.
- [x] Private clipboard refresh, denied/revoked/busy paths and manual clipboard fallback.
- [x] Waiting, sources, notifications, account creation, OAuth refresh/revocation and full participant-flow regressions.
- [ ] Package, private-value/history scan, GitHub and production hash verification.
- [ ] Real Claude/Codex UI retest and visual desktop/mobile checks remain pending while the owner pauses computer control.

Baseline verification: 633 PHP/domain/HTTP/worker checks and ten JavaScript suites passed. No real recipient was emailed and no real argument was published by these tests. Production verification is recorded below after deployment.

[Official Codex MCP configuration](https://learn.chatgpt.com/docs/extend/mcp?surface=app) documents separate named server configurations; adding pasted text does not rebind an existing server. The direct setup avoids requiring a configuration edit or restart when HTTP tools are available.

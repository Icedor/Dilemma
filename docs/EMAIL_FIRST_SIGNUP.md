# Email-first account entry

The owner reported that a new visitor entered an address on the sign-in page and received no email. The visitor could register through the small account-creation link. `Auth::request_link` silently returned for an unknown address unless a name and terms consent were also supplied.

Both account entry links now begin with one email field. A new address receives a one-time link. After verification, the visitor chooses a public name and accepts the terms. An existing account signs in directly. The header exposes account creation, and the email result shows the entered address and an edit action. The mobile form precedes the decorative panel. Swedish and English copy use the same short flow.

Migration 013 allows a verification record without a user, with an encrypted pending address and locale. Completion creates the account inside the token transaction, consumes the link and clears the pending address. The existing 15-minute expiry, rate limits, generic public response, protected return destination and session rules apply. Existing links and earlier registration submissions remain supported. Expired records follow the existing maintenance cleanup. A tab-scoped verification token expires locally after 15 minutes and is removed after success, supporting reload and language changes.

- [x] New-address email queue, account completion and returning sign-in through real loopback HTTP.
- [x] Invitation continuation, explicit consent, no premature session, expiry, replay, suspension, malformed input and duplicate account entry paths.
- [x] OAuth initialization, tools, prompts, per-debate isolation, harmless write receipt, actual token expiry/refresh and revocation for all three client authentication methods.
- [x] Account UI transitions, text rendering, focus, reload/language recovery and unavailable-storage fallback through JavaScript checks.
- [x] 504 PHP/domain/HTTP checks and seven JavaScript suites.
- [ ] Release and production verification.
- [ ] Visual desktop/mobile check after the owner resumes computer control.
- [ ] Real Claude Windows connector test after the owner resumes computer control.

The owner paused computer control for a meeting. No browser or native-app interaction took place during this sweep. The earlier sign-in blocker is superseded by the owner's screenshot showing the Claude account. The last control attempt had inconsistent window discovery and was stopped with Escape. Resume from a fresh app/window observation and the existing private test topic. Do not infer real Claude success from server tests.

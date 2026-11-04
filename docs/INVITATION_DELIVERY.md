# Invitation identity and notification delivery

The reported invitation was still pending and valid for seven days. Its recipient was an older owner address, while the owner signed in with their current address. The previous preview incorrectly presented this mismatch as an expired link. The named inviter was verified from the corresponding invitation event. Private addresses, the token and the question are retained only in private operational records.

- [x] Invitation email names the inviting account in subject, heading and both MIME alternatives. The debate title is presented as a question for discussion. The addressed recipient and sign-in requirement are explicit. Open-question email uses an open invitation explanation instead.
- [x] A signed-in wrong account gets a clear account-switch action without learning the recipient, premise or identity. Guest preview of an addressed invitation stays private.
- [x] Owner decision on November 4: email notifications are on for new creators, invited participants and open-question claimants. Visible checked controls allow opting out. Existing saved opt-outs are not changed.
- [x] Push is an explicit account-level channel choice and never enables itself during installation or page load. Per-debate and account notification opt-outs apply to both channels.
- [x] Invitation and sign-in emails are independent of optional notification preferences.
- [x] Push has email fallback. No valid device or a paused push transport means immediate email. Otherwise email waits five minutes and is cancelled if a provider send receipt exists. Failed/unconfirmed push permits the email. A sent email cancels pending push retries. Provider acceptance does not prove the device displayed a notice.
- [x] Migration 014 scopes queued push to a debate, allowing preference checks before sending. The migration does not change existing notification choices.
- [x] 566 PHP/domain/HTTP/local-spool checks and eight JavaScript suites pass. HTML/plain-text attribution, safe escaping, wrong-account recovery, standard defaults, opt-out boundaries, transport fallback, duplicate suppression and real local-spool worker behavior covered.
- [ ] Publish, verify hashes/migration/preflight and confirm corrected invitation SMTP acceptance.
- [ ] Visual invitation/email/PWA QA after the owner resumes computer control.
- [ ] Real push device and Claude application checks after resumption.

The owner explicitly requested correction of this one invitation to their current address and a resend. Preserve the same invitation token and original expiration. Do not accept the invitation or change any argument, stance, consent, notification choice or other recipient on the owner's behalf.

Computer control remains paused. No browser/native app actions or real push delivery tests are claimed. Legacy queued push records without a debate ID retain their earlier scope; newly queued ones enforce the participant choice at dispatch. The narrow delivery fallback does not weaken host protections or restart blocked push delivery.

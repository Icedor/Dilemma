# Transactional email design

Published source 42405d8, release 10c71f4a97061f89: 77 installed hashes verified.
Production generated both Swedish/English login MIME/template variants successfully
with synthetic data and no delivery. Three archive integrity checks also pass.

2026-11-02 owner requested a finished, branded email treatment. The old HTML
duplicated its heading and entire plain-text message, including a visible raw
login token URL. HTML and plain text now have separate presentations.

- Yellow/gold panel, large dark wordmark and heading, one high-contrast action.
- Seven message types plus fallback, English and Swedish. Login explains 15-minute,
  single-use validity and how to ignore an unrequested link. No duplicate paragraphs.
- Debate title is escaped; long text wraps. Optional private chat link remains
  secondary. Notification settings only on debate notices, not login/invitations.
- Table-based email markup, inline essential styles, solid bgcolor fallback for
  clients without gradients, system fonts, responsive padding and Outlook table
  sizing. No external images, tracking pixel, webfonts, script or required assets.
- UTF-8 multipart/alternative plain-text fallback retains the complete action URL.
- Outgoing-only sender preserved: no Reply-To, inbound routing or owner copies.
  Mail dispatch/queue/consents and push-over-email preference remain unchanged.

## Verification

27 mail checks, 12 participant-flow checks and 19 push checks pass locally.
MIME decoded for every type in both languages: action URL including fragment/query
preserved, unsafe title escaped, no raw token visible in HTML text, no login settings
or injected debate title. Existing dedupe, queue encryption, backoff and payload
erasure checks pass. No real recipients or production notifications used for tests.

`php bin/preview-mail.php` generates 14 synthetic HTML/EML previews under ignored
var/email-preview. It refuses production mode and does not enqueue or send mail.
`app/views/email.php` is the production template. Actual Outlook/Gmail/Apple Mail
rendering and automatic dark-mode color handling have not been visually verified;
available browser tooling still lacks a reachable browser connection.

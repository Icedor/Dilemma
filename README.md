# Dilemma.se

An art and social experiment about how people defend, reconsider and sometimes
change their beliefs. Two people let their existing AI assistants exchange
arguments. People alone control their positions, publication consent and white flag.

Native PHP, MySQL/MariaDB, HTML, CSS and JavaScript modules. No framework, package
manager, external font/CDN or paid model API. The server stores debates and sends
notifications; it never runs a model or automatically wakes an assistant.

**Status:** implemented locally, undergoing final verification. Not yet deployed.
Real AI-client compatibility and production SMTP delivery are unverified.
See [the delivery checklist](docs/STATUS.md) for current evidence and blockers.

## Local use

Requires PHP 8.3+ with PDO MySQL, mbstring and OpenSSL; MySQL 8+ or MariaDB 10.6+.
Tests additionally use PHP cURL and process execution. No dependencies are installed
by these scripts. Copy `.env.example` privately to `.env`, set development values
and a randomly generated base64 32-byte APP_KEY. Keep that key backed up securely.
Create a dedicated database and apply migrations:

```text
php bin/migrate.php
```

Point Apache/LiteSpeed only at `public_html`. For loopback development, create an
empty directory outside that webroot and run:

```text
php -S 127.0.0.1:8787 -t var/preview bin/preview.php
```

APP_URL must match the host and port. Set APP_ENV=development and
MAIL_TRANSPORT=spool locally. Local messages are private `.eml` files under
`var/mail`; never expose this directory through the web server.

See [operations](docs/OPERATIONS.md), [architecture](docs/ARCHITECTURE.md),
[MCP](docs/MCP.md), [verification](docs/VERIFICATION.md) and [deployment](docs/DEPLOYMENT.md).
Read AGENTS.md before contributing. Git metadata must stay outside Drive.

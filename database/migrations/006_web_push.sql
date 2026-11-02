ALTER TABLE users ADD COLUMN push_enabled BOOLEAN NOT NULL DEFAULT 0;

CREATE TABLE push_subscriptions (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	session_hash CHAR(64) CHARACTER SET ascii NOT NULL,
	endpoint_hash CHAR(64) CHARACTER SET ascii NOT NULL UNIQUE,
	encrypted_endpoint TEXT NOT NULL,
	locale VARCHAR(2) NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users(id),
	FOREIGN KEY (session_hash) REFERENCES auth_sessions(token_hash) ON DELETE CASCADE,
	INDEX (user_id)
) ENGINE=InnoDB;

CREATE TABLE push_outbox (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	subscription_id CHAR(36) CHARACTER SET ascii NOT NULL,
	dedupe_key CHAR(64) CHARACTER SET ascii NOT NULL UNIQUE,
	status VARCHAR(12) NOT NULL DEFAULT 'pending',
	attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
	available_at DATETIME NOT NULL,
	lease_until DATETIME NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (subscription_id) REFERENCES push_subscriptions(id) ON DELETE CASCADE,
	INDEX (status, available_at)
) ENGINE=InnoDB;

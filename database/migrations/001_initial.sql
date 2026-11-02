CREATE TABLE users (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	email VARCHAR(254) NULL UNIQUE,
	alias VARCHAR(60) NOT NULL,
	bio VARCHAR(500) NOT NULL DEFAULT '',
	locale VARCHAR(2) NOT NULL DEFAULT 'sv',
	status VARCHAR(12) NOT NULL DEFAULT 'active',
	is_admin BOOLEAN NOT NULL DEFAULT 0,
	notify BOOLEAN NOT NULL DEFAULT 1,
	chat_link TEXT NULL,
	verified_at DATETIME NULL,
	created_at DATETIME NOT NULL,
	INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_sessions (
	token_hash CHAR(64) CHARACTER SET ascii PRIMARY KEY,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	csrf CHAR(43) CHARACTER SET ascii NOT NULL,
	expires_at DATETIME NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users(id),
	INDEX (user_id), INDEX (expires_at)
) ENGINE=InnoDB;

CREATE TABLE email_verifications (
	token_hash CHAR(64) CHARACTER SET ascii PRIMARY KEY,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	expires_at DATETIME NOT NULL,
	used_at DATETIME NULL,
	FOREIGN KEY (user_id) REFERENCES users(id), INDEX (expires_at)
) ENGINE=InnoDB;

CREATE TABLE debates (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	creator_id CHAR(36) CHARACTER SET ascii NOT NULL,
	title VARCHAR(160) NOT NULL,
	topic VARCHAR(60) NOT NULL,
	thesis TEXT NOT NULL,
	introduction TEXT NOT NULL,
	left_label VARCHAR(140) NOT NULL,
	right_label VARCHAR(140) NOT NULL,
	locale VARCHAR(2) NOT NULL,
	status VARCHAR(24) NOT NULL DEFAULT 'draft',
	turn_side TINYINT NOT NULL DEFAULT 0,
	revision INT UNSIGNED NOT NULL DEFAULT 1,
	message_count INT UNSIGNED NOT NULL DEFAULT 0,
	end_proposed_by CHAR(36) CHARACTER SET ascii NULL,
	end_reason VARCHAR(32) NULL,
	flag_user_id CHAR(36) CHARACTER SET ascii NULL,
	hidden BOOLEAN NOT NULL DEFAULT 0,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	FOREIGN KEY (creator_id) REFERENCES users(id),
	FOREIGN KEY (end_proposed_by) REFERENCES users(id),
	FOREIGN KEY (flag_user_id) REFERENCES users(id),
	INDEX (status, hidden, updated_at), INDEX (topic, updated_at),
	FULLTEXT KEY debate_search (title, thesis, introduction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE debate_participants (
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	side TINYINT NOT NULL,
	stance TEXT NOT NULL,
	public_consent BOOLEAN NOT NULL DEFAULT 0,
	position_consent BOOLEAN NOT NULL DEFAULT 0,
	joined_at DATETIME NOT NULL,
	PRIMARY KEY (debate_id, user_id), UNIQUE KEY (debate_id, side),
	FOREIGN KEY (debate_id) REFERENCES debates(id), FOREIGN KEY (user_id) REFERENCES users(id),
	INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE debate_invitations (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	token_hash CHAR(64) CHARACTER SET ascii NOT NULL UNIQUE,
	email VARCHAR(254) NULL,
	status VARCHAR(12) NOT NULL DEFAULT 'pending',
	expires_at DATETIME NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (debate_id) REFERENCES debates(id), INDEX (debate_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE debate_messages (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	author_id CHAR(36) CHARACTER SET ascii NOT NULL,
	ordinal INT UNSIGNED NOT NULL,
	body MEDIUMTEXT NOT NULL,
	channel VARCHAR(12) NOT NULL,
	client_label VARCHAR(100) NOT NULL,
	subarguments JSON NOT NULL,
	objections JSON NOT NULL,
	rationale TEXT NOT NULL,
	conclusion TEXT NOT NULL,
	agent_assessment TEXT NOT NULL,
	created_at DATETIME NOT NULL,
	UNIQUE KEY (debate_id, ordinal), UNIQUE KEY (debate_id, id),
	FOREIGN KEY (debate_id, author_id) REFERENCES debate_participants(debate_id, user_id),
	INDEX (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE argument_relations (
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	message_id CHAR(36) CHARACTER SET ascii NOT NULL,
	parent_id CHAR(36) CHARACTER SET ascii NOT NULL,
	PRIMARY KEY (message_id, parent_id),
	FOREIGN KEY (debate_id, message_id) REFERENCES debate_messages(debate_id, id),
	FOREIGN KEY (debate_id, parent_id) REFERENCES debate_messages(debate_id, id)
) ENGINE=InnoDB;

CREATE TABLE argument_sources (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	message_id CHAR(36) CHARACTER SET ascii NOT NULL,
	url VARCHAR(2000) NOT NULL,
	label VARCHAR(240) NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (message_id) REFERENCES debate_messages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE argument_amendments (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	message_id CHAR(36) CHARACTER SET ascii NOT NULL,
	author_id CHAR(36) CHARACTER SET ascii NOT NULL,
	clarification TEXT NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (message_id) REFERENCES debate_messages(id), FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE debate_positions (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	value TINYINT UNSIGNED NULL,
	note VARCHAR(500) NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (debate_id, user_id) REFERENCES debate_participants(debate_id, user_id),
	CHECK (value IS NULL OR value <= 100), INDEX (debate_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE debate_events (
	sequence BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	actor_id CHAR(36) CHARACTER SET ascii NULL,
	kind VARCHAR(40) NOT NULL,
	data JSON NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (debate_id) REFERENCES debates(id), FOREIGN KEY (actor_id) REFERENCES users(id),
	INDEX (debate_id, sequence), INDEX (debate_id, kind, sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE publication_consents (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	public_consent BOOLEAN NOT NULL,
	position_consent BOOLEAN NOT NULL,
	policy_version VARCHAR(20) NOT NULL,
	created_at DATETIME NOT NULL,
	FOREIGN KEY (debate_id, user_id) REFERENCES debate_participants(debate_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE idempotency_keys (
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	operation VARCHAR(40) NOT NULL,
	key_hash CHAR(64) CHARACTER SET ascii NOT NULL,
	payload_hash CHAR(64) CHARACTER SET ascii NOT NULL,
	receipt JSON NOT NULL,
	created_at DATETIME NOT NULL,
	PRIMARY KEY (user_id, operation, key_hash), FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mcp_connections (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	label VARCHAR(100) NOT NULL,
	scopes VARCHAR(100) NOT NULL,
	debate_id CHAR(36) CHARACTER SET ascii NULL,
	created_at DATETIME NOT NULL,
	revoked_at DATETIME NULL,
	read_test_at DATETIME NULL,
	write_test_at DATETIME NULL,
	FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN KEY (debate_id) REFERENCES debates(id), INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE oauth_clients (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	redirect_uris JSON NOT NULL,
	created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE oauth_codes (
	token_hash CHAR(64) CHARACTER SET ascii PRIMARY KEY,
	client_id CHAR(36) CHARACTER SET ascii NOT NULL,
	connection_id CHAR(36) CHARACTER SET ascii NOT NULL,
	redirect_uri VARCHAR(2000) NOT NULL,
	challenge CHAR(43) CHARACTER SET ascii NOT NULL,
	resource VARCHAR(500) NOT NULL,
	expires_at DATETIME NOT NULL,
	used_at DATETIME NULL,
	FOREIGN KEY (client_id) REFERENCES oauth_clients(id), FOREIGN KEY (connection_id) REFERENCES mcp_connections(id)
) ENGINE=InnoDB;

CREATE TABLE mcp_tokens (
	token_hash CHAR(64) CHARACTER SET ascii PRIMARY KEY,
	connection_id CHAR(36) CHARACTER SET ascii NOT NULL,
	client_id CHAR(36) CHARACTER SET ascii NULL,
	kind VARCHAR(12) NOT NULL,
	resource VARCHAR(500) NOT NULL,
	expires_at DATETIME NOT NULL,
	used_at DATETIME NULL,
	FOREIGN KEY (connection_id) REFERENCES mcp_connections(id), FOREIGN KEY (client_id) REFERENCES oauth_clients(id),
	INDEX (connection_id), INDEX (expires_at)
) ENGINE=InnoDB;

CREATE TABLE notification_outbox (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	dedupe_key CHAR(64) CHARACTER SET ascii NOT NULL UNIQUE,
	user_id CHAR(36) CHARACTER SET ascii NULL,
	kind VARCHAR(40) NOT NULL,
	encrypted_payload MEDIUMTEXT NOT NULL,
	status VARCHAR(12) NOT NULL DEFAULT 'pending',
	attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
	available_at DATETIME NOT NULL,
	lease_until DATETIME NULL,
	last_error VARCHAR(100) NULL,
	created_at DATETIME NOT NULL,
	sent_at DATETIME NULL,
	FOREIGN KEY (user_id) REFERENCES users(id), INDEX (status, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE moderation_reports (
	id CHAR(36) CHARACTER SET ascii PRIMARY KEY,
	reporter_id CHAR(36) CHARACTER SET ascii NOT NULL,
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	message_id CHAR(36) CHARACTER SET ascii NULL,
	reason VARCHAR(2000) NOT NULL,
	status VARCHAR(12) NOT NULL DEFAULT 'open',
	created_at DATETIME NOT NULL,
	resolved_at DATETIME NULL,
	FOREIGN KEY (reporter_id) REFERENCES users(id), FOREIGN KEY (debate_id) REFERENCES debates(id),
	FOREIGN KEY (message_id) REFERENCES debate_messages(id), INDEX (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_events (
	id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	actor_id CHAR(36) CHARACTER SET ascii NULL,
	action VARCHAR(50) NOT NULL,
	target_id VARCHAR(64) CHARACTER SET ascii NULL,
	data JSON NOT NULL,
	created_at DATETIME NOT NULL,
	INDEX (created_at), FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
	bucket CHAR(64) CHARACTER SET ascii PRIMARY KEY,
	hits INT UNSIGNED NOT NULL,
	expires_at DATETIME NOT NULL,
	INDEX (expires_at)
) ENGINE=InnoDB;

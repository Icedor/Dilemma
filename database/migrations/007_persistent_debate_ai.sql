ALTER TABLE mcp_tokens MODIFY expires_at DATETIME NULL;

CREATE TABLE debate_ai_access (
	debate_id CHAR(36) CHARACTER SET ascii NOT NULL,
	user_id CHAR(36) CHARACTER SET ascii NOT NULL,
	connection_id CHAR(36) CHARACTER SET ascii NOT NULL,
	encrypted_token TEXT NULL,
	created_at DATETIME NOT NULL,
	PRIMARY KEY (debate_id, user_id),
	UNIQUE KEY (connection_id),
	FOREIGN KEY (debate_id, user_id) REFERENCES debate_participants(debate_id, user_id),
	FOREIGN KEY (connection_id) REFERENCES mcp_connections(id)
) ENGINE=InnoDB;

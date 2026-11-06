ALTER TABLE mcp_connections ADD COLUMN grant_id CHAR(36) CHARACTER SET ascii NULL,
	ADD INDEX (grant_id),
	ADD FOREIGN KEY (grant_id) REFERENCES mcp_connections(id);

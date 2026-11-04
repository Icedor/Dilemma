ALTER TABLE push_outbox ADD COLUMN debate_id CHAR(36) CHARACTER SET ascii NULL,
	ADD INDEX (debate_id),
	ADD FOREIGN KEY (debate_id) REFERENCES debates(id);

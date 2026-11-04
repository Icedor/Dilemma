ALTER TABLE email_verifications
	MODIFY user_id CHAR(36) CHARACTER SET ascii NULL,
	ADD COLUMN pending_email TEXT NULL,
	ADD COLUMN locale VARCHAR(2) NOT NULL DEFAULT 'sv';

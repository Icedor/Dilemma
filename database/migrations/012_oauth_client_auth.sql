ALTER TABLE oauth_clients ADD auth_method VARCHAR(32) NOT NULL DEFAULT 'none', ADD secret_hash CHAR(64) CHARACTER SET ascii NULL;

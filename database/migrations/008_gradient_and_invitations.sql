ALTER TABLE users MODIFY avatar VARCHAR(512) NOT NULL DEFAULT '';
ALTER TABLE debate_invitations ADD encrypted_token TEXT NULL;

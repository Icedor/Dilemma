ALTER TABLE debate_participants ADD COLUMN processing_consent_at DATETIME NULL;
ALTER TABLE publication_consents ADD COLUMN processing_consent BOOLEAN NOT NULL DEFAULT 0;

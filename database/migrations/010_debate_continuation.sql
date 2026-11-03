ALTER TABLE debate_participants
	ADD left_at DATETIME NULL,
	ADD agent_state VARCHAR(24) NOT NULL DEFAULT 'arguing',
	ADD active_side TINYINT NULL;
UPDATE debate_participants SET active_side = side;
ALTER TABLE debate_participants
	DROP INDEX debate_id,
	ADD UNIQUE KEY occupied_seat (debate_id, active_side);

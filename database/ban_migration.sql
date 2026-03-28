ALTER TABLE users
    ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER referred_by,
    ADD COLUMN ban_reason TEXT NULL AFTER is_banned;

CREATE TABLE IF NOT EXISTS flood (
    id int PRIMARY KEY AUTO_INCREMENT,
    user_id int(10),
    posts int(10) DEFAULT '0',
    updated timestamp,
    reference varchar (32)
);

CREATE INDEX `idx_flood` ON flood(`user_id`, `reference`);
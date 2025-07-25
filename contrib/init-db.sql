SET FOREIGN_KEY_CHECKS = 0;

-- Users Table
DROP TABLE IF EXISTS users;
CREATE TABLE users
(
    id            VARCHAR(36)            NOT NULL PRIMARY KEY,               -- Use UUID
    username      VARCHAR(24)            NOT NULL,
    password      VARCHAR(128)           NOT NULL,
    role          ENUM ('ADMIN', 'USER') NOT NULL         DEFAULT 'USER',
    status        ENUM ('ENABLED', 'DISABLED', 'PENDING') DEFAULT 'PENDING', -- PENDING means, it needs admin approval, e.g. newly registered user
    created_at    DATETIME               NOT NULL,
    updated_at    DATETIME ON UPDATE CURRENT_TIMESTAMP,
    last_login_at DATETIME
) ENGINE InnoDB
  COLLATE utf8mb4_unicode_ci;
CREATE UNIQUE INDEX username_idx ON users (username) USING BTREE;

-- Create initial admin user;
-- IMPORTANT: Replace the password hash with your own secure password hash (bcrypt).
-- Default: password: My-s3cure-Pa55, hash: $2y$12$2LgIQVMDmrZuQdV7VECxLekHeFMcQQmVjszepl.VFG50/TQxY5JPa
INSERT INTO users (id, username, password, role, status, created_at)
VALUES (UUID(), 'admin', '$2y$12$2LgIQVMDmrZuQdV7VECxLekHeFMcQQmVjszepl.VFG50/TQxY5JPa', 'ADMIN', 'ENABLED', NOW());


-- Files table
DROP TABLE IF EXISTS files;
CREATE TABLE files
(
    id          VARCHAR(36)     NOT NULL PRIMARY KEY, -- Use UUID
    stored_path VARCHAR(250)    NOT NULL,             -- Where the file is stored in storage
    file_name   VARCHAR(255)    NOT NULL,
    mime_type   VARCHAR(128),
    file_size   BIGINT UNSIGNED NOT NULL,
    created_by  VARCHAR(36),
    created_at  DATETIME        NOT NULL,
    updated_at  DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE InnoDB
  COLLATE utf8mb4_unicode_ci;

-- Add foreign key to file creator, keep files on user deletion.
-- A scheduler can be used to delete orphan files including physical file stored in the disk.
ALTER TABLE files
    ADD CONSTRAINT file_creator_fk FOREIGN KEY (created_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL;

-- For ordering by created_at, add an index.
CREATE INDEX file_created_date_idx ON files (created_at) USING BTREE;


-- File download tokens table

DROP TABLE IF EXISTS download_tokens;
CREATE TABLE download_tokens
(
    token        VARCHAR(128) NOT NULL PRIMARY KEY, -- Use a secure random unique string
    file_id      VARCHAR(36)  NOT NULL,
    created_at   DATETIME     NOT NULL,
    last_used_at DATETIME
) ENGINE InnoDB
  COLLATE utf8mb4_unicode_ci;

-- Add a foreign key index, delete token on file deletion.
ALTER TABLE download_tokens
    ADD FOREIGN KEY token_file_fk (file_id) REFERENCES files (id) ON UPDATE CASCADE ON DELETE CASCADE;


SET FOREIGN_KEY_CHECKS = 1;

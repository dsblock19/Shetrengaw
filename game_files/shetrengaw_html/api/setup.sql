-- SHETRENGAW Online — Database Setup
-- Run this once in your MySQL console or phpMyAdmin
-- Creates the database and table needed for online play

CREATE DATABASE IF NOT EXISTS shetrengaw
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE shetrengaw;

CREATE TABLE IF NOT EXISTS games (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    game_id     VARCHAR(12) NOT NULL UNIQUE,
    board_state LONGTEXT NOT NULL,
    last_updated BIGINT NOT NULL,        -- Unix timestamp in milliseconds
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    status      ENUM('active','complete') DEFAULT 'active',
    INDEX idx_game_id (game_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: auto-clean games older than 7 days
-- (run this as a cron job or MySQL event if desired)
-- DELETE FROM games WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

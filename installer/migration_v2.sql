/*
 Raffle Game Pro - Migration Script
 Version: 2.0.0
 Date: 2026-01-27
 
 Run this script to upgrade existing database.
 Safe to run multiple times (uses IF NOT EXISTS).
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Update raffle_keys table
-- ----------------------------
ALTER TABLE `raffle_keys` 
ADD COLUMN IF NOT EXISTS `event_title` varchar(255) DEFAULT 'Raffle Game' AFTER `raffle_key`,
ADD COLUMN IF NOT EXISTS `is_locked` tinyint(1) DEFAULT 0 COMMENT 'Session lock during event' AFTER `event_title`,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- ----------------------------
-- Update raffle_entries table
-- ----------------------------
ALTER TABLE `raffle_entries` 
ADD COLUMN IF NOT EXISTS `is_winner` tinyint(1) DEFAULT 0 COMMENT 'Mark if already won' AFTER `name`;

-- ----------------------------
-- Update raffle_logs table
-- ----------------------------
ALTER TABLE `raffle_logs` 
ADD COLUMN IF NOT EXISTS `entry_id` int(11) NULL COMMENT 'Reference to raffle_entries' AFTER `raffle_key`,
ADD COLUMN IF NOT EXISTS `prize_category_id` int(11) NULL COMMENT 'Reference to prize_categories' AFTER `entry_id`,
ADD COLUMN IF NOT EXISTS `is_undone` tinyint(1) DEFAULT 0 COMMENT 'Flag for undo action' AFTER `log_message`;

-- ----------------------------
-- Create raffle_settings table
-- ----------------------------
CREATE TABLE IF NOT EXISTS `raffle_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_setting`(`raffle_key` ASC, `setting_key` ASC) USING BTREE,
  CONSTRAINT `raffle_settings_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Create prize_categories table
-- ----------------------------
CREATE TABLE IF NOT EXISTS `prize_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_color` varchar(20) DEFAULT '#FFD700' COMMENT 'Hex color code',
  `category_order` int(11) DEFAULT 0,
  `quantity` int(11) DEFAULT 1 COMMENT 'Number of winners in this category',
  `winners_count` int(11) DEFAULT 0 COMMENT 'Current winners in this category',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `raffle_key`(`raffle_key` ASC) USING BTREE,
  CONSTRAINT `prize_categories_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Create raffle_state table
-- ----------------------------
CREATE TABLE IF NOT EXISTS `raffle_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_action` enum('idle','spinning','revealing','confirmed') DEFAULT 'idle',
  `current_prize_id` int(11) NULL COMMENT 'Current prize category being drawn',
  `current_winner` varchar(255) NULL COMMENT 'Current winner name',
  `current_winner_id` int(11) NULL COMMENT 'Current winner entry ID',
  `triggered_by` enum('main','vip','remote') DEFAULT 'main' COMMENT 'Who triggered the spin',
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `raffle_key`(`raffle_key` ASC) USING BTREE,
  CONSTRAINT `raffle_state_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Create custom_sounds table
-- ----------------------------
CREATE TABLE IF NOT EXISTS `custom_sounds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sound_type` enum('spin','winner','click') NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `raffle_key`(`raffle_key` ASC) USING BTREE,
  CONSTRAINT `custom_sounds_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;

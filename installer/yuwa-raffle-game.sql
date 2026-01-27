/*
 Raffle Game Pro - Database Migration
 Version: 2.0.0
 Date: 2026-01-27

 This migration adds new tables for:
 - Settings management
 - Prize categories
 - Real-time state sync
 - Custom sound uploads
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for raffle_keys (existing, no changes)
-- ----------------------------
DROP TABLE IF EXISTS `raffle_keys`;
CREATE TABLE `raffle_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Raffle Game',
  `is_locked` tinyint(1) DEFAULT 0 COMMENT 'Session lock during event',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `raffle_key`(`raffle_key` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for raffle_entries (updated)
-- ----------------------------
DROP TABLE IF EXISTS `raffle_entries`;
CREATE TABLE `raffle_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_winner` tinyint(1) DEFAULT 0 COMMENT 'Mark if already won',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `raffle_key`(`raffle_key` ASC) USING BTREE,
  CONSTRAINT `raffle_entries_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for raffle_logs (updated with prize category)
-- ----------------------------
DROP TABLE IF EXISTS `raffle_logs`;
CREATE TABLE `raffle_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_id` int(11) NULL COMMENT 'Reference to raffle_entries',
  `prize_category_id` int(11) NULL COMMENT 'Reference to prize_categories',
  `log_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_undone` tinyint(1) DEFAULT 0 COMMENT 'Flag for undo action',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `raffle_key`(`raffle_key` ASC) USING BTREE,
  CONSTRAINT `raffle_logs_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for raffle_settings (NEW)
-- ----------------------------
DROP TABLE IF EXISTS `raffle_settings`;
CREATE TABLE `raffle_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_setting`(`raffle_key` ASC, `setting_key` ASC) USING BTREE,
  CONSTRAINT `raffle_settings_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

/*
Available setting_key values:
- event_title: Event name (string)
- event_logo: Logo file path (string)
- result_font_size: Font size for result display (number, 24-200)
- spin_duration: Spin time in seconds (number, 1-30)
- show_main_button: Show spin button on main display (boolean, 0/1)
- animation_template: 'text_roll' or 'wheel'
- theme: 'dark' or 'light'
- enable_confetti: Enable confetti effect (boolean, 0/1)
- enable_sound: Enable sound effects (boolean, 0/1)
- sound_spin: Custom spin sound file path (string)
- sound_winner: Custom winner sound file path (string)
- enable_prize_categories: Enable multiple prize categories (boolean, 0/1)
- auto_number_enabled: Enable auto-generated numbers (boolean, 0/1)
- auto_number_prefix: Prefix for auto numbers (string)
- auto_number_start: Start number (number)
- auto_number_end: End number (number)
*/

-- ----------------------------
-- Table structure for prize_categories (NEW)
-- ----------------------------
DROP TABLE IF EXISTS `prize_categories`;
CREATE TABLE `prize_categories` (
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for raffle_state (NEW - for real-time sync)
-- ----------------------------
DROP TABLE IF EXISTS `raffle_state`;
CREATE TABLE `raffle_state` (
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for custom_sounds (NEW - for uploaded sounds)
-- ----------------------------
DROP TABLE IF EXISTS `custom_sounds`;
CREATE TABLE `custom_sounds` (
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;

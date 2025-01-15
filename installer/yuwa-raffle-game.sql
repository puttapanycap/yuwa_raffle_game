/*
 Navicat Premium Dump SQL

 Source Server         : MariaDB_localhost
 Source Server Type    : MariaDB
 Source Server Version : 110502 (11.5.2-MariaDB-log)
 Source Host           : localhost:3306
 Source Schema         : yuwa-raffle-game

 Target Server Type    : MariaDB
 Target Server Version : 110502 (11.5.2-MariaDB-log)
 File Encoding         : 65001

 Date: 15/01/2025 16:47:01
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for raffle_entries
-- ----------------------------
DROP TABLE IF EXISTS `raffle_entries`;
CREATE TABLE `raffle_entries`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `raffle_key`(`raffle_key` ASC) USING BTREE,
  CONSTRAINT `raffle_entries_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of raffle_entries
-- ----------------------------

-- ----------------------------
-- Table structure for raffle_keys
-- ----------------------------
DROP TABLE IF EXISTS `raffle_keys`;
CREATE TABLE `raffle_keys`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `raffle_key`(`raffle_key` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of raffle_keys
-- ----------------------------

-- ----------------------------
-- Table structure for raffle_logs
-- ----------------------------
DROP TABLE IF EXISTS `raffle_logs`;
CREATE TABLE `raffle_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raffle_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `raffle_key`(`raffle_key` ASC) USING BTREE,
  CONSTRAINT `raffle_logs_ibfk_1` FOREIGN KEY (`raffle_key`) REFERENCES `raffle_keys` (`raffle_key`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of raffle_logs
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;

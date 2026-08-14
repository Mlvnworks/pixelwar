-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (x86_64)
--
-- Host: 127.0.0.1    Database: pixelwar
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `al_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `log_text` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`al_id`),
  KEY `activity_logs_user_id_index` (`user_id`),
  KEY `activity_logs_category_index` (`category`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'auth','Logged in.','2026-08-14 14:57:32'),(2,1,'account','Completed admin account setup and requested email verification.','2026-08-14 14:58:20'),(3,1,'account','Updated verification email address.','2026-08-14 15:06:03'),(4,1,'account','Requested a new verification code.','2026-08-14 15:51:12'),(5,1,'account','Updated verification email address.','2026-08-14 15:54:57'),(6,1,'account','Verified email address.','2026-08-14 15:55:56'),(7,1,'admin','Created teacher account \"teacher1\".','2026-08-14 15:58:52'),(8,2,'auth','Logged in.','2026-08-14 15:59:43'),(9,2,'account','Verified email address.','2026-08-14 16:00:02'),(10,2,'account','Completed teacher account setup.','2026-08-14 16:01:00'),(11,2,'challenge','Created challenge \"Profile Card\".','2026-08-14 16:06:09'),(12,2,'challenge','Created challenge \"Login Form\".','2026-08-14 16:07:47'),(13,2,'challenge','Created challenge \"Notification Alert\".','2026-08-14 16:09:04'),(14,2,'challenge','Created challenge \"Product Card\".','2026-08-14 16:10:26'),(15,2,'challenge','Created challenge \"Simple Button Styling\".','2026-08-14 16:13:13'),(16,1,'auth','Requested an admin password reset link.','2026-08-14 16:26:19');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `challenges`
--

DROP TABLE IF EXISTS `challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenges` (
  `challenge_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `difficulty_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `instruction` text NOT NULL,
  `html_source` varchar(255) NOT NULL,
  `css_source` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_deleted` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`challenge_id`),
  KEY `challenges_user_id_index` (`user_id`),
  KEY `challenges_difficulty_id_index` (`difficulty_id`),
  KEY `challenges_status_index` (`status`),
  CONSTRAINT `challenges_difficulty_id_foreign` FOREIGN KEY (`difficulty_id`) REFERENCES `difficulties` (`difficulty_id`),
  CONSTRAINT `challenges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `challenges`
--

LOCK TABLES `challenges` WRITE;
/*!40000 ALTER TABLE `challenges` DISABLE KEYS */;
INSERT INTO `challenges` VALUES (1,2,1,'Profile Card','Create a profile card containing a circular profile image, name, short description, and Follow button. Use CSS to center the content, round the card corners, add a shadow, and make the profile image circular.\r\n\r\nUseful lessons:\r\n\r\nhttps://www.w3schools.com/css/css_boxmodel.asp?utm_source=chatgpt.com','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/html/teacher-2-profile-card-532e5ac0eeede6f3.html','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/css/teacher-2-profile-card-856e555a84d32cbb.css',1,'2026-08-14 16:06:08',NULL),(2,2,1,'Login Form','Goal: Create a clean login form with styled inputs and a login button.','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/html/teacher-2-login-form-b35a5136f825b177.html','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/css/teacher-2-login-form-4d17395b87a3d3e3.css',1,'2026-08-14 16:07:47',NULL),(3,2,1,'Notification Alert','Goal: Style a success notification containing an icon, message, and close button.','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/html/teacher-2-notification-alert-82c9ba133d509d79.html','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/css/teacher-2-notification-alert-2fe532d55a71c727.css',1,'2026-08-14 16:09:04',NULL),(4,2,1,'Product Card','Goal: Build a small product card with an image, product information, price, and purchase button.','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/html/teacher-2-product-card-b52406758ece7dcf.html','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/css/teacher-2-product-card-a9d66525940cf1aa.css',1,'2026-08-14 16:10:26',NULL),(5,2,1,'Simple Button Styling','Style the Click Me button to match the target design. Give it a blue background, white text, rounded corners, and enough padding to make the button larger.','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/html/teacher-2-simple-button-styling-7b5ba418bc8412c2.html','https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/challenge-sources/css/teacher-2-simple-button-styling-fc4ab08bca4f8201.css',1,'2026-08-14 16:13:13',NULL);
/*!40000 ALTER TABLE `challenges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `comments_user_id_index` (`user_id`),
  KEY `comments_challenge_id_index` (`challenge_id`),
  KEY `comments_date_created_index` (`date_created`),
  CONSTRAINT `comments_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`),
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `difficulties`
--

DROP TABLE IF EXISTS `difficulties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `difficulties` (
  `difficulty_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `points` int(11) NOT NULL,
  PRIMARY KEY (`difficulty_id`),
  UNIQUE KEY `difficulties_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `difficulties`
--

LOCK TABLES `difficulties` WRITE;
/*!40000 ALTER TABLE `difficulties` DISABLE KEYS */;
INSERT INTO `difficulties` VALUES (1,'easy','Intro-friendly CSS matching challenge.',20),(2,'medium','Requires combining layout, spacing, and visual details.',40),(3,'hard','Advanced matching challenge with stricter visual precision.',80);
/*!40000 ALTER TABLE `difficulties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `images` (
  `img_id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(255) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`img_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `images`
--

LOCK TABLES `images` WRITE;
/*!40000 ALTER TABLE `images` DISABLE KEYS */;
INSERT INTO `images` VALUES (1,'https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/avatars/user-1-1df8d39b30a0e663.png','2026-08-14 14:58:15'),(2,'https://vaqtstmiikiruqpvywbr.supabase.co/storage/v1/object/public/pixelwar%20files/avatars/user-2-aec01ab0f6f8acbe.png','2026-08-14 16:01:00');
/*!40000 ALTER TABLE `images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `type` varchar(80) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notif_id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_type_index` (`type`),
  KEY `notifications_created_at_index` (`created_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_progress`
--

DROP TABLE IF EXISTS `player_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_progress` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `season_id` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pp_id`),
  UNIQUE KEY `player_progress_user_season_unique` (`user_id`,`season_id`),
  KEY `player_progress_user_id_index` (`user_id`),
  KEY `player_progress_season_id_index` (`season_id`),
  CONSTRAINT `player_progress_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`),
  CONSTRAINT `player_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_progress`
--

LOCK TABLES `player_progress` WRITE;
/*!40000 ALTER TABLE `player_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pvp_matches`
--

DROP TABLE IF EXISTS `pvp_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pvp_matches` (
  `pvp_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  PRIMARY KEY (`pvp_id`),
  KEY `pvp_matches_user_id_index` (`user_id`),
  KEY `pvp_matches_challenge_id_index` (`challenge_id`),
  CONSTRAINT `pvp_matches_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`),
  CONSTRAINT `pvp_matches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pvp_matches`
--

LOCK TABLES `pvp_matches` WRITE;
/*!40000 ALTER TABLE `pvp_matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `pvp_matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pvp_players`
--

DROP TABLE IF EXISTS `pvp_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pvp_players` (
  `p_pvp_id` int(11) NOT NULL AUTO_INCREMENT,
  `pvp_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`p_pvp_id`),
  KEY `pvp_players_pvp_id_index` (`pvp_id`),
  KEY `pvp_players_user_id_index` (`user_id`),
  KEY `pvp_players_status_index` (`status`),
  CONSTRAINT `pvp_players_pvp_id_foreign` FOREIGN KEY (`pvp_id`) REFERENCES `pvp_matches` (`pvp_id`),
  CONSTRAINT `pvp_players_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pvp_players`
--

LOCK TABLES `pvp_players` WRITE;
/*!40000 ALTER TABLE `pvp_players` DISABLE KEYS */;
/*!40000 ALTER TABLE `pvp_players` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ranks`
--

DROP TABLE IF EXISTS `ranks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ranks` (
  `rank_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `points_requirements` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`rank_id`),
  UNIQUE KEY `ranks_name_unique` (`name`),
  UNIQUE KEY `ranks_points_requirements_unique` (`points_requirements`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ranks`
--

LOCK TABLES `ranks` WRITE;
/*!40000 ALTER TABLE `ranks` DISABLE KEYS */;
/*!40000 ALTER TABLE `ranks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `roles_role_unique` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin'),(3,'student'),(2,'teacher');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_players`
--

DROP TABLE IF EXISTS `room_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_players` (
  `rp_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `strict_mode_score` int(11) NOT NULL DEFAULT 0,
  `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`rp_id`),
  KEY `room_players_user_id_index` (`user_id`),
  KEY `room_players_room_id_index` (`room_id`),
  KEY `room_players_status_index` (`status`),
  CONSTRAINT `room_players_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  CONSTRAINT `room_players_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_players`
--

LOCK TABLES `room_players` WRITE;
/*!40000 ALTER TABLE `room_players` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_players` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `room_code` varchar(100) NOT NULL,
  `room_name` varchar(150) NOT NULL,
  `room_description` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `timer_limit` int(11) NOT NULL DEFAULT 0,
  `strict_mode` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_deleted` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`room_id`),
  KEY `rooms_room_code_index` (`room_code`),
  KEY `rooms_user_id_index` (`user_id`),
  KEY `rooms_challenge_id_index` (`challenge_id`),
  KEY `rooms_status_index` (`status`),
  CONSTRAINT `rooms_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`),
  CONSTRAINT `rooms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seasons`
--

DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seasons` (
  `season_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `start_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`season_id`),
  UNIQUE KEY `seasons_name_unique` (`name`),
  KEY `seasons_date_range_index` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seasons`
--

LOCK TABLES `seasons` WRITE;
/*!40000 ALTER TABLE `seasons` DISABLE KEYS */;
/*!40000 ALTER TABLE `seasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_challenge`
--

DROP TABLE IF EXISTS `user_challenge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_challenge` (
  `uc_id` int(11) NOT NULL AUTO_INCREMENT,
  `challenge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `pvp_id` int(11) DEFAULT NULL,
  `season_id` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uc_id`),
  KEY `user_challenge_challenge_id_index` (`challenge_id`),
  KEY `user_challenge_user_id_index` (`user_id`),
  KEY `user_challenge_room_id_index` (`room_id`),
  KEY `user_challenge_pvp_id_index` (`pvp_id`),
  KEY `user_challenge_season_id_index` (`season_id`),
  CONSTRAINT `user_challenge_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`),
  CONSTRAINT `user_challenge_pvp_id_foreign` FOREIGN KEY (`pvp_id`) REFERENCES `pvp_matches` (`pvp_id`),
  CONSTRAINT `user_challenge_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  CONSTRAINT `user_challenge_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`),
  CONSTRAINT `user_challenge_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_challenge`
--

LOCK TABLES `user_challenge` WRITE;
/*!40000 ALTER TABLE `user_challenge` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_challenge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_details`
--

DROP TABLE IF EXISTS `user_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_details` (
  `ud_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `id_picture` int(11) DEFAULT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `student_number` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ud_id`),
  UNIQUE KEY `user_details_user_id_unique` (`user_id`),
  KEY `user_details_image_id_index` (`image_id`),
  KEY `user_details_id_picture_index` (`id_picture`),
  CONSTRAINT `user_details_id_picture_foreign` FOREIGN KEY (`id_picture`) REFERENCES `images` (`img_id`),
  CONSTRAINT `user_details_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `images` (`img_id`),
  CONSTRAINT `user_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_details`
--

LOCK TABLES `user_details` WRITE;
/*!40000 ALTER TABLE `user_details` DISABLE KEYS */;
INSERT INTO `user_details` VALUES (1,1,1,NULL,'Pixel','Admin',NULL),(2,2,2,NULL,'Jane','Doe',NULL);
/*!40000 ALTER TABLE `user_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `acc_type` varchar(30) NOT NULL DEFAULT 'manual',
  `is_verified` int(11) NOT NULL DEFAULT 0,
  `is_active` int(11) NOT NULL DEFAULT 0,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_deleted` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_index` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'admin','pixelwarsystem@gmail.com','$2y$10$tfgjDuzbkJ4QxVGGyCknJOPHBUjI1U0r9/2xxUcQ/4Gxkjm20XHEq','manual',1,1,'2026-08-14 16:33:45','2026-08-14 14:57:15',NULL),(2,2,'teacher_jane','subsh4re@gmail.com','$2y$10$SjBLUcRnHswdBGzKk8awkutCYgHg4wbKtXpcMRXVPv77nQZC5.GYO','manual',1,0,'2026-08-14 16:33:27','2026-08-14 15:58:48',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verifications`
--

DROP TABLE IF EXISTS `verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verifications` (
  `ev_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(80) NOT NULL,
  `token` varchar(255) NOT NULL,
  `request_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ev_id`),
  KEY `verifications_user_id_index` (`user_id`),
  KEY `verifications_token_index` (`token`),
  CONSTRAINT `verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verifications`
--

LOCK TABLES `verifications` WRITE;
/*!40000 ALTER TABLE `verifications` DISABLE KEYS */;
INSERT INTO `verifications` VALUES (1,1,'account verification','$2y$10$.6C05QH4P1V5ERW2tGPw9uq3MeTDG.B.IBaxRifxENgkl2miFF4vm','2026-08-14 14:58:16',-1),(2,1,'account verification','$2y$10$/b2qO3H6BFDCwa43sqief.naUZXGaG4Zqs22c952k9rO1dBcu5Fs.','2026-08-14 15:06:03',-1),(3,1,'account verification','$2y$10$Wt6XL1JZodjp7QaZnGROIe0tiWu7A4IzCEAmeWYXhzn0.ydU7NaPa','2026-08-14 15:51:08',-1),(4,1,'account verification','$2y$10$vEPDSlWzkUT80eGWif1iJeRRhn.kHz6gjnyLFcLMRtnZ3L7bbOruK','2026-08-14 15:54:57',1),(5,2,'account verification','$2y$10$M.DDD9IkAg6PEBdGGcvERex.5pjzsc/n/HX9aBEKj95bLRhgL.qKC','2026-08-14 15:59:43',1),(6,1,'password change','$2y$10$zjqazOgYbNYaaJIMZDg/aOKvkdCG6AoP5pOeAccZanal9wrcnZ25q','2026-08-14 16:26:15',0);
/*!40000 ALTER TABLE `verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'pixelwar'
--

--
-- Dumping routines for database 'pixelwar'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-15  0:34:02

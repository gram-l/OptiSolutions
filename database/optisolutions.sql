-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: optisolutions
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `app_notifications`
--

DROP TABLE IF EXISTS `app_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_notifications` (
  `notification_id` int unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `fk_app_notifications_user` (`user_id`),
  CONSTRAINT `fk_app_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_notifications`
--

LOCK TABLES `app_notifications` WRITE;
/*!40000 ALTER TABLE `app_notifications` DISABLE KEYS */;
INSERT INTO `app_notifications` VALUES (1,2,'feedback','Inquiry notification','This is a test to confirm the screen works.',NULL,NULL,1,'2026-07-14 22:54:02','2026-07-14 22:54:43'),(2,2,'chat_inquiry','New chatbot inquiry','A patient submitted a new question via the chatbot.',NULL,NULL,1,'2026-07-14 23:00:07','2026-07-14 23:05:05');
/*!40000 ALTER TABLE `app_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-conversation-40d90b4f1d8f827c0679224d0a7fe7b609aad49e-40d90b4f1d8f827c0679224d0a7fe7b609aad49e','a:5:{s:12:\"conversation\";O:36:\"App\\Conversations\\ReviewConversation\":5:{s:8:\"\0*\0token\";N;s:12:\"\0*\0cacheTime\";N;s:12:\"\0*\0patientId\";N;s:14:\"\0*\0patientName\";N;s:9:\"\0*\0rating\";N;}s:8:\"question\";s:963:\"O:40:\"BotMan\\BotMan\\Messages\\Outgoing\\Question\":4:{s:10:\"\0*\0actions\";a:5:{i:0;a:7:{s:4:\"name\";s:1:\"1\";s:4:\"text\";s:1:\"1\";s:9:\"image_url\";N;s:3:\"url\";N;s:4:\"type\";s:6:\"button\";s:5:\"value\";s:1:\"1\";s:10:\"additional\";a:0:{}}i:1;a:7:{s:4:\"name\";s:1:\"2\";s:4:\"text\";s:1:\"2\";s:9:\"image_url\";N;s:3:\"url\";N;s:4:\"type\";s:6:\"button\";s:5:\"value\";s:1:\"2\";s:10:\"additional\";a:0:{}}i:2;a:7:{s:4:\"name\";s:1:\"3\";s:4:\"text\";s:1:\"3\";s:9:\"image_url\";N;s:3:\"url\";N;s:4:\"type\";s:6:\"button\";s:5:\"value\";s:1:\"3\";s:10:\"additional\";a:0:{}}i:3;a:7:{s:4:\"name\";s:1:\"4\";s:4:\"text\";s:1:\"4\";s:9:\"image_url\";N;s:3:\"url\";N;s:4:\"type\";s:6:\"button\";s:5:\"value\";s:1:\"4\";s:10:\"additional\";a:0:{}}i:4;a:7:{s:4:\"name\";s:1:\"5\";s:4:\"text\";s:1:\"5\";s:9:\"image_url\";N;s:3:\"url\";N;s:4:\"type\";s:6:\"button\";s:5:\"value\";s:1:\"5\";s:10:\"additional\";a:0:{}}}s:7:\"\0*\0text\";s:43:\"How would you rate your experience with us?\";s:14:\"\0*\0callback_id\";N;s:11:\"\0*\0fallback\";s:41:\"Please tap one of the star options above.\";}\";s:20:\"additionalParameters\";s:6:\"a:0:{}\";s:4:\"next\";s:759:\"O:47:\"Laravel\\SerializableClosure\\SerializableClosure\":1:{s:12:\"serializable\";O:46:\"Laravel\\SerializableClosure\\Serializers\\Signed\":2:{s:12:\"serializable\";s:530:\"O:46:\"Laravel\\SerializableClosure\\Serializers\\Native\":5:{s:3:\"use\";a:0:{}s:8:\"function\";s:163:\"function (\\BotMan\\BotMan\\Messages\\Incoming\\Answer $answer) {\n            $this->rating = (int) $answer->getValue();\n            $this->askFeedbackText();\n        }\";s:5:\"scope\";s:36:\"App\\Conversations\\ReviewConversation\";s:4:\"this\";O:36:\"App\\Conversations\\ReviewConversation\":5:{s:8:\"\0*\0token\";N;s:12:\"\0*\0cacheTime\";N;s:12:\"\0*\0patientId\";N;s:14:\"\0*\0patientName\";N;s:9:\"\0*\0rating\";N;}s:4:\"self\";s:32:\"00000000000002150000000000000000\";}\";s:4:\"hash\";s:44:\"xmX83pDUXbWqZcb6f5CIYgwbxf6usK3l1o0K06T9UrM=\";}}\";s:4:\"time\";s:21:\"0.38372500 1784606819\";}',1784609219);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_logs`
--

DROP TABLE IF EXISTS `chatbot_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `conversation_id` varchar(191) DEFAULT NULL,
  `user_message` text NOT NULL,
  `bot_message` text NOT NULL,
  `chat_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_chatbot_logs_conversation_id` (`conversation_id`),
  KEY `fk_chatbot_logs_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_logs`
--

LOCK TABLES `chatbot_logs` WRITE;
/*!40000 ALTER TABLE `chatbot_logs` DISABLE KEYS */;
INSERT INTO `chatbot_logs` VALUES (1,NULL,NULL,'ghfgffghfhf hfghfhfghfth','Complaint recorded','2026-07-20 06:08:09'),(2,NULL,NULL,'Service ay mabagal','Review recorded','2026-07-20 09:13:42'),(3,NULL,NULL,'Masama ugali ng doctor at staff','Complaint recorded','2026-07-20 09:15:29'),(4,NULL,NULL,'masama ugali ng staff','Complaint recorded','2026-07-20 09:20:03'),(5,NULL,NULL,'Masama ugali niyo','Complaint recorded','2026-07-20 09:45:02'),(6,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','may HMO cards','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-20 10:25:01'),(7,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','Menu','Hello! Welcome to PolyClinic Lipa. How can I help you today?','2026-07-20 10:25:54'),(8,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','schedule visit','Please provide your full name (first and last name):','2026-07-20 10:25:59'),(9,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','Ilyong Gardon','Thanks, Ilyong Gardon!\n---\nPlease provide your contact number (e.g. 09XX XXX XXXX):','2026-07-20 10:26:16'),(10,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','09152345420','Please provide your date of birth (e.g. 05/15/1990 or May 15, 1990):','2026-07-20 10:26:29'),(11,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','May 15, 1990','Please provide your email address:','2026-07-20 10:26:36'),(12,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','elijahloyola65@gmail.com','Please select the service you need:','2026-07-20 10:26:50'),(13,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','dermatology','We currently don\'t have a specialist for Dermatology. Returning to main menu.','2026-07-20 10:26:54'),(14,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','pediatrics','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-20 10:27:01'),(15,NULL,'d772c9e6-8298-4e66-862d-bc16971623c4','ophthalmology','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-20 10:27:09'),(16,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','schedule visit','Please provide your full name (first and last name):','2026-07-20 11:07:07'),(17,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','cancel','Okay, your schedule visit has been cancelled. Type \"Menu\" anytime to start again.','2026-07-20 11:07:11'),(18,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','menu','Hello! Welcome to PolyClinic Lipa. How can I help you today?','2026-07-20 11:07:22'),(19,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','general information','INFO_CARD::{\"title\":\"PolyClinic Lipa - Clinic Information\",\"sections\":[{\"rows\":[{\"label\":\"Clinic Name\",\"value\":\"PolyClinic Lipa\",\"type\":\"text\"},{\"label\":\"Address\",\"value\":\"TM Kalaw St., Lipa City, Batangas 4217\",\"type\":\"text\"},{\"label\":\"Email\",\"value\":\"polycliniclipa@gmail.com\",\"type\":\"text\"},{\"label\":\"Contact No\",\"value\":\"0985 475 5511\",\"type\":\"text\"}]},{\"rows\":[{\"label\":\"Operating Hours\",\"value\":[\"Monday - Friday: 8:00 AM - 6:00 PM\",\"Saturday: 9:00 AM - 1:00 PM\"],\"type\":\"multiline\"}]},{\"rows\":[{\"label\":\"Facebook Link\",\"value\":\"https:\\/\\/www.facebook.com\\/polycliniclipa\",\"type\":\"link\"}]}]}','2026-07-20 11:07:24'),(20,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','submit review/rating','How would you rate your experience with us?','2026-07-20 11:07:29'),(21,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-20 11:07:33'),(23,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','maganda at mura','⚠️ We couldn\'t record your review right now. Please try again, or contact us directly.\n---\nIs there anything else I can help you with?','2026-07-20 11:07:47'),(24,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','submit review/rating','How would you rate your experience with us?','2026-07-20 11:08:59'),(25,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-20 11:09:01'),(27,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','panget ng serbisyo','⚠️ We couldn\'t record your review right now. Please try again, or contact us directly.\n---\nIs there anything else I can help you with?','2026-07-20 11:09:12'),(28,NULL,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','ok','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-20 11:20:51'),(29,NULL,'a53e6af5-ab06-4190-ba35-63b28695a645','submit review/rating','How would you rate your experience with us?','2026-07-20 11:48:23'),(30,NULL,'a53e6af5-ab06-4190-ba35-63b28695a645','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-20 11:48:27'),(32,NULL,'a53e6af5-ab06-4190-ba35-63b28695a645','Maganda ang service at mabilis','⚠️ We couldn\'t record your review right now. Please try again, or contact us directly.\n---\nIs there anything else I can help you with?','2026-07-20 11:48:38'),(33,NULL,'ee3aae7f-a402-4954-8d34-eeff3fa13c1d','submit review/rating','How would you rate your experience with us?','2026-07-20 15:36:50'),(34,NULL,'ee3aae7f-a402-4954-8d34-eeff3fa13c1d','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-20 15:36:53'),(35,1,NULL,'I liked how the doctors treated me','Review recorded','2026-07-20 15:37:08'),(36,NULL,'ee3aae7f-a402-4954-8d34-eeff3fa13c1d','I liked how the doctors treated me','⚠️ We couldn\'t record your review right now. Please try again, or contact us directly.\n---\nIs there anything else I can help you with?','2026-07-20 15:37:08'),(37,NULL,'fc665862-48c5-4871-99f2-b9898bbfe521','submit review/rating','How would you rate your experience with us?','2026-07-20 20:06:54'),(38,NULL,'fc665862-48c5-4871-99f2-b9898bbfe521','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-20 20:06:57'),(39,1,NULL,'Good service, im satisfied','Review recorded','2026-07-20 20:07:16'),(40,NULL,'fc665862-48c5-4871-99f2-b9898bbfe521','Good service, im satisfied','⚠️ We couldn\'t record your review right now. Please try again, or contact us directly.\n---\nIs there anything else I can help you with?','2026-07-20 20:07:16'),(41,NULL,'fc665862-48c5-4871-99f2-b9898bbfe521','submit review/rating','How would you rate your experience with us?','2026-07-20 20:07:31'),(42,NULL,'fc665862-48c5-4871-99f2-b9898bbfe521','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-20 20:07:40'),(43,1,NULL,'Good service','Review recorded','2026-07-20 20:08:08'),(44,NULL,'fc665862-48c5-4871-99f2-b9898bbfe521','Good service','⚠️ We couldn\'t record your review right now. Please try again, or contact us directly.\n---\nIs there anything else I can help you with?','2026-07-20 20:08:08'),(45,NULL,'74d9a015-a673-4d7e-bb05-f79c54ad5d93','submit review/rating','How would you rate your experience with us?','2026-07-20 20:16:59'),(46,NULL,'74d9a015-a673-4d7e-bb05-f79c54ad5d93','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-20 20:17:00'),(47,1,NULL,'Good service, im deeply satified','Review recorded','2026-07-20 20:17:17'),(48,NULL,'74d9a015-a673-4d7e-bb05-f79c54ad5d93','Good service, im deeply satified','Thank you for your feedback!\n\nYou rated us 5/5. We appreciate you taking the time to help us improve.\n---\nIs there anything else I can help you with?','2026-07-20 20:17:21'),(49,NULL,'74d9a015-a673-4d7e-bb05-f79c54ad5d93','submit complaint','Please describe your concern in detail. We would like to hear your experience from us>','2026-07-20 20:18:27'),(50,1,NULL,'Hindi magaling ang doctor','Complaint recorded','2026-07-20 20:19:43'),(51,NULL,'74d9a015-a673-4d7e-bb05-f79c54ad5d93','Hindi magaling ang doctor','Complaint Recorded\n\nReference #: 13\nWe acknowledge receipt of your concern.\n---\nIs there anything else I can help you with?','2026-07-20 20:19:43'),(52,NULL,'2a7349ee-ce28-45ce-af95-77905afd779a','submit complaint','Please describe your concern in detail. We would like to hear your experience from us>','2026-07-20 20:41:34'),(53,1,NULL,'and tagal, ang haba ng pila','Complaint recorded','2026-07-20 20:42:29'),(54,NULL,'2a7349ee-ce28-45ce-af95-77905afd779a','and tagal, ang haba ng pila','Complaint Recorded\n\nReference #: 14\nWe acknowledge receipt of your concern.\n---\nIs there anything else I can help you with?','2026-07-20 20:42:30'),(55,NULL,'13a2a0ea-6791-417b-8c47-50dcc36fa04e','May lagnat ang anak ko','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-20 21:07:02'),(56,NULL,'13a2a0ea-6791-417b-8c47-50dcc36fa04e','schedule visit','Please provide your full name (first and last name):','2026-07-20 21:07:24'),(57,NULL,'2a7349ee-ce28-45ce-af95-77905afd779a','submit complaint','Please describe your concern in detail. We would like to hear your experience from us>','2026-07-20 21:33:19'),(58,1,NULL,'Hindi marunong magpaliwag doctor dito','Complaint recorded','2026-07-20 21:33:39'),(59,NULL,'2a7349ee-ce28-45ce-af95-77905afd779a','Hindi marunong magpaliwag doctor dito','Complaint Recorded\n\nReference #: 15\nWe acknowledge receipt of your concern.\n---\nIs there anything else I can help you with?','2026-07-20 21:33:39'),(60,NULL,'74d9a015-a673-4d7e-bb05-f79c54ad5d93','submit complaint','Please describe your concern in detail. We would like to hear your experience from us>','2026-07-20 21:42:49'),(61,1,NULL,'napaka sungit ng staff dito','Complaint recorded','2026-07-20 21:43:17'),(62,NULL,'74d9a015-a673-4d7e-bb05-f79c54ad5d93','napaka sungit ng staff dito','Complaint Recorded\n\nReference #: 16\nWe acknowledge receipt of your concern.\n---\nIs there anything else I can help you with?','2026-07-20 21:43:17'),(63,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','schedule visit','Please provide your full name (first and last name):','2026-07-21 01:09:51'),(64,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','Noemi Gepiga','Thanks, Noemi Gepiga!\n---\nPlease provide your contact number (e.g. 09XX XXX XXXX):','2026-07-21 01:10:00'),(65,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','09095564533','Please provide your date of birth (e.g. 05/15/1990 or May 15, 1990):','2026-07-21 01:10:06'),(66,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','May 12 2002','Please provide your email address:','2026-07-21 01:10:11'),(67,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','gepiganoemi@gmail.com','Please select the service you need:','2026-07-21 01:10:20'),(68,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','pediatrics','Great! Here are our specialists for Pediatrics:','2026-07-21 01:10:28'),(69,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','11','Suggested schedule: Monday, July 27, 2026 at 10AM - 3PM\n        \n(Based on Dr. Amelia Rellora\'s availability — Monday)','2026-07-21 01:10:35'),(70,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','confirm_no','Suggested schedule: Tuesday, July 28, 2026 at 10AM - 3PM\n        \n(Based on Dr. Amelia Rellora\'s availability — Tuesday)','2026-07-21 01:11:00'),(71,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','confirm_no','Suggested schedule: Wednesday, July 22, 2026 at 10AM - 3PM\n        \n(Based on Dr. Amelia Rellora\'s availability — Wednesday)','2026-07-21 01:11:12'),(72,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','confirm_yes','Saving your schedule visit...\n---\nAPPT_CARD::{\"title\":\"Schedule Visit Confirmed, Noemi Gepiga!\\nKindly screenshot this confirmation for your reference.\",\"sections\":[{\"rows\":[{\"label\":\"Patient\",\"value\":\"Noemi Gepiga\"},{\"label\":\"Contact\",\"value\":\"09095564533\"},{\"label\":\"Date of Birth\",\"value\":\"May 12 2002\"},{\"label\":\"Email\",\"value\":\"gepiganoemi@gmail.com\"}]},{\"rows\":[{\"label\":\"Service\",\"value\":\"Pediatrics\"},{\"label\":\"Doctor\",\"value\":\"Dr. Amelia Rellora\"}]},{\"rows\":[{\"label\":\"Scheduled\",\"value\":\"Wednesday, July 22, 2026 at 10AM - 3PM\"}]}]}\n---\nWould you like us to email you a copy of this whole conversation at gepiganoemi@gmail.com?','2026-07-21 01:11:22'),(73,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','yes_email','Sent! Please check your inbox (and spam folder) at gepiganoemi@gmail.com.\n---\nThank you for scheduling with us!','2026-07-21 01:12:00'),(74,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','complaint','Please describe your concern in detail. We would like to hear your experience from us>','2026-07-21 01:12:45'),(75,1,NULL,'ang sungit ng nurse nyo.','Complaint recorded','2026-07-21 01:13:37'),(76,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','ang sungit ng nurse nyo.','Complaint Recorded\n\nReference #: 17\nWe acknowledge receipt of your concern.\n---\nIs there anything else I can help you with?','2026-07-21 01:13:37'),(77,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','submit review/rating','How would you rate your experience with us?','2026-07-21 01:13:52'),(78,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','3','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-21 01:14:04'),(79,1,NULL,'Sana mas mabilis ang process next time.','Review recorded','2026-07-21 01:14:49'),(80,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','Sana mas mabilis ang process next time.','Thank you for your feedback!\n\nYou rated us 3/5. We appreciate you taking the time to help us improve.\n---\nIs there anything else I can help you with?','2026-07-21 01:14:50'),(81,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','general information','INFO_CARD::{\"title\":\"PolyClinic Lipa - Clinic Information\",\"sections\":[{\"rows\":[{\"label\":\"Clinic Name\",\"value\":\"PolyClinic Lipa\",\"type\":\"text\"},{\"label\":\"Address\",\"value\":\"TM Kalaw St., Lipa City, Batangas 4217\",\"type\":\"text\"},{\"label\":\"Email\",\"value\":\"polycliniclipa@gmail.com\",\"type\":\"text\"},{\"label\":\"Contact No\",\"value\":\"0985 475 5511\",\"type\":\"text\"}]},{\"rows\":[{\"label\":\"Operating Hours\",\"value\":[\"Monday - Friday: 8:00 AM - 6:00 PM\",\"Saturday: 9:00 AM - 1:00 PM\"],\"type\":\"multiline\"}]},{\"rows\":[{\"label\":\"Facebook Link\",\"value\":\"https:\\/\\/www.facebook.com\\/polycliniclipa\",\"type\":\"link\"}]}]}','2026-07-21 01:15:12'),(82,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','Magkano po consultation?','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-21 01:16:06'),(83,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','submit review/rating','How would you rate your experience with us?','2026-07-21 01:19:11'),(84,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','hui','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-21 01:19:41'),(85,1,NULL,'submit review/rating','Review recorded','2026-07-21 01:19:55'),(86,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','submit review/rating','Thank you for your feedback!\n\nYou rated us 0/5. We appreciate you taking the time to help us improve.\n---\nIs there anything else I can help you with?','2026-07-21 01:19:55'),(87,NULL,'a3b8230e-aa3c-4553-8ef1-6eb358cee397','submit review/rating','How would you rate your experience with us?','2026-07-21 01:20:10'),(88,NULL,'a3b8230e-aa3c-4553-8ef1-6eb358cee397','3','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-21 01:20:16'),(89,1,NULL,'(no comment)','Review recorded','2026-07-21 01:20:20'),(90,NULL,'a3b8230e-aa3c-4553-8ef1-6eb358cee397','skip','Thank you for your feedback!\n\nYou rated us 3/5. We appreciate you taking the time to help us improve.\n---\nIs there anything else I can help you with?','2026-07-21 01:20:20'),(91,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','submit review/rating','How would you rate your experience with us?','2026-07-21 01:21:55'),(92,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-21 01:21:59'),(93,1,NULL,'(no comment)','Review recorded','2026-07-21 01:22:07'),(94,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','skip','Thank you for your feedback!\n\nYou rated us 5/5. We appreciate you taking the time to help us improve.\n---\nIs there anything else I can help you with?','2026-07-21 01:22:07'),(95,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','submit complaint','Please describe your concern in detail. We would like to hear your experience from us>','2026-07-21 01:22:12'),(96,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','ang lala ng staff niyo, antatamad','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-21 03:45:04'),(97,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','submit review/rating','How would you rate your experience with us?','2026-07-21 03:45:25'),(98,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','5','Would you like to add any comments? (Or type \"skip\" to submit without comments)','2026-07-21 03:45:48'),(99,1,NULL,'sobrang slay niyo','Review recorded','2026-07-21 03:45:59'),(100,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','sobrang slay niyo','Thank you for your feedback!\n\nYou rated us 5/5. We appreciate you taking the time to help us improve.\n---\nIs there anything else I can help you with?','2026-07-21 03:45:59'),(101,NULL,'c560bb92-eb26-4c41-ae55-29ca580520d3','submit review/rating','How would you rate your experience with us?','2026-07-21 04:06:59'),(102,NULL,'1c547a54-82d3-44e7-a7ea-6044b22c4a9c','Magkano po consultation sainyo?','Thanks for your message! I\'ve forwarded it to our Admin/Staff team — they\'ll reply to you here shortly.','2026-07-21 06:42:30');
/*!40000 ALTER TABLE `chatbot_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_info`
--

DROP TABLE IF EXISTS `clinic_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_info` (
  `clinic_id` int NOT NULL,
  `clinic_name` varchar(200) NOT NULL,
  `address` text,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `operating_hours` varchar(255) DEFAULT NULL,
  `about_us` text,
  `mission` text,
  `vision` text,
  `core_values` json DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_info`
--

LOCK TABLES `clinic_info` WRITE;
/*!40000 ALTER TABLE `clinic_info` DISABLE KEYS */;
INSERT INTO `clinic_info` VALUES (1,'PolyClinic Lipa','TM Kalaw St., Lipa City, Batangas 4217','0985 475 5511','polycliniclipa@gmail.com','Monday - Friday: 8:00 AM - 6:00 PM | Saturday: 9:00 AM - 1:00 PM','PolyClinic Lipa is a multi-specialty outpatient clinic serving the community of Lipa City since 2011. We are committed to providing quality, \ncompassionate, and affordable healthcare for every Filipino family.',NULL,NULL,NULL,'https://www.facebook.com/polycliniclipa',NULL,NULL);
/*!40000 ALTER TABLE `clinic_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaints`
--

DROP TABLE IF EXISTS `complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `complaints` (
  `complaint_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `log_id` int DEFAULT NULL,
  `complaint_text` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`complaint_id`),
  KEY `fk_complaints_patient` (`patient_id`),
  KEY `fk_complaints_log` (`log_id`),
  CONSTRAINT `fk_complaint_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaints_log` FOREIGN KEY (`log_id`) REFERENCES `chatbot_logs` (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
INSERT INTO `complaints` VALUES (1,1,NULL,'The waiting time before consultation was too long.',NULL,'2026-07-18 12:55:48'),(2,2,NULL,'The receptionist was not accommodating during check-in.',NULL,'2026-07-18 12:55:48'),(3,3,NULL,'The prescribed medicine was unavailable at the pharmacy.',NULL,'2026-07-18 12:55:48'),(4,4,NULL,'The consultation room was not clean upon arrival.',NULL,'2026-07-18 12:55:48'),(5,5,NULL,'My appointment started much later than the scheduled time.',NULL,'2026-07-18 12:55:48'),(6,6,NULL,'The laboratory results took longer than expected to be released.',NULL,'2026-07-18 12:55:48'),(7,7,NULL,'The doctor did not clearly explain my diagnosis and treatment.',NULL,'2026-07-18 12:55:48'),(8,8,NULL,'The waiting area was overcrowded and lacked enough seating.',NULL,'2026-07-18 12:55:48'),(9,9,NULL,'The online appointment booking system was difficult to use.',NULL,'2026-07-18 12:55:48'),(10,10,NULL,'The clinic restroom needed better cleanliness and maintenance.',NULL,'2026-07-18 12:55:48'),(11,NULL,4,'masama ugali ng staff',NULL,'2026-07-20 09:20:03'),(12,NULL,5,'Masama ugali niyo',NULL,'2026-07-20 09:45:02'),(13,NULL,50,'Hindi magaling ang doctor',NULL,'2026-07-20 20:19:43'),(14,NULL,53,'and tagal, ang haba ng pila',NULL,'2026-07-20 20:42:30'),(15,NULL,58,'Hindi marunong magpaliwag doctor dito',NULL,'2026-07-20 21:33:39'),(16,NULL,61,'napaka sungit ng staff dito',NULL,'2026-07-20 21:43:17'),(17,14,75,'ang sungit ng nurse nyo.',NULL,'2026-07-21 01:13:37');
/*!40000 ALTER TABLE `complaints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_schedules`
--

DROP TABLE IF EXISTS `doctor_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_schedules` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `fk_doctor_schedule_doctor` (`doctor_id`),
  CONSTRAINT `fk_doctor_schedule_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_schedules`
--

LOCK TABLES `doctor_schedules` WRITE;
/*!40000 ALTER TABLE `doctor_schedules` DISABLE KEYS */;
INSERT INTO `doctor_schedules` VALUES (18,5,'Monday','09:00:00','09:00:00'),(19,5,'Wednesday','11:00:00','11:00:00'),(20,6,'Wednesday','11:00:00','13:00:00'),(21,7,'Tuesday','13:00:00','13:00:00'),(22,7,'Thursday','13:00:00','13:00:00'),(23,7,'Saturday','13:00:00','13:00:00'),(24,8,'Monday','13:00:00','13:00:00'),(25,8,'Friday','13:00:00','13:00:00'),(26,9,'Saturday','10:00:00','12:00:00'),(27,10,'Tuesday','10:00:00','12:00:00'),(28,10,'Thursday','10:00:00','12:00:00'),(29,11,'Monday','10:00:00','15:00:00'),(30,11,'Tuesday','10:00:00','15:00:00'),(31,11,'Wednesday','10:00:00','15:00:00'),(32,11,'Thursday','10:00:00','15:00:00'),(33,11,'Friday','10:00:00','15:00:00'),(34,11,'Saturday','10:00:00','15:00:00'),(35,11,'Sunday','10:00:00','12:00:00'),(36,12,'Tuesday','10:00:00','12:00:00'),(37,13,'Monday','10:00:00','13:00:00'),(38,13,'Friday','10:00:00','13:00:00'),(39,13,'Saturday','10:00:00','13:00:00'),(40,13,'Sunday','10:00:00','13:00:00'),(41,14,'Monday','10:00:00','13:00:00'),(42,14,'Friday','10:00:00','13:00:00'),(43,14,'Saturday','10:00:00','13:00:00'),(44,14,'Sunday','10:00:00','13:00:00'),(75,1,'Monday','08:00:00','17:00:00'),(76,1,'Tuesday','08:00:00','17:00:00'),(77,1,'Wednesday','08:00:00','12:00:00'),(78,3,'Monday','09:00:00','10:00:00'),(79,3,'Friday','09:00:00','10:00:00'),(80,3,'Saturday','09:00:00','10:00:00'),(81,3,'Sunday','09:00:00','10:00:00'),(82,4,'Monday','12:00:00','14:00:00');
/*!40000 ALTER TABLE `doctor_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctors` (
  `doctor_id` int NOT NULL AUTO_INCREMENT,
  `doctor_name` varchar(150) NOT NULL,
  `specialty` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `description` text,
  `contact_number` varchar(20) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL DEFAULT 'male',
  `years_experience` int DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `license` varchar(255) DEFAULT NULL,
  `clinic_room` varchar(100) DEFAULT NULL,
  `fellowship` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (1,'Dr. Jose Recio','Ophthalmology / General Medicine','Active','Dr. Jose Gabriel Recio specializes in Ophthalmology and General.','093456678988','male',7,NULL,NULL,NULL,NULL,'doctors/O0ddzfiTm4xAbgNGWohtBEoN4bWuflUFvxbCLcWu.jpg',1),(3,'Dr. Alfredo Lorenzo Sablay','General / Adult Medicine','Active','Dr. Alfredo Lorenzo Sablay is committed to adult health and wellness.',NULL,'male',6,NULL,NULL,NULL,NULL,'doctors/QKU67zCSckPSwyGeln9iwTTR64Pf4e3wxoNVRn3h.jpg',1),(4,'Dr. Lauren Victoria Rellora','Pediatrician','Active','Dr. Lauren Victoria Rellora is a skilled surgeon.',NULL,'male',6,NULL,NULL,NULL,NULL,'doctors/gylXtUDb0rLDV414fCEJN65EY9mF5H7CHdyNDD1V.jpg',1),(5,'Dr. Angelo Rocafort','Surgery','Active','Dr. Angelo Rocafort is an experienced surgeon.',NULL,'male',5,NULL,NULL,NULL,NULL,NULL,1),(6,'Dr. Louiza Erika Rellora','Surgery','Active','Dr. Louiza Erika Rellora specializes in surgical procedures.',NULL,'male',9,NULL,NULL,NULL,NULL,NULL,1),(7,'Dr. Bianca Templo Serrano','OB-Gyne','Active','Dr. Bianca Templo Serrano offers comprehensive women\'s health services.',NULL,'male',7,NULL,NULL,NULL,NULL,NULL,1),(8,'Dr. Marian Magpantay','OB-Gyne','Active','Dr. Marian Magpantay is a trusted OB-GYN specialist.',NULL,'male',6,NULL,NULL,NULL,NULL,NULL,1),(9,'Dr. Paulinemielle Bernardo-Recio','Internal Medicine','Active','Dr. Paulinemielle Bernardo-Recio specializes in Internal Medicine. Welcome to our team!',NULL,'male',9,NULL,NULL,NULL,NULL,NULL,1),(10,'Dr. Rachelle Diane Maravilla','Medical Oncology','Active','Dr. Rachelle Diane Maravilla specializes in Medical Oncology. Welcome to our team!',NULL,'male',6,NULL,NULL,NULL,NULL,NULL,1),(11,'Dr. Amelia Rellora','Pediatrics','Active','Dr. Amelia Rellora is a dedicated pediatrician with over 18 years of experience.',NULL,'male',5,NULL,NULL,NULL,NULL,NULL,1),(12,'Dr. Jener De Castro','IM-Pulmonology','Active','Dr. Jener De Castro specializes in pulmonary medicine.',NULL,'male',8,NULL,NULL,NULL,NULL,NULL,1),(13,'Dr. Louis Alfred Rellora','General / Adult Medicine','Active','Dr. Louis Alfred Rellora provides adult and general medical care.',NULL,'male',8,NULL,NULL,NULL,NULL,NULL,1),(14,'Dr. Lorenzo Sablay','General / Adult Medicine','Inactive','Dr. Lorenzo Sablay is committed to adult health and wellness.',NULL,'male',6,NULL,NULL,NULL,NULL,NULL,1),(15,'Nadine Lustre','Dermatologist','Active','Botox','09369329908','male',7,NULL,NULL,NULL,NULL,NULL,1),(16,'Mark Roque','Adult Medicine','Active','20 years in service','09231776395','male',7,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `feedback_id` int NOT NULL AUTO_INCREMENT,
  `log_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `feedback_text` text,
  `star_rating` int NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`),
  KEY `fk_feedback_patient` (`patient_id`),
  KEY `fk_feedback_log` (`log_id`),
  CONSTRAINT `fk_feedback_log` FOREIGN KEY (`log_id`) REFERENCES `chatbot_logs` (`log_id`),
  CONSTRAINT `fk_feedback_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,NULL,NULL,'Napakabait ng doctor pero ang tagal ng waiting time',3,'2026-07-13 16:11:53'),(2,NULL,NULL,'The doctor was very accommodating and explained everything clearly.',5,'2026-07-14 01:15:00'),(3,NULL,NULL,'The waiting time was a bit long but the service was good.',4,'2026-07-14 01:30:00'),(4,NULL,NULL,'Friendly staff and clean clinic.',5,'2026-07-14 01:45:00'),(5,NULL,NULL,'The consultation felt rushed.',3,'2026-07-14 02:00:00'),(6,NULL,NULL,'Excellent experience from check-in to consultation.',5,'2026-07-14 02:15:00'),(7,NULL,NULL,'The nurse was very helpful and polite.',4,'2026-07-14 02:30:00'),(8,NULL,NULL,'Appointment started on time. Great service!',5,'2026-07-14 02:45:00'),(9,NULL,NULL,'The clinic was crowded and had a long queue.',2,'2026-07-14 03:00:00'),(10,NULL,NULL,'Doctor answered all my questions patiently.',5,'2026-07-14 03:15:00'),(11,NULL,NULL,'Overall good experience, but parking was difficult.',4,'2026-07-14 03:30:00'),(12,NULL,NULL,'The receptionist was courteous and helpful.',5,'2026-07-14 03:45:00'),(13,NULL,NULL,'Had to wait over an hour before being called.',2,'2026-07-14 04:00:00'),(14,NULL,NULL,'Professional doctor and very informative consultation.',5,'2026-07-14 04:15:00'),(15,NULL,NULL,'Facilities were clean and organized.',4,'2026-07-14 04:30:00'),(16,NULL,NULL,'Expected a faster process.',3,'2026-07-14 04:45:00'),(17,NULL,NULL,'The doctor listened carefully to my concerns.',5,'2026-07-14 05:00:00'),(18,NULL,NULL,'Very satisfied with the medical care provided.',5,'2026-07-14 05:15:00'),(19,NULL,NULL,'Service was okay but could be improved.',3,'2026-07-14 05:30:00'),(20,NULL,NULL,'Staff were friendly and professional.',4,'2026-07-14 05:45:00'),(21,NULL,NULL,'Highly recommended clinic. Will definitely come back.',5,'2026-07-14 06:00:00'),(22,NULL,NULL,'Mabait naman yung doctor at in-explain nang maayos yung condition ko. Satisfied ako.',5,'2026-07-15 01:00:00'),(23,NULL,NULL,'Okay yung service pero medyo matagal yung waiting time. Sana mas mabilis next time.',4,'2026-07-15 01:30:00'),(24,NULL,NULL,'Friendly yung staff pero medyo nalito ako sa process ng registration.',3,'2026-07-15 02:00:00'),(25,NULL,NULL,'Super bait ng nurse at very accommodating sila. Comfortable yung buong visit ko.',5,'2026-07-15 02:30:00'),(26,NULL,NULL,'Maayos yung consultation pero medyo crowded yung clinic. Overall okay pa rin.',4,'2026-07-15 03:00:00'),(27,2,NULL,'Service ay mabagal',3,'2026-07-20 09:13:42'),(28,35,NULL,'I liked how the doctors treated me',5,'2026-07-20 15:37:08'),(29,39,NULL,'Good service, im satisfied',5,'2026-07-20 20:07:16'),(30,43,NULL,'Good service',5,'2026-07-20 20:08:08'),(31,47,NULL,'Good service, im deeply satified',5,'2026-07-20 20:17:17'),(32,79,NULL,'Sana mas mabilis ang process next time.',3,'2026-07-21 01:14:49'),(33,85,NULL,'submit review/rating',0,'2026-07-21 01:19:55'),(34,89,NULL,NULL,3,'2026-07-21 01:20:20'),(35,93,NULL,NULL,5,'2026-07-21 01:22:07'),(36,99,NULL,'sobrang slay niyo',5,'2026-07-21 03:45:59');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiries` (
  `inquiry_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `log_id` int NOT NULL,
  `conversation_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `inquiry_type` varchar(100) DEFAULT NULL,
  `resolved_status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `inquiry_reply` text,
  `replied_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`inquiry_id`),
  KEY `idx_inquiries_conversation_id` (`conversation_id`),
  KEY `fk_inquiries_patient` (`patient_id`),
  KEY `fk_inquiries_log` (`log_id`),
  CONSTRAINT `fk_inquiries_log` FOREIGN KEY (`log_id`) REFERENCES `chatbot_logs` (`log_id`),
  CONSTRAINT `fk_inquiries_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
INSERT INTO `inquiries` VALUES (1,NULL,NULL,6,'d772c9e6-8298-4e66-862d-bc16971623c4','2026-07-20 10:25:01','General','Resolved','what','2026-07-20 11:47:45'),(2,NULL,NULL,28,'b181c210-9b93-4ae3-99bd-7e5d7412f14e','2026-07-20 11:20:51','General','In Progress','sige','2026-07-21 01:24:56'),(3,NULL,NULL,55,'13a2a0ea-6791-417b-8c47-50dcc36fa04e','2026-07-20 21:07:02','General','Resolved','Schedule a Visit po, Ma\'am/Sir for Pedia','2026-07-20 21:08:03'),(4,NULL,NULL,82,'c560bb92-eb26-4c41-ae55-29ca580520d3','2026-07-21 01:16:06','General','In Progress','1000 for pedia','2026-07-21 01:17:17'),(5,NULL,NULL,102,'1c547a54-82d3-44e7-a7ea-6044b22c4a9c','2026-07-21 06:42:30','General','Resolved','1000','2026-07-21 06:43:16');
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiry_replies`
--

DROP TABLE IF EXISTS `inquiry_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiry_replies` (
  `reply_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inquiry_id` int NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `sender` varchar(100) NOT NULL DEFAULT 'Staff',
  `is_staff` tinyint(1) NOT NULL DEFAULT '1',
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`reply_id`),
  KEY `idx_inquiry_id` (`inquiry_id`),
  CONSTRAINT `fk_inquiry_reply` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`inquiry_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiry_replies`
--

LOCK TABLES `inquiry_replies` WRITE;
/*!40000 ALTER TABLE `inquiry_replies` DISABLE KEYS */;
INSERT INTO `inquiry_replies` VALUES (1,1,NULL,'Patient',1,'pediatrics','2026-07-20 10:27:01','2026-07-20 10:27:01'),(2,1,NULL,'Patient',1,'ophthalmology','2026-07-20 10:27:09','2026-07-20 10:27:09'),(3,1,2,'Admin',1,'what','2026-07-20 11:47:45','2026-07-20 11:47:45'),(4,3,2,'Admin',1,'Schedule a Visit po, Ma\'am/Sir for Pedia','2026-07-20 21:08:03','2026-07-20 21:08:03'),(5,4,2,'Admin',1,'1000 for pedia','2026-07-21 01:17:17','2026-07-21 01:17:17'),(6,2,4,'Staff',1,'sige','2026-07-21 01:24:56','2026-07-21 01:24:56'),(7,4,NULL,'Patient',1,'ang lala ng staff niyo, antatamad','2026-07-21 03:45:05','2026-07-21 03:45:05'),(8,5,2,'Admin',1,'1000','2026-07-21 06:43:16','2026-07-21 06:43:16');
/*!40000 ALTER TABLE `inquiry_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_30_090536_create_personal_access_tokens_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('gmikaloyola@gmail.com','593610','2026-07-21 01:48:00');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `patient_id` int NOT NULL AUTO_INCREMENT,
  `patient_fname` varchar(100) DEFAULT NULL,
  `patient_lname` varchar(100) DEFAULT NULL,
  `patient_birthdate` date DEFAULT NULL,
  `patient_email` varchar(255) DEFAULT NULL,
  `patient_contact` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'Maria','Santos','1990-03-14','maria.santos@gmail.com','09171234501'),(2,'Juan','Dela Cruz','1985-07-22','juan.delacruz@gmail.com','09171234502'),(3,'Ana','Reyes','1998-11-05','ana.reyes@gmail.com','09171234503'),(4,'Pedro','Garcia','1975-01-30','pedro.garcia@gmail.com','09171234504'),(5,'Liza','Torres','2001-06-18','liza.torres@gmail.com','09171234505'),(6,'Carlos','Mendoza','1992-09-09','carlos.mendoza@gmail.com','09171234506'),(7,'Grace','Villanueva','1988-12-25','grace.villanueva@gmail.com','09171234507'),(8,'Ramon','Bautista','1979-04-11','ramon.bautista@gmail.com','09171234508'),(9,'Marilyn','Aquino','1995-08-02','sofia.aquino@gmail.com','09171234509'),(10,'Miguel','Fernandez','1983-02-17','miguel.fernandez@gmail.com','09171234510'),(11,'Marimar','Flores','1990-05-15','gmikaloyola@gmail.com','09231776294'),(12,'Maricar','Reyes','1990-05-15','gmikaloyola@gmail.com','09231776295'),(13,'Graciella','Loyola','1990-05-15','23-38093@g.batstate-u.edu.ph','09754543458'),(14,'Noemi','Loyola','2002-05-12','gepiganoemi@gmail.com','09095564533');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=175 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\admin_models\\User',2,'web-or-mobile','d891c7d3b2a28ab273db56d3ebe0710b02317564581327e3bd8c8c673f89669e','[\"*\"]',NULL,NULL,'2026-07-07 11:54:01','2026-07-07 11:54:01'),(2,'App\\Models\\admin_models\\User',2,'web-or-mobile','17bba0954493c47e48a2ffa4138b59757c89251f7e697f6151bc768030f9e03c','[\"*\"]',NULL,NULL,'2026-07-07 14:33:17','2026-07-07 14:33:17'),(3,'App\\Models\\admin_models\\User',2,'web-or-mobile','7589aab3eb6d5354ec08818f2e3f0c7556a737d793ef5a9030244a27b0449dd0','[\"*\"]',NULL,NULL,'2026-07-07 14:48:40','2026-07-07 14:48:40'),(4,'App\\Models\\admin_models\\User',4,'web-or-mobile','1c57eb9791930651bfed6919e6e01baf5a43871d2a3b5263b2133b95b3df529e','[\"*\"]',NULL,NULL,'2026-07-07 14:52:39','2026-07-07 14:52:39'),(5,'App\\Models\\admin_models\\User',4,'web-or-mobile','e1cee52b3357238621e23d02af3f54addd3ff5672a73863f21e9991726437b18','[\"*\"]',NULL,NULL,'2026-07-07 14:59:32','2026-07-07 14:59:32'),(6,'App\\Models\\admin_models\\User',2,'mobile-app','88bc79518c92c7e021ab9fee22fc47a1065e51619f76fd2c030a0e07555700b5','[\"*\"]',NULL,NULL,'2026-07-07 16:50:17','2026-07-07 16:50:17'),(7,'App\\Models\\admin_models\\User',2,'mobile-app','fa5336610c1b0d783ea852ac8a818717bc96604b9f449e403f792c03df63ebcf','[\"*\"]',NULL,NULL,'2026-07-07 16:50:31','2026-07-07 16:50:31'),(8,'App\\Models\\admin_models\\User',2,'mobile-app','895a5bf09d52d09659fe0ea75673f50fd9110a77b6a5fd11f59d5497ab23bbbb','[\"*\"]',NULL,NULL,'2026-07-07 16:54:58','2026-07-07 16:54:58'),(9,'App\\Models\\admin_models\\User',5,'mobile-app','2e692563f16b48c3f05b794e2058dc350a790c3c48d2ce513d70d47ed4a48798','[\"*\"]',NULL,NULL,'2026-07-07 18:36:19','2026-07-07 18:36:19'),(10,'App\\Models\\admin_models\\User',2,'mobile-token','06e71ec45ca2ca0d9add8c7607f5b405b19116622156087e2b935de1f05dcb31','[\"*\"]',NULL,NULL,'2026-07-12 19:08:16','2026-07-12 19:08:16'),(11,'App\\Models\\admin_models\\User',2,'mobile-token','1cafe80a963dd5dc2f74d2e503042633b90f091170064274ec8e229427f1a1b3','[\"*\"]',NULL,NULL,'2026-07-12 19:16:36','2026-07-12 19:16:36'),(12,'App\\Models\\admin_models\\User',2,'mobile-token','d138ac2ef0143c82b74d8a32cbe3b27f561da00b2eeb640011f6fd0b1591ce2d','[\"*\"]',NULL,NULL,'2026-07-13 00:24:49','2026-07-13 00:24:49'),(13,'App\\Models\\admin_models\\User',2,'mobile-token','74e16a2d927fd5ea45473265b1c42d7f4164f9421f808eee3669e8a2412d8601','[\"*\"]',NULL,NULL,'2026-07-13 00:45:52','2026-07-13 00:45:52'),(14,'App\\Models\\admin_models\\User',2,'mobile-token','ae476502430663d91ae9d54cd49864b85bb6163d10d078613ec6294b35084193','[\"*\"]',NULL,NULL,'2026-07-13 00:50:22','2026-07-13 00:50:22'),(15,'App\\Models\\admin_models\\User',2,'mobile-token','988e41976c8899c957333ea006659a0af6a156faee01c522e51907680a7f3141','[\"*\"]',NULL,NULL,'2026-07-13 00:55:26','2026-07-13 00:55:26'),(16,'App\\Models\\admin_models\\User',2,'mobile-token','40812d509362296f5ca8e8e7d814b3f5e0f60dd5e081c26d3b1a782881eba817','[\"*\"]','2026-07-13 01:03:16',NULL,'2026-07-13 00:58:36','2026-07-13 01:03:16'),(17,'App\\Models\\admin_models\\User',4,'mobile-token','428e8d0e2631c3e4f9fd59c15cdac742ea7d96c9f6e945b83c35a06034f2cf44','[\"*\"]',NULL,NULL,'2026-07-13 01:24:42','2026-07-13 01:24:42'),(18,'App\\Models\\admin_models\\User',4,'mobile-token','f5d4f003d92c2f9789139099126eb10fd04cfbb64e59cd8deac77e87b5efecef','[\"*\"]',NULL,NULL,'2026-07-13 01:51:39','2026-07-13 01:51:39'),(19,'App\\Models\\admin_models\\User',4,'mobile-token','78550dd8404b6cc3c50afc59db78658119c733579f2d6d965db499c3917829ca','[\"*\"]','2026-07-13 01:55:23',NULL,'2026-07-13 01:55:02','2026-07-13 01:55:23'),(20,'App\\Models\\admin_models\\User',4,'mobile-token','569c278db2e116fd27ebd9f4e2b8d0bf61982c20f1a396df4133a5ce78a4481e','[\"*\"]',NULL,NULL,'2026-07-13 02:01:40','2026-07-13 02:01:40'),(21,'App\\Models\\admin_models\\User',2,'mobile-token','79b9737d4ae19baa97b993e4b322bfa1e64fb78b3719b8a90f44c85bb3f6516e','[\"*\"]',NULL,NULL,'2026-07-13 02:07:38','2026-07-13 02:07:38'),(22,'App\\Models\\admin_models\\User',2,'mobile-token','af81732afc430a7f599fa8957e81664ae07d2425db972dbf543ad94bf8c3a4ee','[\"*\"]',NULL,NULL,'2026-07-13 07:48:03','2026-07-13 07:48:03'),(23,'App\\Models\\admin_models\\User',2,'mobile-token','67ac4a1a8b754263c4ada3e56fa9f22e72a4b5faea1b2782420c2c241c6fbaa8','[\"*\"]',NULL,NULL,'2026-07-13 08:17:44','2026-07-13 08:17:44'),(24,'App\\Models\\admin_models\\User',2,'mobile-token','55ae2c38613279921eaa95aab80198aa2bc70a6854b253417fbfb47e39a0072c','[\"*\"]','2026-07-13 08:39:06',NULL,'2026-07-13 08:26:28','2026-07-13 08:39:06'),(25,'App\\Models\\admin_models\\User',2,'mobile-app','83ee58d48f15caa97a52a672566084e2876dd3b9ba32822a6b224fcdcdf4d2b3','[\"*\"]','2026-07-13 08:55:19',NULL,'2026-07-13 08:55:13','2026-07-13 08:55:19'),(26,'App\\Models\\admin_models\\User',2,'mobile-app','14d3b62e2ff305396657d2a5e1a64050009f63dcba498112e9764323fd2f0661','[\"*\"]',NULL,NULL,'2026-07-13 08:55:30','2026-07-13 08:55:30'),(27,'App\\Models\\admin_models\\User',4,'mobile-token','a1236bcd4d17cab03c0cc1854f404c15d9d1cd4f4ab9ef5883d57841f7e70f13','[\"*\"]',NULL,NULL,'2026-07-13 08:56:07','2026-07-13 08:56:07'),(28,'App\\Models\\admin_models\\User',2,'mobile-app','0890e81a0883bc973ff3d7e37f3bf9900db387021aa96d3012d9d7063bfaa1ac','[\"*\"]',NULL,NULL,'2026-07-13 15:15:22','2026-07-13 15:15:22'),(29,'App\\Models\\admin_models\\User',4,'mobile-token','ad5bacbc84977af4b7d5c0d03a188a2f4f8ca29cc4a7d61700af6b49bc59b57a','[\"*\"]',NULL,NULL,'2026-07-13 15:15:54','2026-07-13 15:15:54'),(30,'App\\Models\\admin_models\\User',4,'mobile-token','38004340cfb4393351791e529c11228851f49853c9e1225e4eafabd7f6d889e1','[\"*\"]',NULL,NULL,'2026-07-13 15:19:47','2026-07-13 15:19:47'),(31,'App\\Models\\admin_models\\User',4,'mobile-token','56c8e4d43376a49ee87329e0f75d0ed0270e93e76bdf4a7cf5c0f58167335d2a','[\"*\"]','2026-07-13 15:38:36',NULL,'2026-07-13 15:36:46','2026-07-13 15:38:36'),(32,'App\\Models\\admin_models\\User',5,'mobile-app','22ca115dfa4e14e976a8402649efdaaecb96a20166181d7be01e843d9ec2e0d5','[\"*\"]',NULL,NULL,'2026-07-14 02:14:05','2026-07-14 02:14:05'),(33,'App\\Models\\admin_models\\User',5,'mobile-app','0c551915ebf33e210bd33d8239d9b33bc5ccb49f9dd82b6b94f221c0f0d35f8c','[\"*\"]',NULL,NULL,'2026-07-14 02:15:00','2026-07-14 02:15:00'),(34,'App\\Models\\admin_models\\User',2,'mobile-token','99ac8739f434539d9b4f38d3bf10cca04708158a68b8f8f310d966912020d1cd','[\"*\"]','2026-07-14 02:16:09',NULL,'2026-07-14 02:15:25','2026-07-14 02:16:09'),(35,'App\\Models\\admin_models\\User',4,'mobile-token','ed0c9dac1e90a9e28bfa8e1b39e92e563ed673fe6938a732619a01f3c83038e6','[\"*\"]',NULL,NULL,'2026-07-13 18:24:04','2026-07-13 18:24:04'),(36,'App\\Models\\admin_models\\User',2,'mobile-token','83aea408c3fae42a0aa7826002a83b8593f4105ad2f2cfd28a0fb9cd7a96d76e','[\"*\"]',NULL,NULL,'2026-07-13 18:28:02','2026-07-13 18:28:02'),(37,'App\\Models\\admin_models\\User',2,'mobile-token','93b92c5f02eb5d51c907b4feb3394e67ba9cc8ac6fe39bc7a5e1c2d7dba9d9cc','[\"*\"]',NULL,NULL,'2026-07-13 19:48:20','2026-07-13 19:48:20'),(38,'App\\Models\\admin_models\\User',4,'mobile-token','6dfe5b666ad3e0bc082ea1eb7f2fc7c2a9babc35a52671ccfccb28c9d22c52e0','[\"*\"]','2026-07-13 20:56:43',NULL,'2026-07-13 20:50:17','2026-07-13 20:56:43'),(39,'App\\Models\\admin_models\\User',4,'mobile-token','f05f79e1e3e3714e0c68e9c033ddc4251cffceed51625814a5d5c08ccd4d8db3','[\"*\"]',NULL,NULL,'2026-07-14 05:08:20','2026-07-14 05:08:20'),(40,'App\\Models\\admin_models\\User',4,'mobile-token','0c0e774de9719c1ba455218a9d8765e35fe5724805717b9e9de2155a0646bcd1','[\"*\"]',NULL,NULL,'2026-07-14 05:27:56','2026-07-14 05:27:56'),(41,'App\\Models\\admin_models\\User',4,'mobile-token','a4614ec36a19eb173ade9529f3a2c9c89459531067b654590d8e4faa96146a6a','[\"*\"]','2026-07-14 05:47:05',NULL,'2026-07-14 05:32:54','2026-07-14 05:47:05'),(42,'App\\Models\\admin_models\\User',2,'mobile-token','7bf2736b60e6a252d5ff933c8e51a1c6e4a33513d1f4e8bee43aa36413269881','[\"*\"]',NULL,NULL,'2026-07-14 05:47:26','2026-07-14 05:47:26'),(43,'App\\Models\\admin_models\\User',2,'mobile-token','1d9efff0f03e434cd553402c4b6157fa758f998969327eba9de3302d34eba068','[\"*\"]',NULL,NULL,'2026-07-14 05:53:11','2026-07-14 05:53:11'),(44,'App\\Models\\admin_models\\User',2,'mobile-token','fc48cddc9e6f8e049d4f500d12aad4353af708e75c4fb74d36fa84b19bb2648f','[\"*\"]',NULL,NULL,'2026-07-14 05:58:36','2026-07-14 05:58:36'),(45,'App\\Models\\admin_models\\User',2,'mobile-token','0baec0139b3b06fae02acb1ec246b8c0f43854578ce53b4e24f65495517b9f61','[\"*\"]',NULL,NULL,'2026-07-14 06:19:44','2026-07-14 06:19:44'),(46,'App\\Models\\admin_models\\User',2,'mobile-token','875db8ddcf6c556bd29d5618b1b0751928e893b9d74ce68dac16e05a1a2575dc','[\"*\"]',NULL,NULL,'2026-07-14 06:32:35','2026-07-14 06:32:35'),(47,'App\\Models\\admin_models\\User',4,'mobile-token','b23cc743494e23e3ecaa8e470cb5a496084303a35d0aa20e2eaae0deb185f172','[\"*\"]',NULL,NULL,'2026-07-14 06:34:40','2026-07-14 06:34:40'),(48,'App\\Models\\admin_models\\User',4,'mobile-token','5efd99caab6f8794f781e8d1f9613f12990e44866efb1870e25cc130e16ced33','[\"*\"]','2026-07-14 06:39:45',NULL,'2026-07-14 06:38:21','2026-07-14 06:39:45'),(49,'App\\Models\\admin_models\\User',2,'mobile-token','93bac15342d7ce12186e7dd7b4118a52f03ad081cbadd64287d77b3041768957','[\"*\"]','2026-07-14 06:43:13',NULL,'2026-07-14 06:40:10','2026-07-14 06:43:13'),(50,'App\\Models\\admin_models\\User',2,'mobile-token','b77b02af0b2ad005071c02aaab7909ac19902e6eb05cdb16776b8b8946f1536f','[\"*\"]',NULL,NULL,'2026-07-14 07:01:03','2026-07-14 07:01:03'),(51,'App\\Models\\admin_models\\User',4,'mobile-token','c0de99d1bdc10415694782f2a271f6b717ac5159672bd6384e64ec4a31819052','[\"*\"]','2026-07-14 07:05:50',NULL,'2026-07-14 07:04:36','2026-07-14 07:05:50'),(52,'App\\Models\\admin_models\\User',2,'mobile-token','ee183f4c1c060bd0636d28a1896d000b75149f38a17485d8c2dd728006d1ed05','[\"*\"]',NULL,NULL,'2026-07-14 09:24:31','2026-07-14 09:24:31'),(53,'App\\Models\\admin_models\\User',2,'mobile-token','ed9f90576b4d13a01333a380dd689962aabe5cb992b85eb58c3e0fa57feecd31','[\"*\"]',NULL,NULL,'2026-07-14 09:45:42','2026-07-14 09:45:42'),(54,'App\\Models\\admin_models\\User',2,'mobile-token','13f8d9847f6d890d35289f4577ef35a5aaa7baf3ae4471561ec0bce6663b17aa','[\"*\"]',NULL,NULL,'2026-07-14 10:37:26','2026-07-14 10:37:26'),(55,'App\\Models\\admin_models\\User',2,'mobile-token','d8966d7998b044b17d75991268c3258115c026d773d28dfa165fef05627e8747','[\"*\"]',NULL,NULL,'2026-07-14 13:04:36','2026-07-14 13:04:36'),(56,'App\\Models\\admin_models\\User',2,'mobile-token','9fb475b1e7e1db8bb107a3f72ec1392717f3b58df9ee2bc79f9141f8b70469b8','[\"*\"]','2026-07-14 13:06:48',NULL,'2026-07-14 13:06:12','2026-07-14 13:06:48'),(57,'App\\Models\\admin_models\\User',2,'mobile-token','fcf299dc483c82a610864fb64336a56205c0da4f957d93f94cf29413610c758a','[\"*\"]',NULL,NULL,'2026-07-14 13:46:25','2026-07-14 13:46:25'),(58,'App\\Models\\admin_models\\User',2,'mobile-token','669a996dace16fa96645b194be08ba0228224b720893c2dc42e8e173e2b7354f','[\"*\"]',NULL,NULL,'2026-07-14 13:49:41','2026-07-14 13:49:41'),(59,'App\\Models\\admin_models\\User',2,'mobile-token','11bf5d57393b9fc305059f55019c632b12ede30e09ef72a962f2f204bea240d6','[\"*\"]','2026-07-14 14:10:59',NULL,'2026-07-14 14:04:13','2026-07-14 14:10:59'),(60,'App\\Models\\admin_models\\User',2,'mobile-token','7a31d5eef6af5fa7437053d099e74f0750293fbef61a69cd1e2e0dc0687055b5','[\"*\"]',NULL,NULL,'2026-07-14 14:15:47','2026-07-14 14:15:47'),(61,'App\\Models\\admin_models\\User',2,'mobile-token','f9a715fd45663261935789a8a2764516488049e5dc4cb65a9556dda5de2a9984','[\"*\"]',NULL,NULL,'2026-07-14 22:24:39','2026-07-14 22:24:39'),(62,'App\\Models\\admin_models\\User',2,'mobile-token','3499828787ec93ce25d5a7e3623f5b27c8ac906004bbb09dafc3b7b94f90eebd','[\"*\"]',NULL,NULL,'2026-07-14 22:26:21','2026-07-14 22:26:21'),(63,'App\\Models\\admin_models\\User',2,'mobile-token','f84278a990089bc9573c8790348b2c3f6449d884532ac4b8eae48f75469e3507','[\"*\"]',NULL,NULL,'2026-07-15 00:24:34','2026-07-15 00:24:34'),(64,'App\\Models\\admin_models\\User',2,'mobile-token','7f530c77c464a52e7554ef44ef56a3eeaec617c5740421dd09d9d549cb648c41','[\"*\"]',NULL,NULL,'2026-07-15 00:24:41','2026-07-15 00:24:41'),(65,'App\\Models\\admin_models\\User',2,'mobile-token','47fbf8fa74c1168103461c1ab57734e1997f74a256fd55f262386fb770f86f94','[\"*\"]',NULL,NULL,'2026-07-15 00:26:24','2026-07-15 00:26:24'),(66,'App\\Models\\admin_models\\User',4,'mobile-token','2c1e68fc505ac2e2d667188e7cf347f2d7f2a72e9f76a5f0342e8f34d0d913e3','[\"*\"]','2026-07-15 00:28:21',NULL,'2026-07-15 00:27:57','2026-07-15 00:28:21'),(67,'App\\Models\\admin_models\\User',2,'mobile-token','e6232bbb3695a414223abcd104ae21ca26a51880d9d7e5e7630c6d7712bf7a5e','[\"*\"]',NULL,NULL,'2026-07-15 01:21:23','2026-07-15 01:21:23'),(68,'App\\Models\\admin_models\\User',2,'mobile-app','20389fb41cc475668f23c27afd3637cc465d32902b0c3e17cc47944ff8613f17','[\"*\"]',NULL,NULL,'2026-07-15 01:26:04','2026-07-15 01:26:04'),(69,'App\\Models\\admin_models\\User',4,'mobile-token','a5940e80390af48177867035f2ed26d28e1c327f8f3ca22513912ff1cfd768e9','[\"*\"]','2026-07-15 01:29:10',NULL,'2026-07-15 01:26:49','2026-07-15 01:29:10'),(70,'App\\Models\\admin_models\\User',4,'mobile-token','a09e082cb9bfaa4db85e35db9610f19b6fa828f6d03bbd27de3e78e2d26db0fc','[\"*\"]','2026-07-15 01:29:53',NULL,'2026-07-15 01:29:34','2026-07-15 01:29:53'),(71,'App\\Models\\admin_models\\User',4,'mobile-token','10411103380a8cd8a6133ee5c964511cbbe4c590c214521a582da7d0d6d03c41','[\"*\"]','2026-07-15 04:31:36',NULL,'2026-07-15 01:30:28','2026-07-15 04:31:36'),(72,'App\\Models\\admin_models\\User',2,'mobile-token','67c79c9fec38cce6d09956f091a8f81d7c5ac1d4017f65f42f14aa99123b3622','[\"*\"]',NULL,NULL,'2026-07-15 01:31:23','2026-07-15 01:31:23'),(73,'App\\Models\\admin_models\\User',2,'mobile-token','3b9b1ef8c4257a91147c3e743403a5e2bcda4444312e7dfc020f67a47e79bd59','[\"*\"]',NULL,NULL,'2026-07-15 01:56:40','2026-07-15 01:56:40'),(74,'App\\Models\\admin_models\\User',2,'mobile-token','0ace77c8ce2e1491e0d5b20c0d2810e424cbe16b6cb5c754eb44c6be8506c104','[\"*\"]',NULL,NULL,'2026-07-15 01:59:46','2026-07-15 01:59:46'),(75,'App\\Models\\admin_models\\User',2,'mobile-token','15316ed49f1445e4567f43194b5a8a948d1c21cc7942fbda43cad3774ae7250a','[\"*\"]',NULL,NULL,'2026-07-15 02:02:53','2026-07-15 02:02:53'),(76,'App\\Models\\admin_models\\User',2,'mobile-token','01d79b3a14f82b933eeddd057ea56ad38039249e28aca00800e2f9f9e9d5b6cf','[\"*\"]',NULL,NULL,'2026-07-15 02:26:12','2026-07-15 02:26:12'),(77,'App\\Models\\admin_models\\User',4,'mobile-token','b3c860f9a6c83280f96d4674f2072439140472a079f96cba0fcca7b30068abc6','[\"*\"]','2026-07-15 02:27:54',NULL,'2026-07-15 02:26:56','2026-07-15 02:27:54'),(78,'App\\Models\\admin_models\\User',2,'mobile-token','186bf51f831efd61a068cd26b7dd9e0a7bda6792eb2b0dd987304eccf4a07606','[\"*\"]',NULL,NULL,'2026-07-15 02:28:14','2026-07-15 02:28:14'),(79,'App\\Models\\admin_models\\User',2,'mobile-app','80f423c4ea1ac3d84c2a187254affe6df65a1dc869d34a6b377a940558fb3cb7','[\"*\"]',NULL,NULL,'2026-07-15 03:09:23','2026-07-15 03:09:23'),(80,'App\\Models\\admin_models\\User',2,'mobile-token','3182d7598c71960ab845db731e5ec53b5e05ef9ffa48c9f0e72b17c1f7cced0a','[\"*\"]',NULL,NULL,'2026-07-15 03:45:37','2026-07-15 03:45:37'),(81,'App\\Models\\admin_models\\User',2,'mobile-token','412e576a87b50d0e869b0c0676f206c6620485a1b6d0d2d97f7841e472a2c475','[\"*\"]','2026-07-15 04:32:28',NULL,'2026-07-15 04:04:15','2026-07-15 04:32:28'),(82,'App\\Models\\admin_models\\User',4,'mobile-token','3af84bdb16dad4a5b4b0f745b25d2ec5ea64f8d5ce5dd613fc3d7851a54787d2','[\"*\"]','2026-07-15 06:02:50',NULL,'2026-07-15 05:47:15','2026-07-15 06:02:50'),(83,'App\\Models\\admin_models\\User',2,'mobile-app','b9e1678c6b12b419dba99f878cfda45ad9048d1a1c5a5d47855f9a27c3d59951','[\"*\"]',NULL,NULL,'2026-07-15 06:05:21','2026-07-15 06:05:21'),(84,'App\\Models\\admin_models\\User',2,'mobile-app','651bdb68abe6b8ea71ad08bafe0c2f8b879c906b795a2cf135d75ab3fa958543','[\"*\"]',NULL,NULL,'2026-07-15 06:06:56','2026-07-15 06:06:56'),(85,'App\\Models\\admin_models\\User',5,'mobile-app','ad57098634a169b5c47a0ac6cf0d1a91ac4a424559f550c609b2a1694d9259fa','[\"*\"]','2026-07-15 06:11:23',NULL,'2026-07-15 06:11:19','2026-07-15 06:11:23'),(86,'App\\Models\\admin_models\\User',4,'mobile-token','922504b6249deb3b7f0d0bf64cfca56bff60e66efd3b9b742f3e370651e68f1d','[\"*\"]','2026-07-15 06:13:52',NULL,'2026-07-15 06:13:46','2026-07-15 06:13:52'),(87,'App\\Models\\admin_models\\User',2,'mobile-token','aab33d2f9986127d3f00b4cbb6969bdfe232b79f7cdd087d9e017a5794f90dfb','[\"*\"]','2026-07-16 02:46:25',NULL,'2026-07-16 02:44:09','2026-07-16 02:46:25'),(88,'App\\Models\\admin_models\\User',2,'mobile-token','359b8f007a8849c77a81f5ea64629ba288443338cd9e662b3abcc46ecdcbbca5','[\"*\"]',NULL,NULL,'2026-07-16 04:18:18','2026-07-16 04:18:18'),(89,'App\\Models\\admin_models\\User',2,'mobile-token','0db8605a5e5d0479b6b506debd0b3f010229cea5128bfd43de9f14f1de2edfb7','[\"*\"]','2026-08-02 09:36:47',NULL,'2026-07-16 04:18:20','2026-08-02 09:36:47'),(90,'App\\Models\\admin_models\\User',2,'mobile-app','50f4c8daa8a3463cfe18ad3bfc02f5e0ed9dd44514525aeb4bc3f3275f22d558','[\"*\"]',NULL,NULL,'2026-07-16 14:20:33','2026-07-16 14:20:33'),(92,'App\\Models\\admin_models\\User',2,'mobile-token','77962bc7ab797cccc01b59df8fe2f512fb5dcb34c009db7e93093841cb33f3ca','[\"*\"]',NULL,NULL,'2026-07-16 16:21:31','2026-07-16 16:21:31'),(93,'App\\Models\\admin_models\\User',2,'mobile-token','eb774a4d406a112c1f5b3564532db313d40bd70d0f518bb66a0262de733bb982','[\"*\"]',NULL,NULL,'2026-07-16 16:53:31','2026-07-16 16:53:31'),(94,'App\\Models\\admin_models\\User',2,'mobile-token','d81a0272bcb6d4e019902af46213e9c929245f70a9f3ddc8b97391bf1b533b3f','[\"*\"]',NULL,NULL,'2026-07-17 14:09:41','2026-07-17 14:09:41'),(95,'App\\Models\\admin_models\\User',2,'mobile-token','46ac9b4fd1d9d617a38a72606196f954c4a1046b86d72e66c1976db65b0c7bf9','[\"*\"]',NULL,NULL,'2026-07-17 14:10:04','2026-07-17 14:10:04'),(96,'App\\Models\\admin_models\\User',2,'mobile-token','d80fbf9d707b452ef1283e5efd047cfe41335d23d995c839b94e6b7d57989b5f','[\"*\"]',NULL,NULL,'2026-07-17 14:51:59','2026-07-17 14:51:59'),(97,'App\\Models\\admin_models\\User',2,'mobile-token','4b369f03bb0d9adc795cd1bb26a2bde1391ea2c505546e57862493e2e638709b','[\"*\"]',NULL,NULL,'2026-07-17 14:55:36','2026-07-17 14:55:36'),(98,'App\\Models\\admin_models\\User',2,'mobile-token','9d17d483da1b952af2d37e4401d52443086e965d7e4c34e20a80d954b981c652','[\"*\"]',NULL,NULL,'2026-07-17 15:03:23','2026-07-17 15:03:23'),(99,'App\\Models\\admin_models\\User',2,'mobile-token','79b6bd87bac03fd55f9b09240ca92ce52af87202050de5f88fab2107b5f7fb6c','[\"*\"]',NULL,NULL,'2026-07-17 15:54:33','2026-07-17 15:54:33'),(100,'App\\Models\\admin_models\\User',2,'mobile-token','9e7af0fe122a87f376d6ffdb9d68199df1be3af680e4a58219d6221206a13699','[\"*\"]',NULL,NULL,'2026-07-17 15:54:39','2026-07-17 15:54:39'),(101,'App\\Models\\admin_models\\User',2,'mobile-token','497cd1b34738b914f1ce40377eedc78309a7fa8a91e2752091545ad4d147bd6f','[\"*\"]',NULL,NULL,'2026-07-17 16:16:23','2026-07-17 16:16:23'),(102,'App\\Models\\admin_models\\User',2,'mobile-token','c54a5a582db0ae7541e5103e716e9009520d2b8b2a7895f205407afa63f11c48','[\"*\"]',NULL,NULL,'2026-07-17 16:19:24','2026-07-17 16:19:24'),(103,'App\\Models\\admin_models\\User',2,'mobile-token','6d24d7980d3e70e6eecca7783b56b9dbe35a3138d02221145f8889370c6eb3a9','[\"*\"]',NULL,NULL,'2026-07-17 16:23:14','2026-07-17 16:23:14'),(104,'App\\Models\\admin_models\\User',2,'mobile-token','4bfec7ef3e0a53d13f75d1733de1c939e09c107729559e20949cc721a0593e5f','[\"*\"]',NULL,NULL,'2026-07-17 16:25:46','2026-07-17 16:25:46'),(105,'App\\Models\\admin_models\\User',2,'mobile-token','e7933ddff6398efb94c76345d6e4c3cbe6c42853e6ca536e8eff28d75d999a06','[\"*\"]',NULL,NULL,'2026-07-17 16:28:11','2026-07-17 16:28:11'),(106,'App\\Models\\admin_models\\User',2,'mobile-token','494509e3ddb8f90c35adb7a656c995b6014c66bf8de0b7559d154cd02c456a29','[\"*\"]',NULL,NULL,'2026-07-17 16:31:08','2026-07-17 16:31:08'),(107,'App\\Models\\admin_models\\User',2,'mobile-token','3ab9e8aac98db1cb00ffd559c96e93deac46cd901a027c8c57ba61b16e38ec30','[\"*\"]',NULL,NULL,'2026-07-17 16:45:23','2026-07-17 16:45:23'),(108,'App\\Models\\admin_models\\User',2,'mobile-token','ca47741934e5445e6b1ef3ef9c28747d0eae7aadcd79030164a9f604416e4b27','[\"*\"]',NULL,NULL,'2026-07-17 16:45:56','2026-07-17 16:45:56'),(109,'App\\Models\\admin_models\\User',2,'mobile-token','b835efe355a0c738a2b9ab0cc9cef2f6ed41f6161905ac42cc5c1d5ac75facc1','[\"*\"]',NULL,NULL,'2026-07-17 23:30:53','2026-07-17 23:30:53'),(110,'App\\Models\\admin_models\\User',2,'mobile-token','c7a980d5ab069e2d901f72b6259e6668926d2fa370c8b6cdb5686e1fe6122b2e','[\"*\"]',NULL,NULL,'2026-07-17 23:39:27','2026-07-17 23:39:27'),(111,'App\\Models\\admin_models\\User',2,'mobile-token','81e9dcf668ebbab93b977a5c10003da4877668afcb292e3fe904b27908510f2c','[\"*\"]',NULL,NULL,'2026-07-17 23:59:39','2026-07-17 23:59:39'),(112,'App\\Models\\admin_models\\User',2,'mobile-token','20f82bbae2934b6fbc8aa8ff071bfce32ff4796c2ccb00ade48e6217498a5184','[\"*\"]',NULL,NULL,'2026-07-18 00:02:13','2026-07-18 00:02:13'),(113,'App\\Models\\admin_models\\User',2,'mobile-token','c52ade41838129ce831a9755232152bfa441110128a877e599276bf8fd4563e1','[\"*\"]',NULL,NULL,'2026-07-18 00:10:46','2026-07-18 00:10:46'),(114,'App\\Models\\admin_models\\User',2,'mobile-token','739f9f0db848dc3d7484c5e1a8c4a5c0c42e0b5512538830482889f972bc05d5','[\"*\"]',NULL,NULL,'2026-07-18 00:15:10','2026-07-18 00:15:10'),(115,'App\\Models\\admin_models\\User',2,'mobile-token','2ca5dc7e04afdbd1e5934d40dbb6dfe9f0dda97c842e25ae17d20bfed3d832a1','[\"*\"]',NULL,NULL,'2026-07-18 00:17:35','2026-07-18 00:17:35'),(116,'App\\Models\\admin_models\\User',2,'mobile-token','4cd28595682920fe9b43c6588beacc9ccd11c46f6172b0584679ce52f8fece4c','[\"*\"]',NULL,NULL,'2026-07-18 00:29:01','2026-07-18 00:29:01'),(117,'App\\Models\\admin_models\\User',2,'mobile-token','bbce5403d2c25bd0f49f4460f8f0078a09b8d869148eed2a7752b70024dbce2b','[\"*\"]',NULL,NULL,'2026-07-18 00:41:22','2026-07-18 00:41:22'),(118,'App\\Models\\admin_models\\User',2,'mobile-token','bc3a8d84ab3b74bf1fe70c7b923431311bfdf807f08a5cb10927c317e17ac162','[\"*\"]',NULL,NULL,'2026-07-18 01:04:40','2026-07-18 01:04:40'),(119,'App\\Models\\admin_models\\User',2,'mobile-token','3bdbe0f81cacf23cf757b632e8750dab68e733cbfae657c3adada8ff5bef2356','[\"*\"]',NULL,NULL,'2026-07-18 02:53:00','2026-07-18 02:53:00'),(120,'App\\Models\\admin_models\\User',2,'mobile-token','cfd1a8efeceac8eb209c33dd756a8fa61b93b100501d5fa34a316d5046b72667','[\"*\"]',NULL,NULL,'2026-07-18 04:45:32','2026-07-18 04:45:32'),(121,'App\\Models\\admin_models\\User',2,'mobile-token','bc7d23684bcd373e337bddb9c6b2393f54159ec630694ff64553f073ce25bd67','[\"*\"]',NULL,NULL,'2026-07-18 05:59:41','2026-07-18 05:59:41'),(122,'App\\Models\\admin_models\\User',2,'mobile-token','9d420bd7c8f1ea667cf646e680a1c736392e662aa977fdca4f8ba51b6c4977cf','[\"*\"]',NULL,NULL,'2026-07-18 06:03:59','2026-07-18 06:03:59'),(123,'App\\Models\\admin_models\\User',2,'mobile-token','6be4ee16b82bb9610138887b889fc5230d5150339bf0b5e8cc5195988b5d6bf2','[\"*\"]',NULL,NULL,'2026-07-18 06:09:18','2026-07-18 06:09:18'),(124,'App\\Models\\admin_models\\User',2,'mobile-token','14c761bd2b36c18b4a789acbc07e7c09cef106cc0620b47bac230d2b011c7d72','[\"*\"]',NULL,NULL,'2026-07-18 06:10:38','2026-07-18 06:10:38'),(125,'App\\Models\\admin_models\\User',2,'mobile-token','921154eb444ff5ce35826cfaaa0d28a5db4792f500a9e9ce3984230a3de28071','[\"*\"]',NULL,NULL,'2026-07-18 06:13:13','2026-07-18 06:13:13'),(126,'App\\Models\\admin_models\\User',2,'mobile-token','10632dacd6e1adbe9957ca7bb1f8ffc6d361deb62f068333684a0d5323b47223','[\"*\"]',NULL,NULL,'2026-07-18 06:15:03','2026-07-18 06:15:03'),(127,'App\\Models\\admin_models\\User',2,'mobile-token','48d919f38666593b7b34abfe08740211326015e78908104a43d40d10990cc0b8','[\"*\"]','2026-07-18 06:33:17',NULL,'2026-07-18 06:25:27','2026-07-18 06:33:17'),(128,'App\\Models\\admin_models\\User',2,'mobile-token','f4e3fa223cadea23b4ff58f6175509fedbc728f94c0649018508e560ba359f9f','[\"*\"]',NULL,NULL,'2026-07-18 10:39:49','2026-07-18 10:39:49'),(129,'App\\Models\\admin_models\\User',2,'mobile-token','79ac7f086918c1e20ff85c5b2cb4ca1212d6534392fc09bc78edae8885ef4ad6','[\"*\"]',NULL,NULL,'2026-07-18 10:40:04','2026-07-18 10:40:04'),(130,'App\\Models\\admin_models\\User',2,'mobile-token','183588b12858bd1b682a5fdc4774b164bbe4b6200e7b377ec4745732f7c5c67e','[\"*\"]',NULL,NULL,'2026-07-18 10:55:37','2026-07-18 10:55:37'),(131,'App\\Models\\admin_models\\User',2,'mobile-token','3796a4c590fab1d675c6322a5e0a7f6dce1d82df6898596c2f707c2cbff9687d','[\"*\"]',NULL,NULL,'2026-07-18 11:08:50','2026-07-18 11:08:50'),(132,'App\\Models\\admin_models\\User',2,'mobile-token','833c8d7484d0677fb1505df741afa055aafc19cf3f114c48f7bc9cbbb3621f0a','[\"*\"]',NULL,NULL,'2026-07-18 11:14:32','2026-07-18 11:14:32'),(133,'App\\Models\\admin_models\\User',2,'mobile-token','8662fb69e76b07a65fb72490b93593330bf5f67b493f46b01b8bc0c872b84a5e','[\"*\"]',NULL,NULL,'2026-07-18 12:48:47','2026-07-18 12:48:47'),(134,'App\\Models\\admin_models\\User',2,'mobile-token','94e8a3c8954b0c66b101863eae120c92953459670da25da85ac04d3e4a06c6a3','[\"*\"]',NULL,NULL,'2026-07-18 13:03:03','2026-07-18 13:03:03'),(135,'App\\Models\\admin_models\\User',2,'mobile-token','6296795ad8c68e121e2619d1c78703cf34e98d7745d416670dd2344bf42d4713','[\"*\"]',NULL,NULL,'2026-07-18 13:05:37','2026-07-18 13:05:37'),(136,'App\\Models\\admin_models\\User',2,'mobile-token','be9aff20f9ddf2d845caf335aa0098a15084fbb9d6bd177d1022f091f89f020f','[\"*\"]',NULL,NULL,'2026-07-18 13:17:30','2026-07-18 13:17:30'),(137,'App\\Models\\admin_models\\User',2,'mobile-token','ee004400db9495d70b20f75e6e430e1cb3e4e43103667d53f00a1eb789ef24bf','[\"*\"]',NULL,NULL,'2026-07-18 13:30:25','2026-07-18 13:30:25'),(138,'App\\Models\\admin_models\\User',2,'mobile-token','1984f1276cb211eb248c6cbd141c05ff7d44d484f88163da8e73ec5ab14652c8','[\"*\"]',NULL,NULL,'2026-07-18 13:48:52','2026-07-18 13:48:52'),(139,'App\\Models\\admin_models\\User',2,'mobile-token','7adef9a14bfb7d416565e857480794aa3724244466dfb3222477bb4963621461','[\"*\"]',NULL,NULL,'2026-07-20 00:27:46','2026-07-20 00:27:46'),(140,'App\\Models\\admin_models\\User',2,'mobile-token','fd85326d76e6e96d65301ddf4c72b86e4aa2f8e60e55e597f5b0efecaa94d6c4','[\"*\"]','2026-07-20 02:10:19',NULL,'2026-07-20 01:28:53','2026-07-20 02:10:19'),(141,'App\\Models\\admin_models\\User',2,'mobile-token','d25e448459f94282685c9bc14010a75c99794f6500e12bd0ad029eec5469f831','[\"*\"]','2026-07-20 02:49:56',NULL,'2026-07-20 02:49:49','2026-07-20 02:49:56'),(142,'App\\Models\\admin_models\\User',2,'mobile-token','2c755da78d5d5ff7d692283014f8e896dfbe6d63a5f85fc2c4263ba0be8aa8c0','[\"*\"]','2026-07-20 09:26:41',NULL,'2026-07-20 09:25:55','2026-07-20 09:26:41'),(143,'App\\Models\\admin_models\\User',2,'mobile-token','4d80acc08c6a6a39bd73965571013b1c36f60c3ec69d375bfdbb0d774924f441','[\"*\"]','2026-07-20 09:43:22',NULL,'2026-07-20 09:29:33','2026-07-20 09:43:22'),(144,'App\\Models\\admin_models\\User',2,'mobile-token','3555670657c73c45fabfc447c0bef25ece8c83c61e44d8921635e9e8fdbf96a1','[\"*\"]','2026-07-20 09:46:43',NULL,'2026-07-20 09:46:35','2026-07-20 09:46:43'),(145,'App\\Models\\admin_models\\User',2,'mobile-token','fb541c89a7b868b0837283de9700899437c36b8595ba11fdb420bfecb957f502','[\"*\"]','2026-07-20 09:48:58',NULL,'2026-07-20 09:48:49','2026-07-20 09:48:58'),(146,'App\\Models\\admin_models\\User',4,'mobile-token','4bc09a1d08921b0b454cd18571d710a955d0513facc9d691d398097531ebf7a6','[\"*\"]','2026-07-20 09:58:49',NULL,'2026-07-20 09:50:55','2026-07-20 09:58:49'),(147,'App\\Models\\admin_models\\User',4,'mobile-token','382eefa55e108d8f64e1838ffba311733d90cd3a8c990f3600ead41d2e1214d2','[\"*\"]','2026-07-20 10:14:42',NULL,'2026-07-20 10:00:39','2026-07-20 10:14:42'),(148,'App\\Models\\admin_models\\User',4,'mobile-token','ae9ff471c92b5fdb25823a4e2a95420d7630c9e57e62880e5ac67ed7df60bba7','[\"*\"]','2026-07-20 10:18:56',NULL,'2026-07-20 10:15:33','2026-07-20 10:18:56'),(149,'App\\Models\\admin_models\\User',2,'mobile-token','4ff77bac79f4f95ea09ef4071973dbe7611d5abc4ef78bf90bf9016440a95dcf','[\"*\"]','2026-07-20 11:21:09',NULL,'2026-07-20 10:35:03','2026-07-20 11:21:09'),(150,'App\\Models\\admin_models\\User',2,'mobile-token','f49b1cfd3f6338655bf54126419c0b32925bb7123c69ec841303082f05503e0c','[\"*\"]',NULL,NULL,'2026-07-20 11:36:07','2026-07-20 11:36:07'),(151,'App\\Models\\admin_models\\User',2,'mobile-token','bd84321416a0e6ded4d189d8012bb9a6c84fa304b68260da26057c057028f467','[\"*\"]',NULL,NULL,'2026-07-20 11:38:13','2026-07-20 11:38:13'),(152,'App\\Models\\admin_models\\User',2,'mobile-token','4aea56d2e68439214bb6adf51a730fd774dd25b94c86c24e2ecd4dee12188180','[\"*\"]','2026-07-20 11:53:42',NULL,'2026-07-20 11:38:34','2026-07-20 11:53:42'),(153,'App\\Models\\admin_models\\User',2,'mobile-token','1b3052b571d5257ed6ffc216388370f4537d514e536e040cfa06e9bcd8ce8fe8','[\"*\"]',NULL,NULL,'2026-07-20 14:10:38','2026-07-20 14:10:38'),(154,'App\\Models\\admin_models\\User',2,'mobile-token','07c4245221a5db58a34e82a42932024c39673b824ebb33c23568293d26862ea3','[\"*\"]','2026-07-20 14:12:49',NULL,'2026-07-20 14:10:52','2026-07-20 14:12:49'),(155,'App\\Models\\admin_models\\User',2,'mobile-app','100a61560ccc58cf441c13fea7391935bdaaebb88507d1762f2bfd40daf0b69a','[\"*\"]','2026-07-20 21:08:31',NULL,'2026-07-20 21:06:06','2026-07-20 21:08:31'),(157,'App\\Models\\admin_models\\User',2,'mobile-app','e7ddae20be2e6da658911c2fe17e3b853e57e33dc4dccfb1634c7b041c32f9b9','[\"*\"]','2026-07-20 21:49:40',NULL,'2026-07-20 21:12:18','2026-07-20 21:49:40'),(159,'App\\Models\\admin_models\\User',2,'mobile-app','55b611d4c8fa794a983483319b8340fc4d42c55da079183503851d014aece58c','[\"*\"]','2026-07-21 04:10:06',NULL,'2026-07-20 22:28:07','2026-07-21 04:10:06'),(160,'App\\Models\\admin_models\\User',2,'mobile-token','f062489cf52dd02bcf5c9e87d51ab5d5e252e35aa4a21bbc0a03faacd9caeed7','[\"*\"]','2026-07-21 01:04:09',NULL,'2026-07-21 00:57:23','2026-07-21 01:04:09'),(162,'App\\Models\\admin_models\\User',2,'mobile-token','bc7d27a5114d26c04613e4ddb4a92c382166cc90b6693ff5ec2a23c0b56ca5ce','[\"*\"]','2026-07-21 01:19:53',NULL,'2026-07-21 01:16:32','2026-07-21 01:19:53'),(165,'App\\Models\\admin_models\\User',2,'mobile-app','b0bb0c76ffa92afbc22a00553c4b4d9e693f0ca4503cd602d45db7fb2406202d','[\"*\"]','2026-07-21 04:13:42',NULL,'2026-07-21 04:13:00','2026-07-21 04:13:42'),(167,'App\\Models\\admin_models\\User',2,'mobile-token','adf5f0e3e90b8eb311457093ec76155bb169fbf2a7308598399afd07318f24d1','[\"*\"]',NULL,NULL,'2026-07-21 04:53:58','2026-07-21 04:53:58'),(168,'App\\Models\\admin_models\\User',2,'mobile-token','7a00f275f93f904e85028531f1f0f433f0fc0441653834a47cb65b8ecd7e830c','[\"*\"]','2026-07-21 06:00:47',NULL,'2026-07-21 06:00:29','2026-07-21 06:00:47'),(171,'App\\Models\\admin_models\\User',2,'mobile-token','02e559f15b8fd78e70f0f3f3751e8d3abb86a63f5950e7bf32a3e8da850ea17c','[\"*\"]','2026-07-21 06:43:53',NULL,'2026-07-21 06:34:08','2026-07-21 06:43:53'),(172,'App\\Models\\admin_models\\User',4,'mobile-token','86a99902dab841e6a55e09dab455052f7af6c1023939fb4d049e8b6b0aa5fc8a','[\"*\"]','2026-07-21 06:49:04',NULL,'2026-07-21 06:35:51','2026-07-21 06:49:04'),(174,'App\\Models\\admin_models\\User',2,'mobile-app','71959ddb90930e62fc1933792ffd431b892e0cd7ce54f644258e0ff653312d7e','[\"*\"]',NULL,NULL,'2026-07-21 06:48:58','2026-07-21 06:48:58');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedule_visit`
--

DROP TABLE IF EXISTS `schedule_visit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_visit` (
  `visit_id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `notes` text,
  `scheduled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`visit_id`),
  KEY `fk_schedule_visit_patient` (`patient_id`),
  KEY `fk_schedule_visit_doctor` (`doctor_id`),
  CONSTRAINT `fk_schedule_visit_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  CONSTRAINT `fk_schedule_visit_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedule_visit`
--

LOCK TABLES `schedule_visit` WRITE;
/*!40000 ALTER TABLE `schedule_visit` DISABLE KEYS */;
INSERT INTO `schedule_visit` VALUES (1,1,1,'Pediatrics','2026-07-13','Routine checkup','2026-07-14 10:50:25'),(3,3,3,'Surgery','2026-07-14','Pre-op consultation','2026-07-14 10:50:25'),(4,4,4,'IM-Pulmonology','2026-07-14','Follow-up on cough','2026-07-14 10:50:25'),(5,5,5,'Ophthalmology / ENT','2026-07-15','Vision test','2026-07-14 10:50:25'),(6,6,6,'IM-Cardiology','2026-07-15','Blood pressure monitoring','2026-07-14 10:50:25'),(7,7,7,'General / Adult Medicine','2026-07-16','Annual physical','2026-07-14 10:50:25'),(8,1,8,'Pediatrics','2026-07-17','Vaccination','2026-07-14 10:50:25'),(9,NULL,9,'OB-Gyne','2026-07-18','doctor not yet assigned','2026-07-14 10:50:25'),(10,3,NULL,'Surgery','2026-07-19','Patient record pending — walk-in inquiry','2026-07-14 10:50:25'),(11,7,11,'OB-Gyne','2026-07-21','Tuesday, July 21, 2026 at 1PM - 1PM','2026-07-20 04:05:40'),(12,5,12,'Surgery','2026-07-27','Monday, July 27, 2026 at 9AM - 9AM','2026-07-20 04:12:51'),(13,11,13,'Pediatrics','2026-07-27','Monday, July 27, 2026 at 10AM - 3PM','2026-07-20 06:01:03'),(14,11,14,'Pediatrics','2026-07-22','okay','2026-07-21 01:11:22');
/*!40000 ALTER TABLE `schedule_visit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sentiment_results`
--

DROP TABLE IF EXISTS `sentiment_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sentiment_results` (
  `sentiment_id` int NOT NULL AUTO_INCREMENT,
  `feedback_id` int NOT NULL,
  `sentiment_label` enum('Positive','Neutral','Negative') NOT NULL,
  `confidence_score` decimal(4,3) NOT NULL,
  `analyzed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sentiment_id`),
  UNIQUE KEY `uq_feedback` (`feedback_id`),
  CONSTRAINT `fk_sentiment_feedback` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`feedback_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sentiment_results`
--

LOCK TABLES `sentiment_results` WRITE;
/*!40000 ALTER TABLE `sentiment_results` DISABLE KEYS */;
INSERT INTO `sentiment_results` VALUES (1,1,'Negative',0.580,'2026-07-18 04:36:26'),(2,2,'Positive',0.422,'2026-07-18 04:36:27'),(3,3,'Neutral',0.623,'2026-07-18 04:36:27'),(4,4,'Positive',0.514,'2026-07-18 04:36:27'),(5,5,'Neutral',0.566,'2026-07-18 04:36:27'),(6,6,'Neutral',0.402,'2026-07-18 04:36:27'),(7,7,'Positive',0.805,'2026-07-18 04:36:27'),(8,8,'Negative',0.744,'2026-07-18 04:36:27'),(9,9,'Neutral',0.459,'2026-07-18 04:36:27'),(10,10,'Neutral',0.402,'2026-07-18 04:36:27'),(11,11,'Neutral',0.908,'2026-07-18 04:36:27'),(12,12,'Positive',0.411,'2026-07-18 04:36:27'),(13,13,'Neutral',0.386,'2026-07-18 04:36:27'),(14,14,'Positive',0.733,'2026-07-18 04:36:27'),(15,15,'Positive',0.490,'2026-07-18 04:36:27'),(16,16,'Neutral',0.563,'2026-07-18 04:36:27'),(17,17,'Neutral',0.449,'2026-07-18 04:36:27'),(18,18,'Positive',0.672,'2026-07-18 04:36:27'),(19,19,'Neutral',0.743,'2026-07-18 04:36:27'),(20,20,'Neutral',0.437,'2026-07-18 04:36:27'),(21,21,'Positive',0.916,'2026-07-18 04:36:27'),(22,22,'Positive',0.444,'2026-07-18 04:36:27'),(23,23,'Neutral',0.943,'2026-07-18 04:36:27'),(24,24,'Neutral',0.502,'2026-07-18 04:36:27'),(25,25,'Positive',0.836,'2026-07-18 04:36:27'),(26,26,'Neutral',0.792,'2026-07-18 04:36:27'),(27,31,'Positive',0.466,'2026-07-20 20:17:21'),(28,27,'Negative',0.672,'2026-07-20 20:24:27'),(29,28,'Negative',0.369,'2026-07-20 20:24:27'),(30,29,'Positive',0.598,'2026-07-20 20:24:27'),(31,30,'Positive',0.460,'2026-07-20 20:24:27'),(32,32,'Neutral',0.396,'2026-07-21 01:14:50'),(33,33,'Neutral',0.447,'2026-07-21 01:19:55'),(34,36,'Negative',0.560,'2026-07-21 03:45:59');
/*!40000 ALTER TABLE `sentiment_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_conditions`
--

DROP TABLE IF EXISTS `service_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_conditions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_id` int NOT NULL,
  `condition_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_service_conditions_service` (`service_id`),
  CONSTRAINT `fk_service_conditions_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_conditions`
--

LOCK TABLES `service_conditions` WRITE;
/*!40000 ALTER TABLE `service_conditions` DISABLE KEYS */;
INSERT INTO `service_conditions` VALUES (1,1,'Well-child checkups'),(2,1,'Childhood illnesses'),(3,1,'Growth monitoring'),(4,1,'Nutrition guidance'),(5,2,'Prenatal care'),(6,2,'Family planning'),(7,2,'Menstrual disorders'),(8,2,'Menopause management'),(9,3,'Appendectomy'),(10,3,'Hernia repair'),(11,3,'Colorectal surgery'),(12,3,'Breast surgery'),(13,4,'Asthma'),(14,4,'COPD'),(15,4,'Pneumonia'),(16,4,'Sleep apnea'),(17,5,'Eye exams'),(18,5,'Cataract treatment'),(19,5,'Ear infections'),(20,5,'Sinusitis'),(21,6,'Heart disease'),(22,6,'Hypertension'),(23,6,'Heart failure'),(24,6,'Arrhythmias'),(25,7,'Annual physicals'),(26,7,'Chronic disease management'),(27,7,'Preventive care'),(28,7,'Acute illness');
/*!40000 ALTER TABLE `service_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `service_key` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `description` text,
  `room` varchar(50) DEFAULT NULL,
  `schedule` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'dermatology','Dermatology','fa-user-md','Dermatology focuses on skin, hair, and nail conditions.','Room 208','Monday, Wednesday: 10am - 12nn',1),(2,'pediatrics','Pediatrics','fa-child','Pediatrics is a branch of medicine...','Room 201','Monday - Saturday: 10am - 3pm',1),(3,'obgyne','OB-Gyne','fa-female','Obstetrics and Gynecology focuses on women\'s health...','Room 202','Monday - Saturday: 1pm',1),(4,'surgery','Surgery','fa-scalpel','General surgery focuses on abdominal organs...','Room 203','Monday: 9am, Wednesday: 11am-1pm',1),(5,'pulmonology','IM-Pulmonology','fa-lungs','Pulmonology deals with diseases involving the lungs...','Room 204','Tuesday: 10am - 12nn (by appointment)',1),(6,'ophthalmology','Ophthalmology / ENT','fa-eye','Ophthalmology focuses on eye health while ENT...','Room 206','Monday - Saturday: 10am - 12nn',1),(7,'cardiology','IM-Cardiology','fa-heartbeat','Cardiology deals with disorders of the heart...','Room 207','Wednesday: 10am - 12nn, Friday: 10am - 12nn',1),(8,'adultmedicine','General / Adult Medicine','fa-user-md','Adult medicine focuses on prevention, diagnosis...','Room 205','Monday, Friday, Saturday, Sunday: 1pm',1);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('j1cUWyxgYG1OdzsYLb5BCphtutTzgM9hgqEnldGk',2,'192.168.254.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiYlNTMmd0QnZSbzlpRTdXVktoYVJNd25vYURBZTVzRXp3R3hqMVFySiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1MDoiaHR0cDovLzE5Mi4xNjguMjU0LjE0Nzo4MDAwL2FkbWluX2FjYy9jaGF0Ym90X2xvZ3MiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo3MDoiaHR0cDovLzE5Mi4xNjguMjU0LjE0Nzo4MDAwL2FkbWluX2FjYy9hcHBvaW50bWVudHMvZGF5P2RhdGU9MjAyNi0wOC0xMyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9',1786637606);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` bigint unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthday` date DEFAULT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Lara Loyola','mikaelaloyola1020@gmail.com',NULL,'103654560530558036518','2026-06-27 14:51:25','$2y$12$MqLzeQHtOE4IYfD6tq14wuG8sBuY0NgUqpYD2QTO5BS18XAUoQBHW','Admin','2005-02-10','profile_photos/mvfVHuZenr9ycZll382bfsJUU4ZJynlkpoFAoAOG.png','active','2026-07-21 06:34:08','6cV4UTYKwEL5Eevu2kLKQ8RgHPqzQPNZs7Zfm6ZJxsQOQ7tAjNgrkLPJIr4p','2026-06-27 14:51:25','2026-07-20 21:15:28'),(4,'Maria Leonora','gmikaloyola@gmail.com',NULL,'100354961517890616098',NULL,'$2y$12$msOhOqcbiUsJqM6b80dMVO.qF55Ztk5Gndk.BL5oBjZYsrvNgnm7K','Staff',NULL,'profile_photos/I2bDTy7oQ1H3aZ9Ig4RcOZKTLMLtHFHbsPQXpHfP.jpg','active','2026-07-21 06:35:51','2KqOt2MASHc96krbh4Creu3xXsdcM8dPybWOhWi1vf93uEPEJWbAkQpUE30p','2026-06-30 04:32:42','2026-07-21 01:34:23'),(6,'Marites Cruz','marites.cruz@gmail.com',NULL,NULL,NULL,'$2y$12$Gx55jdTuWcqpOogaUWkKbOhkSIh.igCxc3wMHYxPZksgziGn/YFte','Staff',NULL,NULL,'inactive',NULL,NULL,'2026-07-17 14:48:42','2026-07-17 14:48:42'),(7,'Noemi Gepiga','gepiganoemi@gmail.com',NULL,NULL,NULL,'$2y$12$ge9f6HhhNnSRUJpVjUOvguQcpVLk6B./lybOpx36TeA01Twc6OJ6i','Staff',NULL,NULL,'active','2026-07-21 06:46:27',NULL,'2026-07-21 06:45:56','2026-07-21 06:45:56');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-23  0:07:16

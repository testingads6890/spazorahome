/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-11.8.9-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: u966147115_interiorfurnit
-- ------------------------------------------------------
-- Server version	11.8.9-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('billing','shipping') NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `address_line_1` varchar(255) NOT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `postal_code` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_user_id_type_index` (`user_id`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backorders`
--

DROP TABLE IF EXISTS `backorders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backorders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `quantity` bigint(20) unsigned NOT NULL,
  `expected_restock_date` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `fulfilled_date` varchar(255) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subtitle` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blogs`
--

DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulk_enquiries`
--

DROP TABLE IF EXISTS `bulk_enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_enquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `other_phone` varchar(20) DEFAULT NULL,
  `product_type` varchar(120) DEFAULT NULL,
  `category_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`category_ids`)),
  `subcategory_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`subcategory_ids`)),
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `admin_note` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bulk_enquiries_status_created_at_index` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulk_imports`
--

DROP TABLE IF EXISTS `bulk_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `total_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `processed_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `successful_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `failed_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `import_type` enum('products','inventory','prices','bulk_edit') NOT NULL DEFAULT 'products',
  `error_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`error_summary`)),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bulk_imports_user_id_foreign` (`user_id`),
  KEY `bulk_imports_vendor_id_status_index` (`vendor_id`,`status`),
  KEY `bulk_imports_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulk_inquiries`
--

DROP TABLE IF EXISTS `bulk_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bulk_inquiries_status_index` (`status`),
  KEY `bulk_inquiries_user_fk` (`user_id`),
  CONSTRAINT `bulk_inquiries_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulk_inquiry_category`
--

DROP TABLE IF EXISTS `bulk_inquiry_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_inquiry_category` (
  `bulk_inquiry_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`bulk_inquiry_id`,`category_id`),
  KEY `bic_category_fk` (`category_id`),
  CONSTRAINT `bic_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bic_inquiry_fk` FOREIGN KEY (`bulk_inquiry_id`) REFERENCES `bulk_inquiries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulk_inquiry_product`
--

DROP TABLE IF EXISTS `bulk_inquiry_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bulk_inquiry_product` (
  `bulk_inquiry_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`bulk_inquiry_id`,`product_id`),
  KEY `bip_product_fk` (`product_id`),
  CONSTRAINT `bip_inquiry_fk` FOREIGN KEY (`bulk_inquiry_id`) REFERENCES `bulk_inquiries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bip_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` bigint(20) unsigned NOT NULL,
  `value` longtext NOT NULL,
  `expiration` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` bigint(20) unsigned NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `call_logs`
--

DROP TABLE IF EXISTS `call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `provider` enum('twilio','vonage') NOT NULL DEFAULT 'twilio',
  `call_sid` varchar(255) NOT NULL,
  `status` enum('initiated','ringing','in_progress','completed','failed','no_answer','busy','cancelled') NOT NULL DEFAULT 'initiated',
  `direction` enum('outbound','inbound') NOT NULL DEFAULT 'outbound',
  `from_number` varchar(255) DEFAULT NULL,
  `to_number` varchar(255) DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT 0,
  `cost` decimal(10,4) DEFAULT 0.0000,
  `error_code` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `call_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`call_metadata`)),
  `recorded` tinyint(1) DEFAULT 0,
  `recording_url` varchar(255) DEFAULT NULL,
  `recording_duration` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_sid` (`call_sid`),
  KEY `user_id` (`user_id`),
  KEY `phone_number` (`phone_number`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `call_schedules`
--

DROP TABLE IF EXISTS `call_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `call_log_id` bigint(20) unsigned DEFAULT NULL,
  `phone_number` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `scheduled_call_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show','rescheduled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `assessment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assessment_data`)),
  `interested_products` varchar(255) DEFAULT NULL,
  `followup_action` varchar(255) DEFAULT NULL,
  `followup_date` timestamp NULL DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `priority` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `phone_number` (`phone_number`),
  KEY `status` (`status`),
  KEY `scheduled_at` (`scheduled_at`),
  KEY `scheduled_call_at` (`scheduled_call_at`),
  KEY `call_schedules_call_log_id_foreign` (`call_log_id`),
  KEY `call_schedules_vendor_id_foreign` (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `carriers`
--

DROP TABLE IF EXISTS `carriers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carriers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tracking_url` varchar(255) DEFAULT NULL,
  `tracking_number_pattern` varchar(255) DEFAULT NULL,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` bigint(20) unsigned NOT NULL,
  `unit_price` varchar(255) NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `status` enum('active','completed','abandoned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carts_user_id_status_index` (`user_id`,`status`),
  KEY `carts_session_id_index` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `product_type_id` bigint(20) unsigned DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  KEY `categories_status_created_at_index` (`status`,`created_at`),
  KEY `categories_product_type_id_foreign` (`product_type_id`),
  CONSTRAINT `categories_product_type_id_foreign` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category_product`
--

DROP TABLE IF EXISTS `category_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_product` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_product_category_id_product_id_unique` (`category_id`,`product_id`),
  KEY `category_product_product_id_index` (`product_id`),
  CONSTRAINT `category_product_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1024 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_conversations`
--

DROP TABLE IF EXISTS `chat_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `status` enum('open','closed','archived') NOT NULL DEFAULT 'open',
  `subject` varchar(255) NOT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_vendor` (`user_id`,`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_message` (`last_message_at`),
  KEY `vendor_id` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message` longtext NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`conversation_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `district_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cities_district_name_unique` (`district_id`,`name`),
  KEY `cities_district_active_idx` (`district_id`,`is_active`),
  CONSTRAINT `cities_district_fk` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `collection_products`
--

DROP TABLE IF EXISTS `collection_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `sort_order` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `collection_id` (`collection_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `collections`
--

DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collections_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `compares`
--

DROP TABLE IF EXISTS `compares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `consultation_number` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'video',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `iso2` char(2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_name_unique` (`name`),
  UNIQUE KEY `iso2` (`iso2`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credit_note_items`
--

DROP TABLE IF EXISTS `credit_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `taxable` decimal(12,2) NOT NULL,
  `tax` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_items_credit_note_id_foreign` (`credit_note_id`),
  KEY `credit_note_items_order_item_id_index` (`order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `is_full` tinyint(1) NOT NULL DEFAULT 1,
  `number` varchar(255) NOT NULL,
  `issued_at` varchar(255) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `shipping` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  `supplier_invoice_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_notes_order_id_index` (`order_id`),
  KEY `credit_notes_supplier_invoice_id_foreign` (`supplier_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) DEFAULT NULL,
  `symbol` varchar(10) DEFAULT NULL,
  `exchange_rate` decimal(10,4) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT 'before',
  `sort_order` bigint(20) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_profiles`
--

DROP TABLE IF EXISTS `customer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `other_phone` varchar(20) DEFAULT NULL,
  `date_of_birth` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `deleted_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deals`
--

DROP TABLE IF EXISTS `deals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NULL DEFAULT current_timestamp(),
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(255) DEFAULT NULL,
  `original_price` decimal(14,2) DEFAULT NULL,
  `deal_price` decimal(14,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `discount_category`
--

DROP TABLE IF EXISTS `discount_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_category` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discount_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `discount_product`
--

DROP TABLE IF EXISTS `discount_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `discount_product` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discount_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `discounts`
--

DROP TABLE IF EXISTS `discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `discounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discounts_code_unique` (`code`),
  KEY `discounts_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `discounts_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dispute_appeals`
--

DROP TABLE IF EXISTS `dispute_appeals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_appeals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dispute_id` bigint(20) unsigned NOT NULL,
  `appealed_by_id` bigint(20) unsigned NOT NULL,
  `appealed_by_type` varchar(255) NOT NULL DEFAULT '''customer''',
  `reason` longtext NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `decision` varchar(255) DEFAULT NULL,
  `admin_notes` longtext DEFAULT NULL,
  `decided_by_id` bigint(20) unsigned DEFAULT NULL,
  `decided_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dispute_evidence`
--

DROP TABLE IF EXISTS `dispute_evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_evidence` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dispute_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT '''customer''',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `description` longtext DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT '''other''',
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dispute_messages`
--

DROP TABLE IF EXISTS `dispute_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dispute_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispute_id` (`dispute_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disputes`
--

DROP TABLE IF EXISTS `disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disputes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `category` enum('product_quality','not_received','not_as_described','damaged_defective','wrong_item','payment_issue','shipping_issue','refund_not_received','unauthorized_transaction','other') NOT NULL DEFAULT 'other',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','under_review','awaiting_response','in_resolution','resolved','closed','appealed') NOT NULL DEFAULT 'open',
  `resolution_type` enum('refund','replacement','partial_refund','credit','closed_without_action') DEFAULT NULL,
  `amount_claimed` decimal(10,2) DEFAULT NULL,
  `amount_settled` decimal(10,2) DEFAULT NULL,
  `initiated_by` enum('customer','vendor','admin') NOT NULL DEFAULT 'customer',
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `resolution_date` timestamp NULL DEFAULT NULL,
  `resolution_notes` longtext DEFAULT NULL,
  `appeal_count` int(10) unsigned NOT NULL DEFAULT 0,
  `evidence_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disputes_customer_id_foreign` (`customer_id`),
  KEY `disputes_vendor_id_foreign` (`vendor_id`),
  KEY `disputes_order_id_customer_id_vendor_id_index` (`order_id`,`customer_id`,`vendor_id`),
  KEY `disputes_status_priority_index` (`status`,`priority`),
  KEY `disputes_assigned_admin_id_index` (`assigned_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `districts`
--

DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `districts_state_name_unique` (`state_id`,`name`),
  KEY `districts_state_active_idx` (`state_id`,`is_active`),
  CONSTRAINT `districts_state_fk` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `export_enquiries`
--

DROP TABLE IF EXISTS `export_enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `export_enquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `message` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `export_enquiries_product_id_foreign` (`product_id`),
  KEY `export_enquiries_user_id_foreign` (`user_id`),
  KEY `export_enquiries_status_created_at_index` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` longtext NOT NULL,
  `queue` longtext NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` varchar(255) NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `frequently_bought_together`
--

DROP TABLE IF EXISTS `frequently_bought_together`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `frequently_bought_together` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `related_product_id` bigint(20) unsigned NOT NULL,
  `priority` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `related_product_id` (`related_product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gift_cards`
--

DROP TABLE IF EXISTS `gift_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `purchaser_name` varchar(255) NOT NULL,
  `purchaser_email` varchar(255) NOT NULL,
  `message` longtext DEFAULT NULL,
  `initial_balance` varchar(255) NOT NULL,
  `current_balance` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''active''',
  `redeemed_at` varchar(255) DEFAULT NULL,
  `expires_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `import_logs`
--

DROP TABLE IF EXISTS `import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bulk_import_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''skipped''',
  `error_message` longtext DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  `action` varchar(255) NOT NULL DEFAULT '''skip''',
  `row_number` bigint(20) unsigned NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `installation_bookings`
--

DROP TABLE IF EXISTS `installation_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `installation_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `booking_number` varchar(255) NOT NULL,
  `service_type` varchar(255) NOT NULL DEFAULT '''assembly''',
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `preferred_date` varchar(255) NOT NULL,
  `preferred_time` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `address` longtext NOT NULL,
  `product_details` longtext DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `admin_notes` longtext DEFAULT NULL,
  `service_fee` varchar(255) DEFAULT NULL,
  `confirmed_at` varchar(255) DEFAULT NULL,
  `completed_at` varchar(255) DEFAULT NULL,
  `cancelled_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventories`
--

DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `reserved_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `low_stock_threshold` int(10) unsigned NOT NULL DEFAULT 5,
  `last_restock_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','discontinued') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventories_product_id_variant_id_vendor_id_unique` (`product_id`,`variant_id`,`vendor_id`),
  KEY `inventories_variant_id_foreign` (`variant_id`),
  KEY `inventories_vendor_id_status_index` (`vendor_id`,`status`),
  KEY `inventories_last_restock_date_index` (`last_restock_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_logs`
--

DROP TABLE IF EXISTS `inventory_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity_change` bigint(20) unsigned NOT NULL,
  `old_quantity` bigint(20) unsigned NOT NULL,
  `new_quantity` bigint(20) unsigned NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_sequences`
--

DROP TABLE IF EXISTS `invoice_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(255) DEFAULT NULL,
  `last_number` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series` varchar(12) NOT NULL DEFAULT 'INV',
  `financial_year` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_sequences_series_financial_year_unique` (`series`,`financial_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `total_jobs` bigint(20) unsigned NOT NULL,
  `pending_jobs` bigint(20) unsigned NOT NULL,
  `failed_jobs` bigint(20) unsigned NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` longtext DEFAULT NULL,
  `cancelled_at` bigint(20) unsigned DEFAULT NULL,
  `created_at` bigint(20) unsigned NOT NULL,
  `finished_at` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` bigint(20) unsigned NOT NULL,
  `reserved_at` bigint(20) unsigned DEFAULT NULL,
  `available_at` bigint(20) unsigned NOT NULL,
  `created_at` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `direction` varchar(255) NOT NULL DEFAULT 'ltr',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `languages_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_qualification_scores`
--

DROP TABLE IF EXISTS `lead_qualification_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_qualification_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `base_score` int(10) unsigned DEFAULT 0,
  `engagement_score` int(10) unsigned DEFAULT 0,
  `purchase_score` int(10) unsigned DEFAULT 0,
  `location_score` int(10) unsigned DEFAULT 0,
  `product_interest_score` int(10) unsigned DEFAULT 0,
  `total_score` int(10) unsigned DEFAULT 0,
  `score_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`score_breakdown`)),
  `view_count` int(10) unsigned DEFAULT 0,
  `add_to_cart_count` int(10) unsigned DEFAULT 0,
  `wishlist_count` int(10) unsigned DEFAULT 0,
  `purchase_count` int(10) unsigned DEFAULT 0,
  `total_spent` decimal(12,2) DEFAULT 0.00,
  `last_interaction_at` timestamp NULL DEFAULT NULL,
  `last_call_at` timestamp NULL DEFAULT NULL,
  `call_attempts` int(10) unsigned DEFAULT 0,
  `successful_calls` int(10) unsigned DEFAULT 0,
  `eligible_for_calling` varchar(255) DEFAULT 'yes',
  `preferred_call_time` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `total_score` (`total_score`),
  KEY `eligible_for_calling` (`eligible_for_calling`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `low_stock_alerts`
--

DROP TABLE IF EXISTS `low_stock_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `low_stock_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `current_quantity` bigint(20) unsigned NOT NULL,
  `threshold_quantity` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''active''',
  `resolved_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(255) DEFAULT NULL,
  `items` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_type`,`model_id`),
  KEY `model_type_model_id` (`model_type`,`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_type`,`model_id`),
  KEY `model_type_model_id` (`model_type`,`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subscribed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(255) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `vendor_subtotal` decimal(12,2) DEFAULT NULL,
  `vendor_commission` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hsn_code` varchar(8) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_variant_id_foreign` (`variant_id`),
  KEY `order_items_order_id_index` (`order_id`),
  KEY `order_items_vendor_id_index` (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `status` enum('pending','processing','ready_to_ship','shipped','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(12,2) NOT NULL,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'INR',
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `carrier` varchar(255) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `shipping_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `invoice_number` varchar(16) DEFAULT NULL,
  `invoice_date` timestamp NULL DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `shipping_method` varchar(255) DEFAULT NULL,
  `is_export` tinyint(1) NOT NULL DEFAULT 0,
  `charge_currency` varchar(3) DEFAULT NULL,
  `charge_total` decimal(14,2) DEFAULT NULL,
  `exchange_rate` decimal(14,6) DEFAULT NULL,
  `export_weight_kg` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  UNIQUE KEY `orders_invoice_number_unique` (`invoice_number`),
  KEY `orders_billing_address_id_foreign` (`billing_address_id`),
  KEY `orders_shipping_address_id_foreign` (`shipping_address_id`),
  KEY `orders_user_id_status_created_at_index` (`user_id`,`status`,`created_at`),
  KEY `orders_order_number_index` (`order_number`),
  KEY `orders_tracking_number_carrier_index` (`tracking_number`,`carrier`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` bigint(20) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'automatic',
  `description` text DEFAULT NULL,
  `credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`credentials`)),
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_method_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'USD',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_transactions_transaction_id_unique` (`transaction_id`),
  KEY `payment_transactions_payment_method_id_foreign` (`payment_method_id`),
  KEY `payment_transactions_order_id_foreign` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `method` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `refunded_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `refund_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(255) NOT NULL DEFAULT '''INR''',
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_details` longtext DEFAULT NULL,
  `paid_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  `charge_amount` decimal(14,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_shopper_bookings`
--

DROP TABLE IF EXISTS `personal_shopper_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_shopper_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `booking_number` varchar(255) NOT NULL,
  `session_type` varchar(255) NOT NULL DEFAULT '''in_store''',
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `preferred_date` varchar(255) NOT NULL,
  `preferred_time` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `interests` longtext DEFAULT NULL,
  `budget_min` varchar(255) DEFAULT NULL,
  `budget_max` varchar(255) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `shopper_notes` longtext DEFAULT NULL,
  `admin_notes` longtext DEFAULT NULL,
  `confirmed_at` varchar(255) DEFAULT NULL,
  `completed_at` varchar(255) DEFAULT NULL,
  `cancelled_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pickup_bookings`
--

DROP TABLE IF EXISTS `pickup_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pickup_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `booking_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `id_type` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `admin_notes` longtext DEFAULT NULL,
  `ready_at` varchar(255) DEFAULT NULL,
  `picked_up_at` varchar(255) DEFAULT NULL,
  `expires_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pincodes`
--

DROP TABLE IF EXISTS `pincodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pincodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` bigint(20) unsigned NOT NULL,
  `code` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pincodes_city_code_unique` (`city_id`,`code`),
  KEY `pincodes_city_active_idx` (`city_id`,`is_active`),
  CONSTRAINT `pincodes_city_fk` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_attributes`
--

DROP TABLE IF EXISTS `product_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('text','select','color') NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_attributes_slug_unique` (`slug`),
  KEY `product_attributes_type_is_required_index` (`type`,`is_required`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_category`
--

DROP TABLE IF EXISTS `product_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_category` (
  `product_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `product_category_category_fk` (`category_id`),
  CONSTRAINT `product_category_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_category_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_interactions`
--

DROP TABLE IF EXISTS `product_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_interactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `interaction_type` enum('view','cart_add','purchase','wishlist_add','review') NOT NULL,
  `weight` tinyint(4) NOT NULL DEFAULT 1,
  `time_spent_seconds` int(11) DEFAULT NULL,
  `amount_spent` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product_interaction` (`user_id`,`product_id`,`interaction_type`),
  KEY `product_interactions_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `product_interactions_product_id_interaction_type_index` (`product_id`,`interaction_type`),
  KEY `product_interactions_interaction_type_index` (`interaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_pincode`
--

DROP TABLE IF EXISTS `product_pincode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_pincode` (
  `product_id` bigint(20) unsigned NOT NULL,
  `pincode_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`pincode_id`),
  KEY `product_pincode_pincode_fk` (`pincode_id`),
  CONSTRAINT `product_pincode_pincode_fk` FOREIGN KEY (`pincode_id`) REFERENCES `pincodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_pincode_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_types`
--

DROP TABLE IF EXISTS `product_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_types_name_unique` (`name`),
  UNIQUE KEY `product_types_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_units`
--

DROP TABLE IF EXISTS `product_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(60) NOT NULL,
  `label` varchar(60) NOT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_units_key_unique` (`key`),
  KEY `product_units_created_by_foreign` (`created_by`),
  CONSTRAINT `product_units_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `product_variants_product_id_sku_index` (`product_id`,`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `short_description` text DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `finish` varchar(100) DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `style` varchar(100) DEFAULT NULL,
  `product_type` varchar(100) DEFAULT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `running_ft` decimal(10,2) DEFAULT NULL,
  `warranty` text DEFAULT NULL,
  `care_instructions` text DEFAULT NULL,
  `customization_info` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `shipping_info` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `video_url` varchar(255) DEFAULT NULL,
  `sku` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `min_order_qty` int(10) unsigned NOT NULL DEFAULT 1,
  `max_order_qty` int(10) unsigned DEFAULT NULL,
  `low_stock_threshold` int(10) unsigned NOT NULL DEFAULT 5,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sustainability_badges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sustainability_badges`)),
  `sustainability_certified` tinyint(1) NOT NULL DEFAULT 0,
  `is_new_arrival` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `purchase_count` int(11) NOT NULL DEFAULT 0,
  `vendor_status` enum('pending','approved','rejected','changes_requested') DEFAULT NULL,
  `user_role` enum('customer','interior','architecture','designer','other') NOT NULL DEFAULT 'customer',
  `vendor_rejection_reason` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `hsn_code` varchar(8) DEFAULT NULL,
  `product_id` varchar(100) DEFAULT NULL,
  `ean` varchar(50) DEFAULT NULL,
  `designer` varchar(255) DEFAULT NULL,
  `load_capacity` varchar(100) DEFAULT NULL,
  `adjustable_features` text DEFAULT NULL,
  `packaging_quantity` varchar(100) DEFAULT NULL,
  `packaging_weight` decimal(8,2) DEFAULT NULL,
  `packaging_info` text DEFAULT NULL,
  `warranty_months` int(10) unsigned DEFAULT NULL,
  `assembly_instructions` text DEFAULT NULL,
  `assembly_time` varchar(100) DEFAULT NULL,
  `assembly_difficulty` varchar(50) DEFAULT NULL,
  `return_policy` text DEFAULT NULL,
  `return_policy_days` int(10) unsigned DEFAULT NULL,
  `safety_warnings` text DEFAULT NULL,
  `country_of_origin` varchar(100) DEFAULT NULL,
  `importer_info` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `related_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_products`)),
  `loyalty_points_multiplier` decimal(3,1) DEFAULT 1.0,
  `export_available` tinyint(1) NOT NULL DEFAULT 0,
  `export_weight_kg` decimal(8,2) DEFAULT NULL,
  `export_min_qty` int(10) unsigned NOT NULL DEFAULT 1,
  `export_blocked_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`export_blocked_countries`)),
  `vendor_sku` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(150) DEFAULT NULL,
  `model_no` varchar(100) DEFAULT NULL,
  `collection_series` varchar(150) DEFAULT NULL,
  `material_grade` varchar(150) DEFAULT NULL,
  `secondary_material` varchar(150) DEFAULT NULL,
  `colour_code` varchar(50) DEFAULT NULL,
  `finish_type` varchar(100) DEFAULT NULL,
  `finish_code` varchar(50) DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `lead_time_days` int(10) unsigned DEFAULT NULL,
  `is_customizable` tinyint(1) DEFAULT 0,
  `made_to_order` tinyint(1) DEFAULT 0,
  `assembly_required` tinyint(1) DEFAULT 0,
  `service_type` varchar(20) DEFAULT NULL,
  `installation_included` tinyint(1) DEFAULT 0,
  `installation_charges` decimal(10,2) DEFAULT NULL,
  `warranty_exclusions` text DEFAULT NULL,
  `return_eligible` tinyint(1) DEFAULT 1,
  `replacement_policy` text DEFAULT NULL,
  `damage_policy` text DEFAULT NULL,
  `packed_length_cm` decimal(8,2) DEFAULT NULL,
  `packed_width_cm` decimal(8,2) DEFAULT NULL,
  `packed_height_cm` decimal(8,2) DEFAULT NULL,
  `dispatch_from` varchar(150) DEFAULT NULL,
  `shipping_restrictions` text DEFAULT NULL,
  `is_fragile` tinyint(1) DEFAULT 0,
  `is_bulky` tinyint(1) DEFAULT 0,
  `catalogue_url` varchar(500) DEFAULT NULL,
  `cad_url` varchar(500) DEFAULT NULL,
  `image_folder_url` varchar(500) DEFAULT NULL,
  `vendor_notes` text DEFAULT NULL,
  `whats_included` text DEFAULT NULL,
  `whats_not_included` text DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `price_valid_until` date DEFAULT NULL,
  `category_attributes` longtext DEFAULT NULL,
  `faq_1_question` varchar(255) DEFAULT NULL,
  `faq_1_answer` text DEFAULT NULL,
  `faq_2_question` varchar(255) DEFAULT NULL,
  `faq_2_answer` text DEFAULT NULL,
  `faq_3_question` varchar(255) DEFAULT NULL,
  `faq_3_answer` text DEFAULT NULL,
  `bulk_pricing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bulk_pricing`)),
  `secondary_material_grade` varchar(150) DEFAULT NULL,
  `dispatch_time_days` smallint(5) unsigned DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `district_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `pincode_id` bigint(20) unsigned DEFAULT NULL,
  `unit_details` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_category_id_is_active_is_featured_index` (`category_id`,`is_active`,`is_featured`),
  KEY `products_sku_index` (`sku`),
  KEY `products_is_active_created_at_index` (`is_active`,`created_at`),
  KEY `products_vendor_id_vendor_status_index` (`vendor_id`,`vendor_status`),
  KEY `products_brand_index` (`brand`),
  KEY `products_material_index` (`material`),
  KEY `products_color_index` (`color`),
  KEY `products_size_index` (`size`),
  KEY `products_style_index` (`style`),
  KEY `products_is_new_arrival_is_featured_index` (`is_new_arrival`,`is_featured`),
  KEY `products_country_fk` (`country_id`),
  KEY `products_state_fk` (`state_id`),
  KEY `products_district_fk` (`district_id`),
  KEY `products_city_fk` (`city_id`),
  KEY `products_pincode_fk` (`pincode_id`),
  FULLTEXT KEY `products_name_description_sku_fulltext` (`name`,`description`,`sku`),
  CONSTRAINT `products_city_fk` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_country_fk` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_district_fk` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_pincode_fk` FOREIGN KEY (`pincode_id`) REFERENCES `pincodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_state_fk` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=597 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `professional_profiles`
--

DROP TABLE IF EXISTS `professional_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `professional_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  `bio` longtext DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `company_name` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `experience_years` bigint(20) DEFAULT NULL,
  `portfolio_images` text DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recommendation_stats`
--

DROP TABLE IF EXISTS `recommendation_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recommendation_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recommendation_type` enum('personalized','collaborative','content_based','viewed_also_viewed','bought_also_bought','trending','new_arrivals','seasonal') NOT NULL,
  `date` date NOT NULL,
  `total_recommendations` int(11) NOT NULL DEFAULT 0,
  `unique_users` int(11) NOT NULL DEFAULT 0,
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ctr` double NOT NULL DEFAULT 0,
  `conversion_rate` double NOT NULL DEFAULT 0,
  `avg_order_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `avg_score` double NOT NULL DEFAULT 0,
  `new_products_recommended` int(11) NOT NULL DEFAULT 0,
  `seasonal_relevance` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recommendation_stats_recommendation_type_date_unique` (`recommendation_type`,`date`),
  KEY `recommendation_stats_date_index` (`date`),
  KEY `recommendation_stats_recommendation_type_index` (`recommendation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recommendations`
--

DROP TABLE IF EXISTS `recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recommendations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `recommendation_type` enum('personalized','collaborative','content_based','viewed_also_viewed','bought_also_bought','trending','new_arrivals','seasonal') NOT NULL,
  `score` double NOT NULL DEFAULT 0,
  `confidence` double NOT NULL DEFAULT 0.5,
  `reason` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `clicks` int(11) NOT NULL DEFAULT 0,
  `conversions` int(11) NOT NULL DEFAULT 0,
  `is_clicked` tinyint(1) NOT NULL DEFAULT 0,
  `clicked_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recommendations_user_id_product_id_recommendation_type_unique` (`user_id`,`product_id`,`recommendation_type`),
  KEY `recommendations_product_id_foreign` (`product_id`),
  KEY `recommendations_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `recommendations_user_id_recommendation_type_index` (`user_id`,`recommendation_type`),
  KEY `recommendations_recommendation_type_score_index` (`recommendation_type`,`score`),
  KEY `recommendations_recommendation_type_index` (`recommendation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recycle_bookings`
--

DROP TABLE IF EXISTS `recycle_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recycle_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `booking_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `address` longtext NOT NULL,
  `items` longtext DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `admin_notes` longtext DEFAULT NULL,
  `scheduled_at` varchar(255) DEFAULT NULL,
  `completed_at` varchar(255) DEFAULT NULL,
  `cancelled_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `payment_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `refund_amount` varchar(255) NOT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `processed_at` varchar(255) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `return_items`
--

DROP TABLE IF EXISTS `return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` bigint(20) unsigned NOT NULL,
  `return_amount` varchar(255) NOT NULL,
  `condition` varchar(255) NOT NULL DEFAULT '''used''',
  `notes` longtext DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `return_requests`
--

DROP TABLE IF EXISTS `return_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `return_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `reason` varchar(60) NOT NULL,
  `customer_note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `credit_notes` varchar(255) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_requests_order_id_index` (`order_id`),
  KEY `return_requests_user_id_index` (`user_id`),
  KEY `return_requests_resolved_by_index` (`resolved_by`),
  KEY `return_requests_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `return_requests_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_requests_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `return_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `return_reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('requested','approved','rejected','in_transit','received','completed') NOT NULL DEFAULT 'requested',
  `return_date` timestamp NULL DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `returns_user_id_foreign` (`user_id`),
  KEY `returns_vendor_id_foreign` (`vendor_id`),
  KEY `returns_order_id_user_id_vendor_id_index` (`order_id`,`user_id`,`vendor_id`),
  KEY `returns_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `rating` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `comment` longtext DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `vendor_response` text DEFAULT NULL,
  `vendor_replied_at` timestamp NULL DEFAULT NULL,
  `vendor_reply` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `room_ideas`
--

DROP TABLE IF EXISTS `room_ideas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_ideas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `slug` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `search_logs`
--

DROP TABLE IF EXISTS `search_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(255) DEFAULT NULL,
  `results_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `search_suggestions`
--

DROP TABLE IF EXISTS `search_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(255) DEFAULT NULL,
  `frequency` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT '''string''',
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `carrier` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `shipped_at` varchar(255) DEFAULT NULL,
  `delivered_at` varchar(255) DEFAULT NULL,
  `deleted_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  `return_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shipping_carriers`
--

DROP TABLE IF EXISTS `shipping_carriers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_carriers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `credentials` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipping_carriers_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shipping_methods`
--

DROP TABLE IF EXISTS `shipping_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type` varchar(255) NOT NULL DEFAULT 'flat_rate',
  `description` text DEFAULT NULL,
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_order` decimal(12,2) DEFAULT NULL,
  `delivery_estimate` varchar(100) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shipping_zones`
--

DROP TABLE IF EXISTS `shipping_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_zones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `countries` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `states` text DEFAULT NULL,
  `cities` text DEFAULT NULL,
  `rate` decimal(14,2) DEFAULT NULL,
  `free_shipping_threshold` decimal(14,2) DEFAULT NULL,
  `rate_per_kg` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sliders`
--

DROP TABLE IF EXISTS `sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sliders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `slides` text DEFAULT NULL,
  `sort_order` bigint(20) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `states_country_name_unique` (`country_id`,`name`),
  KEY `states_country_active_idx` (`country_id`,`is_active`),
  CONSTRAINT `states_country_fk` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `static_pages`
--

DROP TABLE IF EXISTS `static_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `static_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meta_title` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supplier_invoices`
--

DROP TABLE IF EXISTS `supplier_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `number` varchar(16) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_invoices_number_unique` (`number`),
  KEY `supplier_invoices_vendor_id_foreign` (`vendor_id`),
  KEY `supplier_invoices_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `ticket_number` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('order','product','shipping','payment','return','other') NOT NULL DEFAULT 'other',
  `status` enum('open','in_progress','resolved','closed','escalated') NOT NULL DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `resolution` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `internal_remarks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`internal_remarks`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_tickets_ticket_number_unique` (`ticket_number`),
  KEY `support_tickets_user_id_status_index` (`user_id`,`status`),
  KEY `support_tickets_assigned_to_index` (`assigned_to`),
  KEY `support_tickets_ticket_number_index` (`ticket_number`),
  KEY `support_tickets_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `support_tickets_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `swatches`
--

DROP TABLE IF EXISTS `swatches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `swatches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `color_code` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_attribute_id` bigint(20) unsigned DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT 'color',
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_call_records`
--

DROP TABLE IF EXISTS `ticket_call_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_call_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `call_duration` int(10) unsigned DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `support_executive_id` bigint(20) unsigned DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_mobile` varchar(255) DEFAULT NULL,
  `call_datetime` datetime DEFAULT NULL,
  `call_purpose` varchar(255) DEFAULT NULL,
  `call_status` varchar(255) DEFAULT 'completed',
  `resolution` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `recording_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type` varchar(255) DEFAULT 'chat',
  `attachments` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `language_id` bigint(20) unsigned NOT NULL,
  `group` varchar(255) DEFAULT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_type` enum('customer','designer','architect','admin') NOT NULL DEFAULT 'customer',
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `professional_description` text DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_guest` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_coupons`
--

DROP TABLE IF EXISTS `vendor_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `code` varchar(40) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'percent',
  `value` decimal(12,2) NOT NULL,
  `min_order` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(12,2) DEFAULT NULL,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `used_count` int(10) unsigned NOT NULL DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_coupons_code_unique` (`code`),
  KEY `vendor_coupons_vendor_id_index` (`vendor_id`),
  CONSTRAINT `vendor_coupons_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_documents`
--

DROP TABLE IF EXISTS `vendor_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `type` varchar(40) NOT NULL,
  `label` varchar(120) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `size` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_orders`
--

DROP TABLE IF EXISTS `vendor_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` varchar(255) NOT NULL,
  `commission` varchar(255) NOT NULL,
  `payout_amount` varchar(255) NOT NULL,
  `payout_status` varchar(255) NOT NULL DEFAULT '''pending''',
  `paid_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `tcs_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `delivered_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_payouts`
--

DROP TABLE IF EXISTS `vendor_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_payouts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `amount` varchar(255) NOT NULL,
  `method` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `details` longtext DEFAULT NULL,
  `paid_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_products`
--

DROP TABLE IF EXISTS `vendor_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '''pending''',
  `commission_rate` varchar(255) DEFAULT NULL,
  `rejection_reason` longtext DEFAULT NULL,
  `approved_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_return_requests`
--

DROP TABLE IF EXISTS `vendor_return_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_return_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `vendor_order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'requested',
  `vendor_response` text DEFAULT NULL,
  `responded_by` bigint(20) unsigned DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_return_requests_vendor_order_id_foreign` (`vendor_order_id`),
  KEY `vendor_return_requests_order_item_id_foreign` (`order_item_id`),
  KEY `vendor_return_requests_responded_by_foreign` (`responded_by`),
  KEY `vendor_return_requests_processed_by_foreign` (`processed_by`),
  KEY `vendor_return_requests_vendor_id_status_created_at_index` (`vendor_id`,`status`,`created_at`),
  KEY `vendor_return_requests_customer_id_created_at_index` (`customer_id`,`created_at`),
  CONSTRAINT `vendor_return_requests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_return_requests_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_return_requests_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_return_requests_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_return_requests_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_return_requests_vendor_order_id_foreign` FOREIGN KEY (`vendor_order_id`) REFERENCES `vendor_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_users`
--

DROP TABLE IF EXISTS `vendor_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT '''staff''',
  `permissions` longtext DEFAULT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_wallet_transactions`
--

DROP TABLE IF EXISTS `vendor_wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_wallet_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `kind` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ref_type` varchar(30) DEFAULT NULL,
  `ref_id` bigint(20) unsigned DEFAULT NULL,
  `available_at` timestamp NULL DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_wallet_transactions_vendor_id_index` (`vendor_id`),
  KEY `vendor_wallet_transactions_ref_type_ref_id_index` (`ref_type`,`ref_id`),
  KEY `vendor_wallet_transactions_vendor_id_kind_index` (`vendor_id`,`kind`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_withdrawal_requests`
--

DROP TABLE IF EXISTS `vendor_withdrawal_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_withdrawal_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `vendor_payout_id` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_withdrawal_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `vendor_withdrawal_requests_vendor_payout_id_foreign` (`vendor_payout_id`),
  KEY `vendor_withdrawal_requests_vendor_id_status_created_at_index` (`vendor_id`,`status`,`created_at`),
  CONSTRAINT `vendor_withdrawal_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_withdrawal_requests_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_withdrawal_requests_vendor_payout_id_foreign` FOREIGN KEY (`vendor_payout_id`) REFERENCES `vendor_payouts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_withdrawals`
--

DROP TABLE IF EXISTS `vendor_withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_withdrawals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `method` varchar(30) NOT NULL DEFAULT 'bank_transfer',
  `payout_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payout_details`)),
  `vendor_order_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vendor_order_ids`)),
  `note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_withdrawals_vendor_id_index` (`vendor_id`),
  KEY `vendor_withdrawals_processed_by_index` (`processed_by`),
  KEY `vendor_withdrawals_vendor_id_status_index` (`vendor_id`,`status`),
  CONSTRAINT `vendor_withdrawals_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_withdrawals_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `business_type` varchar(50) DEFAULT NULL,
  `seller_type` varchar(30) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `product_categories` longtext DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `other_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'India',
  `postal_code` varchar(255) DEFAULT NULL,
  `tax_number` varchar(255) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `gst_registered` tinyint(1) NOT NULL DEFAULT 0,
  `bank_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bank_details`)),
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `approved_at` timestamp NULL DEFAULT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `store_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`store_settings`)),
  `logo` varchar(255) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `whatsapp_conversations`
--

DROP TABLE IF EXISTS `whatsapp_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `whatsapp_id` varchar(255) NOT NULL,
  `status` enum('active','closed','archived','awaiting_response') NOT NULL DEFAULT 'active',
  `type` enum('sales','support','feedback','inquiry') NOT NULL DEFAULT 'sales',
  `started_at` timestamp NULL DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `message_count` int(10) unsigned DEFAULT 0,
  `last_message_preview` varchar(255) DEFAULT NULL,
  `context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context_data`)),
  `assigned_to` varchar(255) DEFAULT NULL,
  `requires_human_handoff` tinyint(1) DEFAULT 0,
  `handoff_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `whatsapp_messages`
--

DROP TABLE IF EXISTS `whatsapp_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `direction` enum('inbound','outbound') NOT NULL DEFAULT 'inbound',
  `type` enum('text','image','document','audio','video','template') NOT NULL DEFAULT 'text',
  `message_text` longtext DEFAULT NULL,
  `media_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_data`)),
  `whatsapp_message_id` varchar(255) NOT NULL,
  `status` enum('sent','delivered','read','failed') NOT NULL DEFAULT 'sent',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `error_code` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_ai_generated` tinyint(1) DEFAULT 0,
  `ai_model` varchar(255) DEFAULT NULL,
  `ai_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `whatsapp_suggestions`
--

DROP TABLE IF EXISTS `whatsapp_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `type` enum('product','order_tracking','support_option','appointment','discount') NOT NULL DEFAULT 'product',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `action_url` varchar(255) DEFAULT NULL,
  `status` enum('shown','clicked','ignored','expired') NOT NULL DEFAULT 'shown',
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `whatsapp_templates`
--

DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `template_id` varchar(255) NOT NULL,
  `category` enum('marketing','transactional','utility') NOT NULL DEFAULT 'utility',
  `template_text` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `buttons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`buttons`)),
  `status` enum('approved','pending','rejected','disabled') NOT NULL DEFAULT 'approved',
  `usage_count` int(10) unsigned DEFAULT 0,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-04  5:45:45
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-11.8.9-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: u966147115_interiorfurnit
-- ------------------------------------------------------
-- Server version	11.8.9-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Dumping data for table `categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `product_type_id`, `image`, `status`, `deleted_at`, `created_at`, `updated_at`, `meta_title`, `meta_description`) VALUES (1,'Living Room','living-room','Furniture and decor for your living space',NULL,1,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(2,'Bedroom','bedroom','Beds, wardrobes and bedroom storage',NULL,1,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(3,'Kitchen & Dining','kitchen-dining','Kitchen cabinets, tables and dining essentials',NULL,7,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(4,'Bathroom','bathroom','Storage, mirrors and bathroom accessories',NULL,1,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(5,'Office','office','Desks, chairs and office organization',NULL,1,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(6,'Kids & Baby','kids-baby','Furniture and storage for children',NULL,6,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(7,'Lighting','lighting','Lamps, bulbs and lighting solutions',NULL,3,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(8,'Textiles & Rugs','textiles-rugs','Curtains, pillows, rugs and bedding',NULL,4,NULL,1,NULL,'2026-09-19 05:39:11','2026-09-19 05:39:11',NULL,NULL),
(9,'Decoration','decoration','Picture frames, vases and home accessories',NULL,2,NULL,1,NULL,'2026-09-19 05:39:12','2026-09-19 05:39:12',NULL,NULL),
(10,'Outdoor','outdoor','Garden furniture and outdoor accessories',NULL,5,NULL,1,NULL,'2026-09-19 05:39:12','2026-09-19 05:39:12',NULL,NULL),
(11,'Dining','dining',NULL,NULL,7,NULL,1,NULL,'2026-09-27 07:27:03','2026-09-27 07:27:03',NULL,NULL),
(12,'Hallway','hallway',NULL,NULL,1,NULL,1,NULL,'2026-09-27 07:27:03','2026-09-27 07:27:03',NULL,NULL),
(13,'Vasee','vase',NULL,1,NULL,NULL,1,NULL,'2026-11-02 15:48:20','2026-11-02 15:48:20',NULL,NULL),
(14,'Sofas','living-room-sofas',NULL,1,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(15,'Coffee Tables','living-room-coffee-tables',NULL,1,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(16,'TV Units','living-room-tv-units',NULL,1,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(17,'Recliners','living-room-recliners',NULL,1,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(18,'Side Tables','living-room-side-tables',NULL,1,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(19,'Armchairs','living-room-armchairs',NULL,1,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(20,'Beds','bedroom-beds',NULL,2,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(21,'Wardrobes','bedroom-wardrobes',NULL,2,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(22,'Bedside Tables','bedroom-bedside-tables',NULL,2,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(23,'Dressers','bedroom-dressers',NULL,2,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(24,'Mattresses','bedroom-mattresses',NULL,2,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(25,'Bedsheets','bedroom-bedsheets',NULL,2,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(26,'Dining Tables','kitchen-dining-dining-tables',NULL,3,7,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(27,'Dining Chairs','kitchen-dining-dining-chairs',NULL,3,7,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(28,'Kitchen Islands','kitchen-dining-kitchen-islands',NULL,3,7,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(29,'Bar Stools','kitchen-dining-bar-stools',NULL,3,7,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(30,'Crockery Storage','kitchen-dining-crockery-storage',NULL,3,7,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(31,'Dining Sets','dining-dining-sets',NULL,11,7,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(32,'Benches','dining-benches',NULL,11,7,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(33,'Bathroom Shelves','bathroom-bathroom-shelves',NULL,4,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(34,'Mirrors','bathroom-mirrors',NULL,4,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(35,'Storage Cabinets','bathroom-storage-cabinets',NULL,4,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(36,'Study Desks','office-study-desks',NULL,5,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(37,'Office Chairs','office-office-chairs',NULL,5,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(38,'Bookshelves','office-bookshelves',NULL,5,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(39,'Filing Cabinets','office-filing-cabinets',NULL,5,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(40,'Kids Beds','kids-baby-kids-beds',NULL,6,6,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(41,'Study Chairs','kids-baby-study-chairs',NULL,6,6,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(42,'Toy Storage','kids-baby-toy-storage',NULL,6,6,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(43,'Cribs','kids-baby-cribs',NULL,6,6,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(44,'Pendant Lamps','lighting-pendant-lamps',NULL,7,3,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(45,'Table Lamps','lighting-table-lamps',NULL,7,3,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(46,'Floor Lamps','lighting-floor-lamps',NULL,7,3,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(47,'Wall Lights','lighting-wall-lights',NULL,7,3,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(48,'LED Strips','lighting-led-strips',NULL,7,3,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(49,'Rugs','textiles-rugs-rugs',NULL,8,4,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(50,'Curtains','textiles-rugs-curtains',NULL,8,4,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(51,'Cushions','textiles-rugs-cushions',NULL,8,4,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(52,'Bedsheets & Quilts','textiles-rugs-bedsheets-quilts',NULL,8,4,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(53,'Wall Art','decoration-wall-art',NULL,9,2,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(54,'Vases','decoration-vases',NULL,9,2,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(55,'Planters','decoration-planters',NULL,9,2,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(56,'Clocks','decoration-clocks',NULL,9,2,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(57,'Candles','decoration-candles',NULL,9,2,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(58,'Outdoor Chairs','outdoor-outdoor-chairs',NULL,10,5,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(59,'Outdoor Tables','outdoor-outdoor-tables',NULL,10,5,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(60,'Hammocks','outdoor-hammocks',NULL,10,5,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(61,'Planters','outdoor-planters',NULL,10,5,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(62,'Shoe Racks','hallway-shoe-racks',NULL,12,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(63,'Coat Stands','hallway-coat-stands',NULL,12,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL),
(64,'Console Tables','hallway-console-tables',NULL,12,1,NULL,1,NULL,'2026-11-04 05:17:19','2026-11-04 05:17:19',NULL,NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `product_types`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product_types` WRITE;
/*!40000 ALTER TABLE `product_types` DISABLE KEYS */;
INSERT INTO `product_types` (`id`, `name`, `slug`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Furniture','furniture',1,'2026-11-04 05:10:47','2026-11-04 05:10:47'),
(2,'Decor','decor',1,'2026-11-04 05:10:47','2026-11-04 05:10:47'),
(3,'Lighting','lighting',1,'2026-11-04 05:10:47','2026-11-04 05:10:47'),
(4,'Textiles','textiles',1,'2026-11-04 05:10:47','2026-11-04 05:10:47'),
(5,'Outdoor','outdoor',1,'2026-11-04 05:10:47','2026-11-04 05:10:47'),
(6,'Kids','kids',1,'2026-11-04 05:10:47','2026-11-04 05:10:47'),
(7,'Kitchen & Dining','kitchen-dining',1,'2026-11-04 05:10:47','2026-11-04 05:10:47');
/*!40000 ALTER TABLE `product_types` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `product_units`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product_units` WRITE;
/*!40000 ALTER TABLE `product_units` DISABLE KEYS */;
INSERT INTO `product_units` (`id`, `key`, `label`, `fields`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES (1,'running_ft','Running ft','[{\"name\":\"length\",\"label\":\"Length (ft)\",\"type\":\"number\"},{\"name\":\"height\",\"label\":\"Height (ft)\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(2,'rm','Running metre','[{\"name\":\"length\",\"label\":\"Length (m)\",\"type\":\"number\"},{\"name\":\"height\",\"label\":\"Height (m)\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(3,'sqft','Sq ft','[{\"name\":\"area\",\"label\":\"Area (sq ft)\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(4,'sqm','Sq m','[{\"name\":\"area\",\"label\":\"Area (sq m)\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(5,'roll','Roll','[{\"name\":\"length\",\"label\":\"Roll length (m)\",\"type\":\"number\"},{\"name\":\"width\",\"label\":\"Roll width (m)\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(6,'box','Box','[{\"name\":\"pieces\",\"label\":\"Pieces in one box\",\"type\":\"number\"},{\"name\":\"coverage\",\"label\":\"Coverage (e.g. 2 sq ft)\",\"type\":\"text\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(7,'pcs','Piece','[{\"name\":\"count\",\"label\":\"Number of pieces\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(8,'set','Set','[{\"name\":\"count\",\"label\":\"Pieces in one set\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28'),
(9,'pair','Pair','[{\"name\":\"count\",\"label\":\"Pieces in one pair\",\"type\":\"number\"}]',1,NULL,'2026-11-04 04:33:28','2026-11-04 04:33:28');
/*!40000 ALTER TABLE `product_units` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `countries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` (`id`, `name`, `iso2`, `is_active`, `created_at`, `updated_at`) VALUES (1,'India','IN',1,'2026-10-31 05:00:37','2026-10-31 05:00:37');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `states`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `states` WRITE;
/*!40000 ALTER TABLE `states` DISABLE KEYS */;
INSERT INTO `states` (`id`, `country_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES (1,1,'Andhra Pradesh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(2,1,'Arunachal Pradesh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(3,1,'Assam',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(4,1,'Bihar',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(5,1,'Chhattisgarh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(6,1,'Goa',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(7,1,'Gujarat',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(8,1,'Haryana',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(9,1,'Himachal Pradesh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(10,1,'Jharkhand',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(11,1,'Karnataka',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(12,1,'Kerala',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(13,1,'Madhya Pradesh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(14,1,'Maharashtra',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(15,1,'Manipur',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(16,1,'Meghalaya',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(17,1,'Mizoram',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(18,1,'Nagaland',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(19,1,'Odisha',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(20,1,'Punjab',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(21,1,'Rajasthan',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(22,1,'Sikkim',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(23,1,'Tamil Nadu',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(24,1,'Telangana',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(25,1,'Tripura',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(26,1,'Uttar Pradesh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(27,1,'Uttarakhand',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(28,1,'West Bengal',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(29,1,'Andaman and Nicobar Islands',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(30,1,'Chandigarh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(31,1,'Dadra and Nagar Haveli and Daman and Diu',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(32,1,'Delhi',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(33,1,'Jammu and Kashmir',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(34,1,'Ladakh',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(35,1,'Lakshadweep',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37'),
(36,1,'Puducherry',NULL,1,'2026-10-31 05:00:37','2026-10-31 05:00:37');
/*!40000 ALTER TABLE `states` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `districts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `districts` WRITE;
/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
INSERT INTO `districts` (`id`, `state_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (1,21,'Jaipur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(2,21,'Jodhpur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(3,21,'Udaipur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(4,21,'Kota',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(5,21,'Ajmer',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(6,14,'Mumbai',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(7,14,'Pune',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(8,14,'Nagpur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(9,14,'Nashik',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(10,14,'Thane',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(11,11,'Bengaluru Urban',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(12,11,'Mysuru',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(13,11,'Dakshina Kannada',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(14,11,'Dharwad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(15,11,'Belagavi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(16,23,'Chennai',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(17,23,'Coimbatore',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(18,23,'Madurai',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(19,23,'Tiruchirappalli',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(20,23,'Salem',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(21,7,'Ahmedabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(22,7,'Surat',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(23,7,'Vadodara',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(24,7,'Rajkot',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(25,7,'Bhavnagar',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(26,26,'Lucknow',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(27,26,'Kanpur Nagar',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(28,26,'Agra',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(29,26,'Varanasi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(30,26,'Ghaziabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(31,32,'New Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(32,32,'Central Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(33,32,'South Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(34,32,'West Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(35,32,'North Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(36,28,'Kolkata',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(37,28,'Howrah',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(38,28,'Darjeeling',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(39,28,'Siliguri',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(40,28,'Durgapur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(41,12,'Thiruvananthapuram',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(42,12,'Ernakulam',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(43,12,'Kozhikode',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(44,12,'Thrissur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(45,12,'Kannur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(46,24,'Hyderabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(47,24,'Warangal',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(48,24,'Karimnagar',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(49,24,'Nizamabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(50,24,'Khammam',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(51,29,'South Andaman',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(52,29,'North and Middle Andaman',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(53,29,'Nicobar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(54,1,'Visakhapatnam',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(55,1,'Vijayawada',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(56,1,'Guntur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(57,1,'Tirupati',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(58,1,'Kurnool',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(59,2,'Papum Pare',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(60,2,'Tawang',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(61,2,'East Siang',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(62,2,'Kra Daadi',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(63,2,'West Kameng',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(64,3,'Kamrup Metropolitan',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(65,3,'Dibrugarh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(66,3,'Jorhat',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(67,3,'Cachar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(68,3,'Sonitpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(69,4,'Patna',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(70,4,'Gaya',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(71,4,'Muzaffarpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(72,4,'Bhagalpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(73,4,'Darbhanga',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(74,30,'Chandigarh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(75,5,'Raipur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(76,5,'Bilaspur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(77,5,'Durg',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(78,5,'Korba',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(79,5,'Bastar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(80,31,'Silvassa',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(81,31,'Daman',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(82,31,'Diu',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(83,6,'North Goa',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(84,6,'South Goa',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(85,8,'Gurugram',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(86,8,'Faridabad',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(87,8,'Karnal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(88,8,'Panipat',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(89,8,'Hisar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(90,9,'Shimla',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(91,9,'Kangra',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(92,9,'Mandi',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(93,9,'Solan',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(94,9,'Kullu',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(95,33,'Srinagar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(96,33,'Jammu',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(97,33,'Anantnag',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(98,33,'Baramulla',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(99,33,'Kathua',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(100,10,'Ranchi',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(101,10,'East Singhbhum',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(102,10,'Dhanbad',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(103,10,'Bokaro',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(104,10,'Hazaribagh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(105,34,'Leh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(106,34,'Kargil',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(107,35,'Lakshadweep',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(108,13,'Bhopal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(109,13,'Indore',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(110,13,'Jabalpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(111,13,'Gwalior',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(112,13,'Ujjain',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(113,15,'Imphal West',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(114,15,'Imphal East',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(115,15,'Thoubal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(116,16,'East Khasi Hills',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(117,16,'West Garo Hills',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(118,16,'Jaintia Hills',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(119,17,'Aizawl',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(120,17,'Lunglei',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(121,17,'Champhai',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(122,18,'Kohima',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(123,18,'Dimapur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(124,18,'Mokokchung',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(125,19,'Khordha',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(126,19,'Cuttack',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(127,19,'Sundargarh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(128,19,'Ganjam',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(129,19,'Sambalpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(130,36,'Puducherry',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(131,36,'Karaikal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(132,36,'Mahe',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(133,36,'Yanam',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(134,20,'Ludhiana',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(135,20,'Amritsar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(136,20,'Jalandhar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(137,20,'Patiala',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(138,20,'Bathinda',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(139,22,'East Sikkim',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(140,22,'South Sikkim',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(141,22,'West Sikkim',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(142,25,'West Tripura',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(143,25,'South Tripura',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(144,25,'Gomati',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(145,27,'Dehradun',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(146,27,'Haridwar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(147,27,'Nainital',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(148,27,'Almora',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(149,27,'Udham Singh Nagar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23');
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `cities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
INSERT INTO `cities` (`id`, `district_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (1,1,'Jaipur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(2,2,'Jodhpur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(3,3,'Udaipur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(4,4,'Kota',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(5,5,'Ajmer',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(6,6,'Mumbai',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(7,7,'Pune',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(8,8,'Nagpur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(9,9,'Nashik',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(10,10,'Thane',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(11,11,'Bengaluru Urban',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(12,12,'Mysuru',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(13,13,'Dakshina Kannada',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(14,14,'Dharwad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(15,15,'Belagavi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(16,16,'Chennai',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(17,17,'Coimbatore',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(18,18,'Madurai',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(19,19,'Tiruchirappalli',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(20,20,'Salem',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(21,21,'Ahmedabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(22,22,'Surat',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(23,23,'Vadodara',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(24,24,'Rajkot',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(25,25,'Bhavnagar',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(26,26,'Lucknow',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(27,27,'Kanpur Nagar',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(28,28,'Agra',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(29,29,'Varanasi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(30,30,'Ghaziabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(31,31,'New Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(32,32,'Central Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(33,33,'South Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(34,34,'West Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(35,35,'North Delhi',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(36,36,'Kolkata',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(37,37,'Howrah',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(38,38,'Darjeeling',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(39,39,'Siliguri',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(40,40,'Durgapur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(41,41,'Thiruvananthapuram',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(42,42,'Ernakulam',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(43,43,'Kozhikode',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(44,44,'Thrissur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(45,45,'Kannur',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(46,46,'Hyderabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(47,47,'Warangal',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(48,48,'Karimnagar',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(49,49,'Nizamabad',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(50,50,'Khammam',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(51,51,'South Andaman',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(52,52,'North and Middle Andaman',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(53,53,'Nicobar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(54,54,'Visakhapatnam',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(55,55,'Vijayawada',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(56,56,'Guntur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(57,57,'Tirupati',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(58,58,'Kurnool',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(59,59,'Papum Pare',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(60,60,'Tawang',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(61,61,'East Siang',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(62,62,'Kra Daadi',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(63,63,'West Kameng',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(64,64,'Kamrup Metropolitan',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(65,65,'Dibrugarh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(66,66,'Jorhat',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(67,67,'Cachar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(68,68,'Sonitpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(69,69,'Patna',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(70,70,'Gaya',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(71,71,'Muzaffarpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(72,72,'Bhagalpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(73,73,'Darbhanga',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(74,74,'Chandigarh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(75,75,'Raipur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(76,76,'Bilaspur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(77,77,'Durg',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(78,78,'Korba',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(79,79,'Bastar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(80,80,'Silvassa',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(81,81,'Daman',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(82,82,'Diu',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(83,83,'North Goa',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(84,84,'South Goa',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(85,85,'Gurugram',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(86,86,'Faridabad',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(87,87,'Karnal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(88,88,'Panipat',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(89,89,'Hisar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(90,90,'Shimla',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(91,91,'Kangra',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(92,92,'Mandi',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(93,93,'Solan',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(94,94,'Kullu',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(95,95,'Srinagar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(96,96,'Jammu',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(97,97,'Anantnag',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(98,98,'Baramulla',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(99,99,'Kathua',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(100,100,'Ranchi',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(101,101,'East Singhbhum',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(102,102,'Dhanbad',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(103,103,'Bokaro',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(104,104,'Hazaribagh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(105,105,'Leh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(106,106,'Kargil',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(107,107,'Lakshadweep',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(108,108,'Bhopal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(109,109,'Indore',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(110,110,'Jabalpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(111,111,'Gwalior',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(112,112,'Ujjain',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(113,113,'Imphal West',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(114,114,'Imphal East',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(115,115,'Thoubal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(116,116,'East Khasi Hills',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(117,117,'West Garo Hills',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(118,118,'Jaintia Hills',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(119,119,'Aizawl',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(120,120,'Lunglei',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(121,121,'Champhai',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(122,122,'Kohima',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(123,123,'Dimapur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(124,124,'Mokokchung',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(125,125,'Khordha',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(126,126,'Cuttack',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(127,127,'Sundargarh',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(128,128,'Ganjam',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(129,129,'Sambalpur',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(130,130,'Puducherry',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(131,131,'Karaikal',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(132,132,'Mahe',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(133,133,'Yanam',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(134,134,'Ludhiana',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(135,135,'Amritsar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(136,136,'Jalandhar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(137,137,'Patiala',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(138,138,'Bathinda',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(139,139,'East Sikkim',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(140,140,'South Sikkim',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(141,141,'West Sikkim',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(142,142,'West Tripura',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(143,143,'South Tripura',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(144,144,'Gomati',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(145,145,'Dehradun',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(146,146,'Haridwar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(147,147,'Nainital',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(148,148,'Almora',1,'2026-11-02 05:29:23','2026-11-02 05:29:23'),
(149,149,'Udham Singh Nagar',1,'2026-11-02 05:29:23','2026-11-02 05:29:23');
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `pincodes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pincodes` WRITE;
/*!40000 ALTER TABLE `pincodes` DISABLE KEYS */;
INSERT INTO `pincodes` (`id`, `city_id`, `code`, `is_active`, `created_at`, `updated_at`) VALUES (1,1,'302001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(2,2,'342001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(3,3,'313001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(4,4,'324001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(5,5,'305001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(6,6,'400001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(7,7,'411001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(8,8,'440001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(9,9,'422001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(10,10,'400601',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(11,11,'560001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(12,12,'570001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(13,13,'575001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(14,14,'580001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(15,15,'590001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(16,16,'600001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(17,17,'641001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(18,18,'625001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(19,19,'620001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(20,20,'636001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(21,21,'380001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(22,22,'395003',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(23,23,'390001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(24,24,'360001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(25,25,'364001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(26,26,'226001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(27,27,'208001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(28,28,'282001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(29,29,'221001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(30,30,'201001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(31,31,'110001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(32,32,'110002',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(33,33,'110017',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(34,34,'110027',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(35,35,'110007',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(36,36,'700001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(37,37,'711101',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(38,38,'734101',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(39,39,'734001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(40,40,'713201',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(41,41,'695001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(42,42,'682011',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(43,43,'673001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(44,44,'680001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(45,45,'670001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(46,46,'500001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(47,47,'506002',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(48,48,'505001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(49,49,'503001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49'),
(50,50,'507001',1,'2026-11-02 04:20:49','2026-11-02 04:20:49');
/*!40000 ALTER TABLE `pincodes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `currencies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` (`id`, `code`, `symbol`, `exchange_rate`, `is_active`, `created_at`, `updated_at`, `name`, `position`, `sort_order`) VALUES (1,'INR','₹',93.0000,1,'2026-10-30 12:57:02','2026-11-02 06:05:32','Indian Rupee','before',0),
(2,'USD','$',0.0120,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','US Dollar','before',1),
(3,'EUR','€',0.0110,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Euro','before',2),
(4,'GBP','£',0.0094,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','British Pound','before',3),
(5,'AED','AED',0.0440,1,'2026-11-02 04:20:52','2026-11-02 06:05:32','UAE Dirham','before',4);
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `languages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` (`id`, `name`, `code`, `direction`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'English','en','ltr',1,0,'2026-09-20 12:16:52','2026-09-20 12:16:52'),
(2,'हिन्दी','hi','ltr',1,1,'2026-09-20 12:16:52','2026-09-20 12:16:52'),
(3,'Tamil','ta','ltr',0,10,'2026-11-02 04:20:52','2026-11-02 04:20:52'),
(4,'Marathi','mr','ltr',0,11,'2026-11-02 04:20:52','2026-11-02 04:20:52'),
(5,'Gujarati','gu','ltr',0,12,'2026-11-02 04:20:52','2026-11-02 04:20:52');
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `carriers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `carriers` WRITE;
/*!40000 ALTER TABLE `carriers` DISABLE KEYS */;
INSERT INTO `carriers` (`id`, `name`, `code`, `is_active`, `created_at`, `updated_at`, `tracking_url`, `tracking_number_pattern`, `api_endpoint`, `api_key`) VALUES (1,'Delhivery','delhivery',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','https://www.delhivery.com/track/package/{tracking}','^[A-Z0-9]{8,20}$',NULL,NULL),
(2,'Blue Dart','bluedart',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','https://www.bluedart.com/tracking?awb={tracking}','^[A-Z0-9]{8,20}$',NULL,NULL),
(3,'DTDC','dtdc',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','https://www.dtdc.in/tracking.asp?cnno={tracking}','^[A-Z0-9]{8,20}$',NULL,NULL),
(4,'Ekart','ekart',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','https://ekartlogistics.com/track/{tracking}','^[A-Z0-9]{8,20}$',NULL,NULL),
(5,'India Post','indiapost',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','https://www.indiapost.gov.in/?tracking={tracking}','^[A-Z0-9]{8,20}$',NULL,NULL);
/*!40000 ALTER TABLE `carriers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `shipping_zones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `shipping_zones` WRITE;
/*!40000 ALTER TABLE `shipping_zones` DISABLE KEYS */;
INSERT INTO `shipping_zones` (`id`, `name`, `countries`, `is_active`, `created_at`, `updated_at`, `states`, `cities`, `rate`, `free_shipping_threshold`, `rate_per_kg`) VALUES (1,'North India','[\"India\"]',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','[\"Delhi\",\"Haryana\",\"Punjab\",\"Uttar Pradesh\",\"Rajasthan\"]',NULL,499.00,25000.00,20.00),
(2,'West India','[\"India\"]',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','[\"Maharashtra\",\"Gujarat\",\"Goa\"]',NULL,399.00,25000.00,20.00),
(3,'South India','[\"India\"]',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','[\"Karnataka\",\"Tamil Nadu\",\"Kerala\",\"Telangana\",\"Andhra Pradesh\"]',NULL,449.00,25000.00,20.00),
(4,'East India','[\"India\"]',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','[\"West Bengal\",\"Odisha\",\"Bihar\",\"Jharkhand\"]',NULL,549.00,25000.00,20.00),
(5,'North East','[\"India\"]',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','[\"Assam\",\"Meghalaya\",\"Tripura\",\"Manipur\",\"Nagaland\"]',NULL,799.00,25000.00,20.00);
/*!40000 ALTER TABLE `shipping_zones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `shipping_methods`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `shipping_methods` WRITE;
/*!40000 ALTER TABLE `shipping_methods` DISABLE KEYS */;
INSERT INTO `shipping_methods` (`id`, `name`, `cost`, `is_active`, `created_at`, `updated_at`, `type`, `description`, `rate`, `min_order`, `delivery_estimate`, `sort_order`) VALUES (1,'Standard delivery',499.00,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','flat_rate','7-10 days',499.00,0.00,'7-10 days',0),
(2,'Express delivery',999.00,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','flat_rate','3-5 days',999.00,0.00,'3-5 days',1),
(3,'Free delivery',0.00,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','free_shipping','7-12 days',0.00,25000.00,'7-12 days',2),
(4,'Store pickup',0.00,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','local_pickup','Ready in 2 days',0.00,0.00,'Ready in 2 days',3),
(5,'White-glove installation',1999.00,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','flat_rate','5-7 days',1999.00,0.00,'5-7 days',4);
/*!40000 ALTER TABLE `shipping_methods` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `payment_methods`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` (`id`, `name`, `slug`, `type`, `description`, `credentials`, `config`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'Razorpay','razorpay','online','Razorpay',NULL,NULL,1,0,'2026-11-02 04:20:52','2026-11-02 04:20:52'),
(2,'Cash on delivery','cod','offline','Cash on delivery',NULL,NULL,1,1,'2026-11-02 04:20:52','2026-11-02 04:20:52'),
(3,'UPI','upi','online','UPI',NULL,NULL,0,2,'2026-11-02 04:20:52','2026-11-02 04:20:52'),
(4,'Bank transfer','bank-transfer','offline','Bank transfer',NULL,NULL,0,3,'2026-11-02 04:20:52','2026-11-02 04:20:52'),
(5,'EMI','emi','online','EMI',NULL,NULL,0,4,'2026-11-02 04:20:52','2026-11-02 04:20:52');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `banners`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` (`id`, `title`, `image_url`, `link_url`, `sort_order`, `is_active`, `created_at`, `updated_at`, `subtitle`, `image`, `link`, `position`) VALUES (1,'Festive Sale',NULL,NULL,0,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Up to 40% off sofas','https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&h=600&q=70','/search?category=1','homepage'),
(2,'New Bedroom Range',NULL,NULL,1,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Solid wood beds','https://images.unsplash.com/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1200&h=600&q=70','/search?category=2','homepage'),
(3,'Dining Sets',NULL,NULL,2,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Seat the whole family','https://images.unsplash.com/photo-1604578762246-41134e37f9cc?auto=format&fit=crop&w=1200&h=600&q=70','/search?category=11','homepage'),
(4,'Light It Up',NULL,NULL,3,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Lamps and pendants','https://images.unsplash.com/photo-1540932239986-30128078f3c5?auto=format&fit=crop&w=1200&h=600&q=70','/search?category=7','category'),
(5,'Outdoor Living',NULL,NULL,4,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Balcony and garden','https://images.unsplash.com/photo-1532323544230-7191fd51bc1b?auto=format&fit=crop&w=1200&h=600&q=70','/search?category=10','category');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `blogs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
INSERT INTO `blogs` (`id`, `title`, `slug`, `content`, `is_published`, `created_at`, `updated_at`, `excerpt`, `featured_image`, `author_name`, `published_at`, `meta_title`, `meta_description`) VALUES (1,'How to choose the right sofa','how-to-choose-the-right-sofa','<p>How to choose the right sofa. Practical advice from our design team.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Our guide: How to choose the right sofa.','https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&h=675&q=70','Design Team','2026-10-23 04:20:52',NULL,NULL),
(2,'Five small-bedroom storage ideas','five-small-bedroom-storage-ideas','<p>Five small-bedroom storage ideas. Practical advice from our design team.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Our guide: Five small-bedroom storage ideas.','https://images.unsplash.com/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1200&h=675&q=70','Design Team','2026-10-13 04:20:52',NULL,NULL),
(3,'Caring for solid wood furniture','caring-for-solid-wood-furniture','<p>Caring for solid wood furniture. Practical advice from our design team.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Our guide: Caring for solid wood furniture.','https://images.unsplash.com/photo-1572048572872-2394404cf1f3?auto=format&fit=crop&w=1200&h=675&q=70','Design Team','2026-10-03 04:20:52',NULL,NULL),
(4,'Setting up a home office that works','setting-up-a-home-office-that-works','<p>Setting up a home office that works. Practical advice from our design team.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Our guide: Setting up a home office that works.','https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=1200&h=675&q=70','Design Team','2026-09-23 04:20:52',NULL,NULL),
(5,'Lighting tips for Indian homes','lighting-tips-for-indian-homes','<p>Lighting tips for Indian homes. Practical advice from our design team.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Our guide: Lighting tips for Indian homes.','https://images.unsplash.com/photo-1540932239986-30128078f3c5?auto=format&fit=crop&w=1200&h=675&q=70','Design Team','2026-09-13 04:20:52',NULL,NULL);
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `pages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `is_active`, `created_at`, `updated_at`, `meta_title`, `meta_description`) VALUES (1,'About Us','about-us','<h2>About Us</h2><p>This page describes our About Us. Edit it in Admin.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','About Us',NULL),
(2,'Contact Us','contact-us','<h2>Contact Us</h2><p>This page describes our Contact Us. Edit it in Admin.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Contact Us',NULL),
(3,'Shipping Policy','shipping-policy','<h2>Shipping Policy</h2><p>This page describes our Shipping Policy. Edit it in Admin.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Shipping Policy',NULL),
(4,'Returns and Refunds','returns-and-refunds','<h2>Returns and Refunds</h2><p>This page describes our Returns and Refunds. Edit it in Admin.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Returns and Refunds',NULL),
(5,'Privacy Policy','privacy-policy','<h2>Privacy Policy</h2><p>This page describes our Privacy Policy. Edit it in Admin.</p>',1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Privacy Policy',NULL);
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `static_pages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `static_pages` WRITE;
/*!40000 ALTER TABLE `static_pages` DISABLE KEYS */;
INSERT INTO `static_pages` (`id`, `title`, `slug`, `content`, `meta_description`, `is_active`, `created_at`, `updated_at`, `meta_title`, `deleted_at`) VALUES (1,'Terms and Conditions','terms-and-conditions','<p>Terms and Conditions details. Edit in Admin.</p>',NULL,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Terms and Conditions',NULL),
(2,'FAQ','faq','<p>FAQ details. Edit in Admin.</p>',NULL,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','FAQ',NULL),
(3,'Careers','careers','<p>Careers details. Edit in Admin.</p>',NULL,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Careers',NULL),
(4,'Bulk Orders','bulk-orders','<p>Bulk Orders details. Edit in Admin.</p>',NULL,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Bulk Orders',NULL),
(5,'Care Guide','care-guide','<p>Care Guide details. Edit in Admin.</p>',NULL,1,'2026-11-02 04:20:52','2026-11-02 04:20:52','Care Guide',NULL);
/*!40000 ALTER TABLE `static_pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `collections`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `collections` WRITE;
/*!40000 ALTER TABLE `collections` DISABLE KEYS */;
INSERT INTO `collections` (`id`, `name`, `slug`, `description`, `image`, `is_active`, `is_featured`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'Festive Living','festive-living','Hand-picked Living Room pieces for Indian homes.','https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=900&h=600&q=70',1,1,'2026-11-02 04:20:49','2026-11-02 04:20:49',NULL),
(2,'Compact Homes','compact-homes','Hand-picked Bedroom pieces for Indian homes.','https://images.unsplash.com/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=900&h=600&q=70',1,1,'2026-11-02 04:20:49','2026-11-02 04:20:49',NULL),
(3,'Work From Home','work-from-home','Hand-picked Office pieces for Indian homes.','https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=900&h=600&q=70',1,1,'2026-11-02 04:20:49','2026-11-02 04:20:49',NULL),
(4,'Outdoor Lounge','outdoor-lounge','Hand-picked Outdoor pieces for Indian homes.','https://images.unsplash.com/photo-1532323544230-7191fd51bc1b?auto=format&fit=crop&w=900&h=600&q=70',1,1,'2026-11-02 04:20:49','2026-11-02 04:20:49',NULL),
(5,'Dining Essentials','dining-essentials','Hand-picked Dining pieces for Indian homes.','https://images.unsplash.com/photo-1604578762246-41134e37f9cc?auto=format&fit=crop&w=900&h=600&q=70',1,1,'2026-11-02 04:20:49','2026-11-02 04:20:49',NULL);
/*!40000 ALTER TABLE `collections` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `room_ideas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `room_ideas` WRITE;
/*!40000 ALTER TABLE `room_ideas` DISABLE KEYS */;
INSERT INTO `room_ideas` (`id`, `title`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`, `slug`, `image`, `room_type`, `deleted_at`) VALUES (1,'Warm Indian living room','Ideas and pieces for a Warm Indian living room.',NULL,1,'2026-11-02 04:20:49','2026-11-02 04:20:49','warm-indian-living-room','https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&h=800&q=70','living_room',NULL),
(2,'Calm bedroom retreat','Ideas and pieces for a Calm bedroom retreat.',NULL,1,'2026-11-02 04:20:49','2026-11-02 04:20:49','calm-bedroom-retreat','https://images.unsplash.com/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1200&h=800&q=70','bedroom',NULL),
(3,'Modular kitchen ideas','Ideas and pieces for a Modular kitchen ideas.',NULL,1,'2026-11-02 04:20:49','2026-11-02 04:20:49','modular-kitchen-ideas','https://images.unsplash.com/photo-1559554704-0f74b35a8718?auto=format&fit=crop&w=1200&h=800&q=70','kitchen',NULL),
(4,'Spa-style bathroom','Ideas and pieces for a Spa-style bathroom.',NULL,1,'2026-11-02 04:20:49','2026-11-02 04:20:49','spa-style-bathroom','https://images.unsplash.com/photo-1576698483491-8c43f0862543?auto=format&fit=crop&w=1200&h=800&q=70','bathroom',NULL),
(5,'Productive home office','Ideas and pieces for a Productive home office.',NULL,1,'2026-11-02 04:20:49','2026-11-02 04:20:49','productive-home-office','https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=1200&h=800&q=70','office',NULL);
/*!40000 ALTER TABLE `room_ideas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `menus`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` (`id`, `name`, `is_active`, `created_at`, `updated_at`, `location`, `items`) VALUES (1,'Main menu',1,'2026-11-02 04:20:52','2026-11-03 04:00:39','header','[{\"label\":\"Blog\",\"url\":\"\\/blog\"}]'),
(2,'Customer care',0,'2026-11-02 04:20:52','2026-11-02 04:20:52','footer','[{\"label\":\"Contact Us\",\"url\":\"\\/page\\/contact-us\"},{\"label\":\"Shipping Policy\",\"url\":\"\\/page\\/shipping-policy\"}]'),
(3,'Company',0,'2026-11-02 04:20:52','2026-11-02 04:20:52','footer','[{\"label\":\"About Us\",\"url\":\"\\/page\\/about-us\"},{\"label\":\"Careers\",\"url\":\"\\/page\\/careers\"}]'),
(4,'Shop by room',0,'2026-11-02 04:20:52','2026-11-02 04:20:52','footer','[{\"label\":\"Dining\",\"url\":\"\\/search?category=11\"},{\"label\":\"Office\",\"url\":\"\\/search?category=5\"}]'),
(5,'Legal',0,'2026-11-02 04:20:52','2026-11-02 04:20:52','footer','[{\"label\":\"Privacy Policy\",\"url\":\"\\/page\\/privacy-policy\"},{\"label\":\"Terms\",\"url\":\"\\/page\\/terms-and-conditions\"}]');
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `sliders`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sliders` WRITE;
/*!40000 ALTER TABLE `sliders` DISABLE KEYS */;
INSERT INTO `sliders` (`id`, `title`, `image_url`, `is_active`, `created_at`, `updated_at`, `name`, `location`, `slides`, `sort_order`) VALUES (1,'Home hero',NULL,1,'2026-11-02 04:20:52','2026-11-04 04:38:09','Home hero','home','[{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Home hero: Living Room\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Home hero: Bedroom\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1604578762246-41134e37f9cc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Home hero: Dining\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"}]',0),
(2,'Festive',NULL,0,'2026-11-02 04:20:52','2026-11-04 04:38:09','Festive','home','[{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Festive: Living Room\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Festive: Bedroom\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1604578762246-41134e37f9cc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Festive: Dining\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"}]',1),
(3,'Monsoon',NULL,0,'2026-11-02 04:20:52','2026-11-04 04:38:09','Monsoon','home','[{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Monsoon: Living Room\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Monsoon: Bedroom\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1604578762246-41134e37f9cc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Monsoon: Dining\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"}]',2),
(4,'New arrivals',NULL,0,'2026-11-02 04:20:52','2026-11-04 04:38:09','New arrivals','home','[{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"New arrivals: Living Room\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"New arrivals: Bedroom\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1604578762246-41134e37f9cc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"New arrivals: Dining\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"}]',3),
(5,'Clearance',NULL,0,'2026-11-02 04:20:52','2026-11-04 04:38:09','Clearance','home','[{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Clearance: Living Room\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1635594202056-9ea3b497e5c0?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Clearance: Bedroom\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"},{\"image\":\"https:\\/\\/images.unsplash.com\\/photo-1604578762246-41134e37f9cc?auto=format&fit=crop&w=1600&h=600&q=70\",\"title\":\"Clearance: Dining\",\"text\":\"Shop the rooms\",\"button\":\"Shop now\",\"link\":\"\\/search\"}]',4);
/*!40000 ALTER TABLE `sliders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-04  5:45:45

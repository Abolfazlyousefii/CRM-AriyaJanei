/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ariya_product_varieties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ariya_product_varieties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ariya_product_id` bigint(20) unsigned NOT NULL,
  `ariya_variety_id` bigint(20) unsigned DEFAULT NULL,
  `model_name` varchar(255) NOT NULL DEFAULT '-',
  `unique_key` varchar(255) DEFAULT NULL,
  `price` bigint(20) NOT NULL DEFAULT 0,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `is_placeholder` tinyint(1) NOT NULL DEFAULT 0,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ariya_product_varieties_ariya_product_id_ariya_variety_id_unique` (`ariya_product_id`,`ariya_variety_id`),
  KEY `ariya_product_varieties_ariya_variety_id_index` (`ariya_variety_id`),
  CONSTRAINT `ariya_product_varieties_ariya_product_id_foreign` FOREIGN KEY (`ariya_product_id`) REFERENCES `ariya_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ariya_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ariya_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ariya_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `base_price` bigint(20) NOT NULL DEFAULT 0,
  `base_quantity` int(11) NOT NULL DEFAULT 0,
  `has_varieties` tinyint(1) NOT NULL DEFAULT 0,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ariya_products_ariya_id_unique` (`ariya_id`),
  KEY `ariya_products_base_quantity_index` (`base_quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `crm_order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variety_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_order_items_crm_order_id_product_id_index` (`crm_order_id`,`product_id`),
  CONSTRAINT `crm_order_items_crm_order_id_foreign` FOREIGN KEY (`crm_order_id`) REFERENCES `crm_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `lock_expires_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `customer_name` varchar(255) NOT NULL,
  `customer_mobile` varchar(20) NOT NULL,
  `customer_address` text NOT NULL,
  `province_id` bigint(20) unsigned NOT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_id` bigint(20) unsigned NOT NULL,
  `shipping_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `total_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `ariya_customer_id` bigint(20) unsigned DEFAULT NULL,
  `ariya_address_id` bigint(20) unsigned DEFAULT NULL,
  `ariya_order_id` bigint(20) unsigned DEFAULT NULL,
  `embed_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_orders_uuid_unique` (`uuid`),
  KEY `crm_orders_created_by_index` (`created_by`),
  KEY `crm_orders_assigned_to_index` (`assigned_to`),
  KEY `crm_orders_status_index` (`status`),
  KEY `crm_orders_customer_mobile_index` (`customer_mobile`),
  KEY `crm_orders_ariya_customer_id_index` (`ariya_customer_id`),
  KEY `crm_orders_ariya_address_id_index` (`ariya_address_id`),
  KEY `crm_orders_ariya_order_id_index` (`ariya_order_id`),
  KEY `crm_orders_embed_token_index` (`embed_token`),
  KEY `crm_orders_locked_by_index` (`locked_by`),
  KEY `crm_orders_lock_expires_at_index` (`lock_expires_at`),
  CONSTRAINT `crm_orders_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category_value` varchar(255) DEFAULT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `base_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `final_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_products_slug_unique` (`slug`),
  KEY `custom_products_category_value_index` (`category_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` text DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_notes_customer_id_foreign` (`customer_id`),
  KEY `customer_notes_user_id_foreign` (`user_id`),
  CONSTRAINT `customer_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_satisfaction_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_satisfaction_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submitted_at` date DEFAULT NULL,
  `shipment_sent_at` date DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_family` varchar(255) DEFAULT NULL,
  `shipping_method` enum('barbari','tipax','rahmati','ghafari','nadi','hozori') DEFAULT NULL,
  `satisfaction_status` enum('satisfied','unsatisfied') DEFAULT NULL,
  `operator_communication_score` tinyint(3) unsigned DEFAULT NULL,
  `shipment_score` tinyint(3) unsigned DEFAULT NULL,
  `product_quality_score` tinyint(3) unsigned DEFAULT NULL,
  `needs_consultation` enum('yes','no') DEFAULT NULL,
  `wants_in_person_purchase` enum('yes','no') DEFAULT NULL,
  `assigned_to_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned NOT NULL,
  `referral_note` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `result` text DEFAULT NULL,
  `result_filled_at` timestamp NULL DEFAULT NULL,
  `referral_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `purchase_status` varchar(30) DEFAULT NULL,
  `no_purchase_reason` varchar(50) DEFAULT NULL,
  `sales_response_score` tinyint(3) unsigned DEFAULT NULL,
  `support_positive_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`support_positive_features`)),
  `warranty_explained` varchar(3) DEFAULT NULL,
  `warranty_meets_needs` varchar(3) DEFAULT NULL,
  `shipping_time_score` tinyint(3) unsigned DEFAULT NULL,
  `packaging_quality_score` tinyint(3) unsigned DEFAULT NULL,
  `product_value_satisfied` varchar(3) DEFAULT NULL,
  `would_recommend` varchar(3) DEFAULT NULL,
  `would_choose_again` varchar(3) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `customer_seller_user_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_satisfaction_forms_assigned_to_user_id_foreign` (`assigned_to_user_id`),
  KEY `customer_satisfaction_forms_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `customer_satisfaction_forms_purchase_status_index` (`purchase_status`),
  KEY `customer_satisfaction_forms_no_purchase_reason_index` (`no_purchase_reason`),
  KEY `customer_satisfaction_forms_customer_id_foreign` (`customer_id`),
  KEY `customer_satisfaction_forms_customer_seller_user_id_foreign` (`customer_seller_user_id`),
  CONSTRAINT `customer_satisfaction_forms_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_satisfaction_forms_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_satisfaction_forms_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_satisfaction_forms_customer_seller_user_id_foreign` FOREIGN KEY (`customer_seller_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_number` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `DISC` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `province` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `reference_type_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `marketer_changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_phone_unique` (`phone`),
  UNIQUE KEY `customers_customer_number_unique` (`customer_number`),
  KEY `customers_category_id_foreign` (`category_id`),
  KEY `customers_reference_type_id_foreign` (`reference_type_id`),
  KEY `customers_user_id_foreign` (`user_id`),
  CONSTRAINT `customers_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_reference_type_id_foreign` FOREIGN KEY (`reference_type_id`) REFERENCES `reference_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `embed_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `embed_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `embed_tokens_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluation_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluation_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `target_user_id` bigint(20) unsigned NOT NULL,
  `score` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluation_answers_question_id_foreign` (`question_id`),
  KEY `evaluation_answers_user_id_foreign` (`user_id`),
  KEY `evaluation_answers_target_user_id_foreign` (`target_user_id`),
  CONSTRAINT `evaluation_answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `evaluation_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluation_answers_target_user_id_foreign` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluation_answers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluation_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluation_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `evaluator_role` varchar(255) NOT NULL,
  `target_role` varchar(255) NOT NULL,
  `department_role` varchar(255) DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluation_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluation_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluation_questions_form_id_foreign` (`form_id`),
  CONSTRAINT `evaluation_questions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `evaluation_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_group_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_messages_sender_id_foreign` (`sender_id`),
  KEY `group_messages_message_group_id_created_at_index` (`message_group_id`,`created_at`),
  CONSTRAINT `group_messages_message_group_id_foreign` FOREIGN KEY (`message_group_id`) REFERENCES `message_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_attachments_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_attachments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `invoice_date` date NOT NULL,
  `total_amount` bigint(20) NOT NULL DEFAULT 0,
  `attachment_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoices_customer_id_foreign` (`customer_id`),
  KEY `invoices_user_id_foreign` (`user_id`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leaves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `substitute_user_id` bigint(20) unsigned DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `accountant_id` bigint(20) unsigned DEFAULT NULL,
  `super_manager_id` bigint(20) unsigned DEFAULT NULL,
  `leave_type` varchar(255) NOT NULL,
  `leave_unit` enum('روزانه','ساعتی') NOT NULL DEFAULT 'روزانه',
  `start_date` text NOT NULL,
  `end_date` text DEFAULT NULL,
  `start_time` text DEFAULT NULL,
  `end_time` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leaves_user_id_foreign` (`user_id`),
  KEY `leaves_manager_id_foreign` (`manager_id`),
  KEY `leaves_accountant_id_foreign` (`accountant_id`),
  KEY `leaves_super_manager_id_foreign` (`super_manager_id`),
  KEY `leaves_substitute_user_id_foreign` (`substitute_user_id`),
  CONSTRAINT `leaves_accountant_id_foreign` FOREIGN KEY (`accountant_id`) REFERENCES `users` (`id`),
  CONSTRAINT `leaves_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`),
  CONSTRAINT `leaves_substitute_user_id_foreign` FOREIGN KEY (`substitute_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leaves_super_manager_id_foreign` FOREIGN KEY (`super_manager_id`) REFERENCES `users` (`id`),
  CONSTRAINT `leaves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_group_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_group_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_group_user_message_group_id_user_id_unique` (`message_group_id`,`user_id`),
  KEY `message_group_user_user_id_foreign` (`user_id`),
  CONSTRAINT `message_group_user_message_group_id_foreign` FOREIGN KEY (`message_group_id`) REFERENCES `message_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_group_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `creator_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_groups_creator_id_foreign` (`creator_id`),
  CONSTRAINT `message_groups_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_sender_id_foreign` (`sender_id`),
  KEY `messages_receiver_id_foreign` (`receiver_id`),
  CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `leave_id` bigint(20) unsigned DEFAULT NULL,
  `task_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_leave_id_foreign` (`leave_id`),
  KEY `notifications_task_id_foreign` (`task_id`),
  KEY `notifications_user_id_task_id_index` (`user_id`,`task_id`),
  CONSTRAINT `notifications_leave_id_foreign` FOREIGN KEY (`leave_id`) REFERENCES `leaves` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_price_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_price_overrides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_key` varchar(255) NOT NULL,
  `base_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `final_price` bigint(20) unsigned NOT NULL DEFAULT 0,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_price_overrides_product_key_unique` (`product_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `condition` int(11) NOT NULL,
  `percent` decimal(5,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscribable_type` varchar(255) NOT NULL,
  `subscribable_id` bigint(20) unsigned NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `public_key` varchar(255) DEFAULT NULL,
  `auth_token` varchar(255) DEFAULT NULL,
  `content_encoding` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_subscribable_type_subscribable_id_index` (`subscribable_type`,`subscribable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reference_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reference_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_types_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `repeat` enum('once','daily','weekly','monthly') NOT NULL DEFAULT 'once',
  `remind_at` datetime NOT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminders_user_id_foreign` (`user_id`),
  CONSTRAINT `reminders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_attachments_report_id_foreign` (`report_id`),
  CONSTRAINT `report_attachments_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('draft','submitted','read') NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `voice_path` varchar(255) DEFAULT NULL,
  `successful_calls` int(11) NOT NULL DEFAULT 0,
  `unsuccessful_calls` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reports_user_id_foreign` (`user_id`),
  CONSTRAINT `reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `request_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `super_manager_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_tickets_user_id_foreign` (`user_id`),
  KEY `request_tickets_manager_id_foreign` (`manager_id`),
  KEY `request_tickets_super_manager_id_foreign` (`super_manager_id`),
  CONSTRAINT `request_tickets_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `request_tickets_super_manager_id_foreign` FOREIGN KEY (`super_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `request_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
DROP TABLE IF EXISTS `shop_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(100) NOT NULL,
  `city` varchar(60) NOT NULL,
  `relation_type` enum('مرتبط','غیر مرتبط') NOT NULL,
  `address` varchar(200) NOT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `shop_name` varchar(120) NOT NULL,
  `owner_name` varchar(120) NOT NULL,
  `owner_phone` varchar(30) NOT NULL,
  `cooperation_interest` varchar(50) NOT NULL,
  `activity_field` varchar(100) DEFAULT NULL,
  `shop_size` varchar(50) DEFAULT NULL,
  `shop_location` varchar(50) DEFAULT NULL,
  `shop_grade` varchar(150) DEFAULT NULL,
  `main_goods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`main_goods`)),
  `arya_customer` varchar(20) DEFAULT NULL,
  `payment_terms` varchar(20) DEFAULT NULL,
  `nr_activity` varchar(120) DEFAULT NULL,
  `nr_activity_other` varchar(120) DEFAULT NULL,
  `nr_goods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nr_goods`)),
  `nr_goods_other` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `due_at` datetime DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_user_id_foreign` (`user_id`),
  CONSTRAINT `tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `sales` int(11) NOT NULL DEFAULT 0,
  `commission` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_products_user_id_foreign` (`user_id`),
  KEY `user_products_product_id_foreign` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `blocked_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `deactivation_reason` text DEFAULT NULL,
  `deactivated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `force_password_reset` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_manager_id_foreign` (`manager_id`),
  KEY `users_deactivated_by_foreign` (`deactivated_by`),
  KEY `users_is_active_index` (`is_active`),
  CONSTRAINT `users_deactivated_by_foreign` FOREIGN KEY (`deactivated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_06_02_052518_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_06_02_060043_add_phone_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_06_02_061908_make_email_nullable_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_06_02_062252_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_06_02_062434_create_reference_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_06_02_062521_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_06_02_062837_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_06_02_063003_create_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_06_02_063143_create_invoice_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_06_02_063650_create_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_06_17_065851_add_culomn_status_submittedat_feedback_rating_to_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_06_24_105857_create_customer_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_08_10_143858_add_unique_to_phone_in_customers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_08_27_051017_create_products_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_08_27_051614_user_products_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_08_27_113616_add_price_to_products_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_09_11_120119_add_marketer_changed_at_to_customers_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_09_11_235408_add_marketer_changed_at_to_customers_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_09_14_054915_create_activity_log_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_09_14_054916_add_event_column_to_activity_log_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_09_14_054917_add_batch_uuid_column_to_activity_log_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_09_15_094538_add_blocked_until_to_users_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_09_15_210016_add_blocked_until_to_users_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_09_15_111553_create_tasks_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_09_16_012649_create_tasks_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_09_16_013549_create_tasks_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_09_17_092329_add_manager_id_to_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_09_18_161907_create_leaves_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_09_20_092054_add_role_to_users_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_09_27_142117_create_reminders_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_09_28_093505_create_notifications_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_09_28_154354_create_messages_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_09_30_123844_add_voice_path_to_reports_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_10_01_102640_add_force_password_reset_to_users_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_10_01_153210_create_push_subscriptions_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_10_02_112506_create_evaluations_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_10_03_130634_add_department_role_to_evaluation_forms_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_10_03_151451_make_question_id_nullable_in_evaluation_answers_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_10_05_110837_add_seen_at_to_messages_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_10_05_153034_add_deleted_at_to_leaves_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_10_06_000000_create_request_tickets_table2025_10_06_000000_create_request_tickets_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_10_13_091739_add_calls_to_reports_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_12_11_105301_add_attachment_path_to_invoices_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_12_11_111112_create_invoice_attachments_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_12_11_113150_make_category_id_nullable_in_customers_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_12_21_151525_create_embed_tokens_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_01_21_095831_create_shop_contacts_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_01_28_102849_add_description_to_shop_contacts_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_02_02_000001_create_crm_orders_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_02_02_000002_create_crm_order_items_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_02_02_000003_add_lock_fields_to_crm_orders',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_02_03_150439_create_ariya_products_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_02_03_150519_create_ariya_product_varieties_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_02_10_000001_create_customer_satisfaction_forms_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_02_10_000002_add_shipment_sent_at_to_customer_satisfaction_forms_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_02_10_000003_add_referral_seen_at_to_customer_satisfaction_forms_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_02_17_120000_create_message_groups_tables',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_02_17_130000_create_group_messages_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_02_18_000001_add_province_city_to_customers_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_03_28_000001_add_customer_number_to_customers_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_03_29_000001_create_announcements_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_03_30_120000_add_substitute_to_leaves_and_leave_id_to_notifications',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_03_31_100000_add_due_at_to_tasks_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_03_31_100100_add_task_id_to_notifications_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_04_1_134350_add_leave_unit_to_leaves_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_05_16_000001_add_description_to_customer_satisfaction_forms_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_05_18_000001_make_customer_satisfaction_fields_optional_and_add_new_metrics',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_06_02_000001_create_custom_products_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_06_02_000002_create_product_price_overrides_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_07_25_000001_add_v2_fields_to_customer_satisfaction_forms_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_07_26_000001_add_customer_links_to_customer_satisfaction_forms_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_09_25_000001_add_lifecycle_fields_to_users_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_09_25_000002_protect_user_owned_records_from_cascade_deletion',47);

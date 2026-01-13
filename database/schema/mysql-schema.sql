/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `archive_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `archive_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(255) NOT NULL,
  `retention_months` int(11) NOT NULL DEFAULT 36,
  `date_column` varchar(255) NOT NULL DEFAULT 'created_at',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_archived_at` timestamp NULL DEFAULT NULL,
  `records_archived` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `archive_policies_table_name_unique` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artisan_command_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artisan_command_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `artisan_command_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artisan_commands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artisan_commands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `command` varchar(255) NOT NULL,
  `signature` text DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parameters_description` text DEFAULT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `artisan_commands_command_unique` (`command`),
  KEY `artisan_commands_category_id_foreign` (`category_id`),
  CONSTRAINT `artisan_commands_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `artisan_command_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `swift` varchar(20) DEFAULT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'CZ',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `banks_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `idx_cache_expiration` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ico` varchar(255) DEFAULT NULL,
  `dic` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `shortcut` varchar(255) DEFAULT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `country` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_ico_unique` (`ico`),
  KEY `idx_clients_user_created` (`user_id`,`created_at`),
  KEY `idx_clients_user_default` (`user_id`,`is_default`),
  KEY `idx_clients_user_ico` (`user_id`,`ico`),
  KEY `idx_clients_user_name_search` (`user_id`,`name`),
  KEY `idx_clients_user_email_search` (`user_id`,`email`),
  CONSTRAINT `clients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cron_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `command` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `custom_expression` varchar(255) DEFAULT NULL,
  `run_at` time DEFAULT NULL,
  `day_of_week` int(11) DEFAULT NULL,
  `day_of_month` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `last_run` timestamp NULL DEFAULT NULL,
  `last_output` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `database_health_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `database_health_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(255) NOT NULL,
  `severity` enum('info','warning','critical') NOT NULL,
  `message` text NOT NULL,
  `metric_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alert_type_severity` (`alert_type`,`severity`),
  KEY `idx_alert_resolved_time` (`resolved`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `database_health_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `database_health_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(255) NOT NULL,
  `metric_value` decimal(15,4) NOT NULL,
  `metric_unit` varchar(255) DEFAULT NULL,
  `status` enum('good','warning','critical') NOT NULL DEFAULT 'good',
  `recommendation` text DEFAULT NULL,
  `measured_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_health_metric_time` (`metric_name`,`measured_at`),
  KEY `idx_health_status_time` (`status`,`measured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `database_maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `database_maintenance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_type` varchar(255) NOT NULL,
  `table_name` varchar(255) NOT NULL,
  `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`results`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_maintenance_task_status` (`task_type`,`status`),
  KEY `idx_maintenance_table_date` (`table_name`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `database_size_monitor`;
/*!50001 DROP VIEW IF EXISTS `database_size_monitor`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `database_size_monitor` AS SELECT
 1 AS `table_name`,
  1 AS `row_count`,
  1 AS `size_mb`,
  1 AS `data_size_mb`,
  1 AS `index_size_mb`,
  1 AS `storage_engine` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `entity_limit_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entity_limit_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `entity_type` varchar(50) NOT NULL COMMENT 'Type of entity (invoice, client, etc.)',
  `metric_type` varchar(50) NOT NULL COMMENT 'Metric type (count, value, size)',
  `period_type` varchar(50) NOT NULL COMMENT 'Period type (daily, monthly, etc.)',
  `period_start` datetime NOT NULL COMMENT 'Start of the tracking period',
  `period_end` datetime NOT NULL COMMENT 'End of the tracking period',
  `current_value` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Current usage value',
  `last_reset_at` timestamp NULL DEFAULT NULL COMMENT 'When usage was last reset',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usage_record` (`user_id`,`entity_type`,`metric_type`,`period_type`,`period_start`),
  KEY `entity_limit_usage_user_id_entity_type_metric_type_index` (`user_id`,`entity_type`,`metric_type`),
  KEY `entity_limit_usage_period_start_period_end_index` (`period_start`,`period_end`),
  KEY `entity_limit_usage_entity_type_period_type_index` (`entity_type`,`period_type`),
  CONSTRAINT `entity_limit_usage_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entity_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entity_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(255) NOT NULL COMMENT 'Permission that this limit applies to',
  `entity_type` varchar(255) NOT NULL COMMENT 'Entity type: invoice, client, supplier, product, etc.',
  `limit_value` int(11) NOT NULL COMMENT 'Maximum allowed value for this limit',
  `default_period` varchar(20) NOT NULL DEFAULT 'monthly' COMMENT 'Default period type for this entity',
  `period_type` enum('daily','weekly','monthly','yearly','lifetime') NOT NULL DEFAULT 'monthly',
  `metric_type` varchar(255) NOT NULL DEFAULT 'count' COMMENT 'Type of metric: count, value, size',
  `description` varchar(255) DEFAULT NULL COMMENT 'Human readable description',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this limit is currently active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entity_limits_unique` (`permission_name`,`entity_type`,`period_type`,`metric_type`),
  KEY `entity_limits_permission_name_index` (`permission_name`),
  KEY `entity_limits_permission_name_entity_type_index` (`permission_name`,`entity_type`),
  KEY `entity_limits_entity_type_period_type_index` (`entity_type`,`period_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL COMMENT 'Color code for visual representation',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'CZK',
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachments` text NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_included` tinyint(1) NOT NULL DEFAULT 1,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_user_id_foreign` (`user_id`),
  KEY `expenses_supplier_id_foreign` (`supplier_id`),
  KEY `expenses_category_id_foreign` (`category_id`),
  KEY `expenses_payment_method_id_foreign` (`payment_method_id`),
  KEY `expenses_status_id_foreign` (`status_id`),
  CONSTRAINT `expenses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
DROP TABLE IF EXISTS `invoice_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_custom_product` tinyint(1) NOT NULL DEFAULT 0,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_product_invoice_id_product_id_unique` (`invoice_id`,`product_id`),
  KEY `idx_invoice_products_invoice_created` (`invoice_id`,`created_at`),
  KEY `idx_invoice_products_product_created` (`product_id`,`created_at`),
  CONSTRAINT `invoice_product_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_products_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_products_archive` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_invoice_id` bigint(20) unsigned NOT NULL,
  `archive_invoice_id` bigint(20) unsigned NOT NULL,
  `products_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`products_data`)),
  `archived_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_archive_products_invoice` (`archive_invoice_id`),
  KEY `idx_archive_products_original` (`original_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_statistics`;
/*!50001 DROP VIEW IF EXISTS `invoice_statistics`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `invoice_statistics` AS SELECT
 1 AS `month_year`,
  1 AS `user_id`,
  1 AS `invoice_count`,
  1 AS `total_revenue`,
  1 AS `avg_invoice_amount`,
  1 AS `min_invoice_amount`,
  1 AS `max_invoice_amount`,
  1 AS `paid_invoices`,
  1 AS `unpaid_invoices`,
  1 AS `overdue_invoices` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_vs` varchar(50) NOT NULL,
  `invoice_ks` varchar(20) DEFAULT NULL,
  `invoice_ss` varchar(20) DEFAULT NULL,
  `due_in` int(11) NOT NULL,
  `payment_status_id` bigint(20) unsigned DEFAULT NULL,
  `issue_date` date NOT NULL,
  `tax_point_date` date DEFAULT NULL,
  `payment_amount` int(11) NOT NULL,
  `payment_currency` varchar(3) NOT NULL,
  `template` varchar(255) NOT NULL DEFAULT 'default',
  `invoice_logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invoice_text` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoices_client_id_index` (`client_id`),
  KEY `invoices_user_id_index` (`user_id`),
  KEY `invoices_payment_method_id_index` (`payment_method_id`),
  KEY `invoices_supplier_id_foreign` (`supplier_id`),
  KEY `idx_invoices_user_created` (`user_id`,`created_at`),
  KEY `idx_invoices_user_issue_date` (`user_id`,`issue_date`),
  KEY `idx_invoices_user_client` (`user_id`,`client_id`),
  KEY `idx_invoices_payment_calc` (`user_id`,`issue_date`,`due_in`),
  KEY `idx_invoices_user_payment_status` (`user_id`,`payment_status_id`),
  KEY `idx_invoices_user_invoice_vs` (`user_id`,`invoice_vs`),
  CONSTRAINT `invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices_archive` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_vs` varchar(50) NOT NULL,
  `invoice_ks` varchar(20) DEFAULT NULL,
  `invoice_ss` varchar(20) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_in` int(11) DEFAULT NULL,
  `payment_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `payment_status_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`invoice_data`)),
  `archived_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_archive_user_archived` (`user_id`,`archived_at`),
  KEY `idx_archive_original_id` (`original_id`),
  KEY `idx_archive_issue_date` (`issue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mysql_optimization_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mysql_optimization_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(255) NOT NULL,
  `current_value` varchar(255) DEFAULT NULL,
  `recommended_value` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('high','medium','low') NOT NULL DEFAULT 'medium',
  `applied` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_optimization_priority_applied` (`priority`,`applied`),
  KEY `idx_optimization_setting_applied` (`setting_name`,`applied`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_categories_slug_unique` (`slug`),
  KEY `page_categories_slug_index` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `publishing_start` datetime DEFAULT NULL,
  `publishing_end` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_published_by_foreign` (`published_by`),
  KEY `pages_published_index` (`published`),
  KEY `pages_category_id_index` (`category_id`),
  KEY `pages_parent_id_index` (`parent_id`),
  KEY `pages_sort_order_index` (`sort_order`),
  KEY `pages_slug_index` (`slug`),
  KEY `pages_published_publishing_start_publishing_end_index` (`published`,`publishing_start`,`publishing_end`),
  CONSTRAINT `pages_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `page_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pages_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pages_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `gateway` varchar(255) NOT NULL,
  `gateway_payment_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'CZK',
  `payment_method` varchar(255) DEFAULT NULL,
  `gateway_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_data`)),
  `failure_reason` text DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_gateway_gateway_payment_id_index` (`gateway`,`gateway_payment_id`),
  KEY `payments_subscription_id_status_index` (`subscription_id`,`status`),
  KEY `payments_status_paid_at_index` (`status`,`paid_at`),
  CONSTRAINT `payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `metric_type` varchar(255) NOT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `query_type` varchar(255) DEFAULT NULL,
  `metric_value` decimal(10,4) NOT NULL,
  `metric_unit` varchar(255) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `measured_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_metrics_type_date` (`metric_type`,`measured_at`),
  KEY `idx_metrics_table_date` (`table_name`,`measured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Supplier ID',
  `user_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'CZK',
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_tax_id_foreign` (`tax_id`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `idx_products_user_created` (`user_id`,`created_at`),
  KEY `idx_products_user_name` (`user_id`,`name`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`),
  CONSTRAINT `products_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes` (`id`),
  CONSTRAINT `products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `query_performance_monitor`;
/*!50001 DROP VIEW IF EXISTS `query_performance_monitor`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `query_performance_monitor` AS SELECT
 1 AS `table_name`,
  1 AS `query_type`,
  1 AS `row_count`,
  1 AS `description` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `sessions_last_activity_index` (`last_activity`),
  KEY `idx_sessions_user_activity` (`user_id`,`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `status_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL COMMENT 'CSS třída určující barvu stavu',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Zda je stav aktivní a lze ho vybrat',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `statuses_slug_unique` (`slug`),
  KEY `statuses_category_id_foreign` (`category_id`),
  CONSTRAINT `statuses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `status_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plan_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plan_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plan_features_slug_unique` (`slug`),
  KEY `subscription_plan_features_is_active_sort_order_index` (`is_active`,`sort_order`),
  KEY `subscription_plan_features_slug_index` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plan_subscription_plan_feature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plan_subscription_plan_feature` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `subscription_plan_feature_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_spf_unique` (`subscription_plan_id`,`subscription_plan_feature_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'CZK',
  `billing_period` enum('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `billing_interval` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `trial_days` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_plans_is_active_billing_period_index` (`is_active`,`billing_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending','active','cancelled','expired','past_due') NOT NULL DEFAULT 'pending',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `next_billing_at` timestamp NULL DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'CZK',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `subscriptions_user_id_status_index` (`user_id`,`status`),
  KEY `subscriptions_status_next_billing_at_index` (`status`,`next_billing_at`),
  CONSTRAINT `subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ico` varchar(255) DEFAULT NULL,
  `dic` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `shortcut` varchar(255) DEFAULT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `country` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `supplier_logo` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `bank_code` varchar(255) DEFAULT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `swift` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `has_payment_info` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_ico_unique` (`ico`),
  KEY `idx_suppliers_user_created` (`user_id`,`created_at`),
  KEY `idx_suppliers_user_default` (`user_id`,`is_default`),
  KEY `idx_suppliers_user_ico` (`user_id`,`ico`),
  KEY `idx_suppliers_user_name_search` (`user_id`,`name`),
  KEY `idx_suppliers_user_email_search` (`user_id`,`email`),
  CONSTRAINT `suppliers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `slug` varchar(255) DEFAULT NULL COMMENT 'Tax slug',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_activity_summary`;
/*!50001 DROP VIEW IF EXISTS `user_activity_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `user_activity_summary` AS SELECT
 1 AS `user_id`,
  1 AS `user_name`,
  1 AS `user_email`,
  1 AS `total_invoices`,
  1 AS `total_clients`,
  1 AS `total_suppliers`,
  1 AS `total_products`,
  1 AS `last_invoice_date`,
  1 AS `invoices_last_30_days`,
  1 AS `invoices_last_7_days` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `database_size_monitor`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`invoice`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `database_size_monitor` AS select `information_schema`.`TABLES`.`TABLE_NAME` AS `table_name`,`information_schema`.`TABLES`.`TABLE_ROWS` AS `row_count`,round((`information_schema`.`TABLES`.`DATA_LENGTH` + `information_schema`.`TABLES`.`INDEX_LENGTH`) / 1024 / 1024,2) AS `size_mb`,round(`information_schema`.`TABLES`.`DATA_LENGTH` / 1024 / 1024,2) AS `data_size_mb`,round(`information_schema`.`TABLES`.`INDEX_LENGTH` / 1024 / 1024,2) AS `index_size_mb`,`information_schema`.`TABLES`.`ENGINE` AS `storage_engine` from `information_schema`.`TABLES` where `information_schema`.`TABLES`.`TABLE_SCHEMA` = database() order by `information_schema`.`TABLES`.`DATA_LENGTH` + `information_schema`.`TABLES`.`INDEX_LENGTH` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `invoice_statistics`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`invoice`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `invoice_statistics` AS select date_format(`invoices`.`issue_date`,'%Y-%m') AS `month_year`,`invoices`.`user_id` AS `user_id`,count(0) AS `invoice_count`,sum(`invoices`.`payment_amount`) AS `total_revenue`,avg(`invoices`.`payment_amount`) AS `avg_invoice_amount`,min(`invoices`.`payment_amount`) AS `min_invoice_amount`,max(`invoices`.`payment_amount`) AS `max_invoice_amount`,count(case when `invoices`.`payment_status_id` = 1 then 1 end) AS `paid_invoices`,count(case when `invoices`.`payment_status_id` = 2 then 1 end) AS `unpaid_invoices`,count(case when `invoices`.`payment_status_id` = 3 then 1 end) AS `overdue_invoices` from `invoices` where `invoices`.`issue_date` >= current_timestamp() - interval 24 month group by date_format(`invoices`.`issue_date`,'%Y-%m'),`invoices`.`user_id` order by date_format(`invoices`.`issue_date`,'%Y-%m') desc,`invoices`.`user_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `query_performance_monitor`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`invoice`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `query_performance_monitor` AS select 'invoices' AS `table_name`,'user_invoices_listing' AS `query_type`,count(0) AS `row_count`,'SELECT with user_id filter' AS `description` from `invoices` union all select 'clients' AS `table_name`,'user_clients_listing' AS `query_type`,count(0) AS `row_count`,'SELECT with user_id filter' AS `description` from `clients` union all select 'suppliers' AS `table_name`,'user_suppliers_listing' AS `query_type`,count(0) AS `row_count`,'SELECT with user_id filter' AS `description` from `suppliers` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `user_activity_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`invoice`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `user_activity_summary` AS select `u`.`id` AS `user_id`,`u`.`name` AS `user_name`,`u`.`email` AS `user_email`,count(distinct `i`.`id`) AS `total_invoices`,count(distinct `c`.`id`) AS `total_clients`,count(distinct `s`.`id`) AS `total_suppliers`,count(distinct `p`.`id`) AS `total_products`,max(`i`.`created_at`) AS `last_invoice_date`,sum(case when `i`.`created_at` >= current_timestamp() - interval 30 day then 1 else 0 end) AS `invoices_last_30_days`,sum(case when `i`.`created_at` >= current_timestamp() - interval 7 day then 1 else 0 end) AS `invoices_last_7_days` from ((((`users` `u` left join `invoices` `i` on(`u`.`id` = `i`.`user_id`)) left join `clients` `c` on(`u`.`id` = `c`.`user_id`)) left join `suppliers` `s` on(`u`.`id` = `s`.`user_id`)) left join `products` `p` on(`u`.`id` = `p`.`user_id`)) group by `u`.`id`,`u`.`name`,`u`.`email` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2020_03_31_114745_remove_backpackuser_model',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_05_09_create_banks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_04_05_132541_create_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_04_05_142251_create_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_04_05_190655_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_04_06_141058_create_payment_methods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_04_06_145533_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_04_06_145600_create_entity_limits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_04_06_145603_create_entity_limit_usage_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_04_07_135904_add_phone_and_email_to_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_04_07_142905_rename_and_add_fields_to_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_04_07_143416_add_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_04_07_145426_add_phone_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_04_07_154550_add_user_id_to_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_04_08_111907_create_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_04_08_171332_rename_payment_days_to_due_in_in_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_04_08_203900_update_due_in_column_type_to_integer',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_04_09_193751_convert_payment_status_strings_to_ids',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_04_09_202051_remove_payment_status_column_from_invoices',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_04_10_110200_make_clients_columns_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_04_10_203343_add_description_to_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_04_11_141533_create_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_04_13_125251_add_color_and_is_active_columns_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_04_15_153440_add_user_id_email_phone_to_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_04_15_161848_update_clients_table_add_description',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_04_15_162604_up_invoices_add_supplier_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_04_16_000000_remove_address_fields_from_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_04_16_000001_add_is_default_to_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_04_17_155011_up_clients_t_add_is_default',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_04_18_162126_up_invoices_t_set_nullable_invoices_ss_and_invoices_ks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_04_23_000126_add_payment_info_to_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_05_05_130626_create_cron_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_05_07_124909_create_artisan_command_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_05_07_125108_create_artisan_commands_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_05_09_074155_add_type_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_05_09_162351_create_taxes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_05_09_171909_add_slug_field_to_taxes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_05_12_115051_change_zip_column_type_in_clients_suppliers_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_05_13_195357_create_product_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_05_13_195424_create_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_05_14_065757_up__product_t_add_supplier_id_f',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_05_14_120519_cr_product_invoice_t',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_05_15_105646_up_product_t_add_is_active_and_currency_f',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_05_16_000001_enhance_invoice_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_05_16_000001_rename_invoice_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_05_16_000002_migrate_json_data_to_invoice_product',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_05_16_205942_up_invoice_products_t_add_name_f',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_05_19_081937_cr_expense_categories_t',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_05_19_081938_cr_expenses_t',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_05_19_091727_cr_status_categories_t',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_05_19_145430_rm_type_c_from_statuses_t',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_05_21_085206_up_expenses_t_add_tax_included_f',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_05_22_103544_up_expenses_t_change_recipt_file_to_attachements_f',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_05_30_120000_optimize_database_phase_1',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_05_30_130000_optimize_database_phase_2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_05_30_140000_optimize_database_phase_3',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_06_09_113706_seed_user_roes_and_permissions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_06_16_080655_add_description_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_06_18_105532_create_mysql_optimization_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_06_19_105906_add_signature_to_artisan_commands_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_06_19_110220_update_signature_column_length_in_artisan_commands_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_06_19_162330_add_unit_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_06_25_073405_create_page_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_06_25_073454_create_pages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_06_30_084317_add_supplier_logo_to_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_07_01_125653_add_invoice_logo_to_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_07_06_183015_add_template_to_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_07_07_133100_create_subscription_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_07_07_133151_create_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_07_07_133216_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_07_09_080303_create_subscription_plan_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_07_09_081355_create_subscription_plan_feature_pivot_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_07_09_102038_drop_features_column_from_subscription_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_04_06_145600_create_entity_limits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_01_09_120001_create_role_entity_limits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_01_09_120002_create_user_entity_limits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_01_09_120003_create_entity_limit_usage_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_01_10_120000_refactor_entity_limits_for_permissions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_07_11_092056_add_lifetime_to_entity_limits_period_type',2);

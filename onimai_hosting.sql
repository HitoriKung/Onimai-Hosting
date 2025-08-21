-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 21, 2025 at 07:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onimai_hosting`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `shop_url` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `api_key` varchar(64) DEFAULT NULL,
  `confirmed` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `user_id`, `shop_name`, `shop_url`, `phone`, `api_key`, `confirmed`, `created_at`) VALUES
(1, 1, 'test', 'https://test.com', '1234567890', '366dce5ca67298bbe92e7d10e6555b922de95143069fd5b423560c3534b1401e', 1, '2025-08-21 12:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=ใช้งาน,0=ปิด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hosting_categories`
--

CREATE TABLE `hosting_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `directadmin_ip` varchar(45) DEFAULT NULL,
  `directadmin_url` varchar(255) NOT NULL,
  `directadmin_user` varchar(100) DEFAULT NULL,
  `directadmin_pass` varchar(255) DEFAULT NULL,
  `ns1` text NOT NULL,
  `ns2` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `module` text NOT NULL DEFAULT 'directadmin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hosting_categories`
--

INSERT INTO `hosting_categories` (`id`, `name`, `description`, `directadmin_ip`, `directadmin_url`, `directadmin_user`, `directadmin_pass`, `ns1`, `ns2`, `is_active`, `created_at`, `module`) VALUES
(1, 'DirectAdmin SG-1', 'เซิร์ฟเวอร์ประสิทธิภาพสูง เร็วแรง เสถียร ตั้งอยู่ในสิงคโปร์', '192.168.1.100', '', NULL, NULL, '', '', 1, '2025-02-19 14:55:20', 'directadmin');

-- --------------------------------------------------------

--
-- Table structure for table `hosting_orders`
--

CREATE TABLE `hosting_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `domain` varchar(255) NOT NULL,
  `hosting_username` varchar(50) DEFAULT NULL,
  `hosting_password` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','suspended','cancelled') DEFAULT 'pending',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `renewed_at` datetime DEFAULT NULL,
  `renewed_by` varchar(255) NOT NULL,
  `terminated_at` datetime DEFAULT NULL,
  `terminated_by` varchar(255) NOT NULL,
  `suspended_at` datetime DEFAULT NULL,
  `suspended_by` varchar(255) NOT NULL,
  `unsuspended_at` datetime DEFAULT NULL,
  `unsuspended_by` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hosting_orders`
--

INSERT INTO `hosting_orders` (`id`, `user_id`, `package_id`, `domain`, `hosting_username`, `hosting_password`, `status`, `start_date`, `end_date`, `created_at`, `renewed_at`, `renewed_by`, `terminated_at`, `terminated_by`, `suspended_at`, `suspended_by`, `unsuspended_at`, `unsuspended_by`) VALUES
(238, 1, 1, 'hello.com', 'test', 'test', 'active', '2025-07-26 15:06:43', '2025-08-26 15:06:43', '2025-07-26 15:06:43', NULL, '', NULL, '', NULL, '', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `hosting_packages`
--

CREATE TABLE `hosting_packages` (
  `id` int(11) NOT NULL,
  `package_id` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `agent_price` decimal(10,2) DEFAULT NULL,
  `domains_limit` varchar(255) DEFAULT '1',
  `subdomains_limit` varchar(255) DEFAULT '1',
  `space_mb` varchar(255) DEFAULT NULL,
  `bandwidth_gb` varchar(255) DEFAULT NULL,
  `email_accounts` varchar(255) DEFAULT NULL,
  `db_count` varchar(255) DEFAULT NULL,
  `has_ssl` tinyint(1) DEFAULT 1,
  `has_softaculous` tinyint(1) DEFAULT 1,
  `has_directadmin` tinyint(1) DEFAULT 1,
  `has_backup` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hosting_packages`
--

INSERT INTO `hosting_packages` (`id`, `package_id`, `category_id`, `name`, `price_monthly`, `agent_price`, `domains_limit`, `subdomains_limit`, `space_mb`, `bandwidth_gb`, `email_accounts`, `db_count`, `has_ssl`, `has_softaculous`, `has_directadmin`, `has_backup`, `is_active`, `created_at`) VALUES
(1, 'basic', 1, 'Starter Pack SG1', 15.00, 10.00, '1', '1', '100', '10', '1', '1', 1, 1, 1, 1, 1, '2025-02-19 14:58:11');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `redeem_codes`
--

CREATE TABLE `redeem_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `credit_amount` decimal(10,2) NOT NULL,
  `min_topup` decimal(10,2) DEFAULT 0.00,
  `min_spent` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `redeem_history`
--

CREATE TABLE `redeem_history` (
  `id` int(11) NOT NULL,
  `code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `credit_amount` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topup_requests`
--

CREATE TABLE `topup_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'auto_success',
  `created_at` datetime DEFAULT current_timestamp(),
  `api_response` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topup_transactions`
--

CREATE TABLE `topup_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('bank','truewallet','redeem') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `slip_image` varchar(255) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `true_link` varchar(255) DEFAULT NULL,
  `redeem_code` varchar(50) DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `slip_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `type` enum('topup','deduct','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `realname` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `balance` decimal(10,2) DEFAULT 0.00,
  `balance_used` decimal(10,2) DEFAULT 0.00,
  `date_register` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `last_logout` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `realname`, `surname`, `password`, `role`, `balance`, `balance_used`, `date_register`, `last_login`, `status`, `ip`, `last_logout`) VALUES
(1, 'Demo1234', 'demo1234@gmail.com', 'Uma', 'Desu', '$2y$10$221bqyw91CZJ86VssW.DVOLGV9O/vprgE6qOMLx9mFNtYuazGQHZa', 'user', 0.00, 0.00, '2025-08-21 12:14:12', '2025-08-21 12:14:17', 'active', '::1', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hosting_categories`
--
ALTER TABLE `hosting_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hosting_orders`
--
ALTER TABLE `hosting_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `hosting_packages`
--
ALTER TABLE `hosting_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `redeem_history`
--
ALTER TABLE `redeem_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `code_id` (`code_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `topup_requests`
--
ALTER TABLE `topup_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `topup_transactions`
--
ALTER TABLE `topup_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hosting_categories`
--
ALTER TABLE `hosting_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hosting_orders`
--
ALTER TABLE `hosting_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- AUTO_INCREMENT for table `hosting_packages`
--
ALTER TABLE `hosting_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `redeem_history`
--
ALTER TABLE `redeem_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topup_requests`
--
ALTER TABLE `topup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topup_transactions`
--
ALTER TABLE `topup_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `agents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `hosting_orders`
--
ALTER TABLE `hosting_orders`
  ADD CONSTRAINT `hosting_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `hosting_orders_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `hosting_packages` (`id`);

--
-- Constraints for table `hosting_packages`
--
ALTER TABLE `hosting_packages`
  ADD CONSTRAINT `hosting_packages_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `hosting_categories` (`id`);

--
-- Constraints for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  ADD CONSTRAINT `redeem_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `redeem_history`
--
ALTER TABLE `redeem_history`
  ADD CONSTRAINT `redeem_history_ibfk_1` FOREIGN KEY (`code_id`) REFERENCES `redeem_codes` (`id`),
  ADD CONSTRAINT `redeem_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `topup_transactions`
--
ALTER TABLE `topup_transactions`
  ADD CONSTRAINT `topup_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `topup_transactions_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

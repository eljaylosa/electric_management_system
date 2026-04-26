-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 02:38 PM
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
-- Database: `electric_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `reading_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `reading_id`, `amount`, `due_date`, `status`, `created_at`) VALUES
(84, 84, 18.00, '2026-05-25', 'paid', '2026-04-26 02:52:28'),
(85, 85, 18.00, '2026-05-25', 'paid', '2026-04-26 02:52:49'),
(86, 86, 18.00, '2026-04-24', 'paid', '2026-04-26 03:42:24'),
(87, 87, 882.00, '2026-05-26', 'paid', '2026-04-26 11:41:16'),
(88, 88, 72.00, '2026-04-25', 'unpaid', '2026-04-26 12:08:18'),
(89, 89, 54.00, '2026-05-26', 'paid', '2026-04-26 13:03:44'),
(90, 90, 54.00, '2026-05-26', 'paid', '2026-04-26 13:06:09');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `rate_per_kwh` decimal(10,4) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `rate_per_kwh`, `is_deleted`, `deleted_at`) VALUES
(1, 'Residential', 0.1300, 0, NULL),
(2, 'Commercial', 0.1800, 0, NULL),
(3, 'SA AMING NAYON', 0.3400, 1, '2026-04-24 19:59:00'),
(5, 'hehe', 12.2300, 1, '2026-04-24 19:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `consumers`
--

CREATE TABLE `consumers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `meter_no` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `user_id` int(11) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consumers`
--

INSERT INTO `consumers` (`id`, `name`, `address`, `contact`, `meter_no`, `category_id`, `profile_pic`, `user_id`, `is_deleted`, `deleted_at`) VALUES
(45, 'sample', 'Bataan', '12345678912', 'MTR-000045', 2, '1777142216_3.png', 26, 0, NULL),
(47, 'Eljay Losa', 'Bataan', '12345678912', 'MTR-000046', 2, '1777197326_3.png', 28, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `consumer_requests`
--

CREATE TABLE `consumer_requests` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consumer_requests`
--

INSERT INTO `consumer_requests` (`id`, `full_name`, `address`, `contact`, `email`, `status`, `created_at`, `category_id`) VALUES
(2, 'Jon Batongbakal', 'Bataan', '12345678912', 'eglosa24@bpsu.edu.ph', 'approved', '2026-04-24 15:52:38', NULL),
(3, 'Miss na kita', 'Bataan', '12345678912', 'eglosa24@bpsu.edu.ph', 'rejected', '2026-04-25 12:41:38', NULL),
(4, 'Miss na kita', 'Manila', '12345678912', 'eljaygenegalosa@gmail.com', 'approved', '2026-04-25 12:42:15', NULL),
(5, 'sample', 'Bataan', '12345678912', 'eglosa24@bpsu.edu.ph', 'approved', '2026-04-25 17:44:20', NULL),
(6, 'Eljay Losa', 'Bataan', '12345678912', 'eljaygenegalosa@gmail.com', 'approved', '2026-04-26 04:31:06', 1),
(7, 'Eljay Losa', 'Bataan', '12345678912', 'eljaygenegalosa@gmail.com', 'approved', '2026-04-26 05:05:25', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `sender_id`, `receiver_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 3, 1, 'Payment has been made for Bill ID: 33', 'payment', 1, '2026-04-21 13:41:57'),
(2, 1, 3, 'New bill generated. Amount: ₱18.00', 'bill', 1, '2026-04-21 13:46:58'),
(3, 1, 1, 'New bill generated. Amount: ₱36.00', 'bill', 1, '2026-04-21 15:08:41'),
(4, 1, 1, 'Payment has been made for Bill ID: 27', 'payment', 1, '2026-04-21 15:09:12'),
(5, 1, 1, 'New bill generated. Amount: ₱18.00', 'bill', 1, '2026-04-21 15:55:43'),
(6, 1, 1, 'New bill generated. Amount: ₱18.00', 'bill', 1, '2026-04-21 15:57:20'),
(7, 1, 1, 'New bill generated. Amount: ₱1,080.00', 'bill', 1, '2026-04-21 15:57:52'),
(8, 1, 1, 'New bill generated. Amount: ₱90.00', 'bill', 1, '2026-04-21 16:11:24'),
(9, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 17:51:41'),
(10, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 18:53:40'),
(11, 1, 1, 'Payment has been made for Bill ID: 52', 'payment', 1, '2026-04-21 18:53:58'),
(12, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 20:05:44'),
(13, 1, 1, 'New bill generated. Amount: ₱54.00', 'bill', 1, '2026-04-21 22:28:48'),
(14, 1, 1, 'Payment has been made for Bill ID: 53', 'payment', 1, '2026-04-21 23:06:15'),
(15, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 23:06:58'),
(16, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 23:15:09'),
(17, 1, 1, 'New bill generated. Amount: ₱39.00', 'bill', 1, '2026-04-21 23:16:52'),
(18, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 23:23:47'),
(19, 1, 1, 'Payment has been made for Bill ID: 55', 'payment', 1, '2026-04-21 23:24:18'),
(20, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 23:27:20'),
(21, 1, 1, 'Payment has been made for Bill ID: 56', 'payment', 1, '2026-04-21 23:27:47'),
(22, 1, 1, 'Payment has been made for Bill ID: 57', 'payment', 1, '2026-04-21 23:27:50'),
(23, 1, 1, 'New bill generated! Amount: ₱13.00', 'bill', 1, '2026-04-21 23:30:39'),
(24, 1, 1, '???? New bill generated! Amount: ₱13.00', 'bill', 1, '2026-04-21 23:38:54'),
(25, 1, 1, 'Payment has been made for Bill ID: 58', 'payment', 1, '2026-04-21 23:40:03'),
(26, 1, 1, '???? New bill generated! Amount: ₱26.00', 'bill', 1, '2026-04-21 23:40:35'),
(27, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 23:41:20'),
(28, 1, 1, 'Payment has been made for Bill ID: 59', 'payment', 1, '2026-04-21 23:41:43'),
(29, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-21 23:48:16'),
(30, 1, 1, 'Payment has been made for Bill ID: 60', 'payment', 1, '2026-04-21 23:48:47'),
(31, 1, 1, 'Payment has been made for Bill ID: 61', 'payment', 1, '2026-04-21 23:48:50'),
(32, 1, 1, 'Payment has been made for Bill ID: 62', 'payment', 1, '2026-04-21 23:48:53'),
(33, 1, 1, 'Payment has been made for Bill ID: 63', 'payment', 1, '2026-04-21 23:48:57'),
(34, 1, 1, 'New bill generated. Amount: ₱117.00', 'bill', 1, '2026-04-22 20:56:29'),
(35, 1, 1, 'Payment has been made for Bill ID: 54', 'payment', 1, '2026-04-22 21:07:34'),
(36, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-22 22:36:14'),
(37, 1, 1, 'New bill generated. Amount: ₱13.00', 'bill', 1, '2026-04-22 22:36:52'),
(38, 1, 1, 'New bill generated. Amount: ₱18.00', 'bill', 1, '2026-04-22 22:45:06'),
(39, 1, 1, 'New bill generated. Amount: ₱13.00', 'billing', 1, '2026-04-23 22:35:13'),
(40, 1, 9, 'New bill generated. Amount: ₱13.00', 'billing', 1, '2026-04-23 23:14:59'),
(41, 1, 10, 'New bill generated. Amount: ₱36.00', 'billing', 1, '2026-04-24 20:03:02'),
(42, 1, 9, 'New bill generated. Amount: ₱13.00', 'billing', 1, '2026-04-24 20:37:13'),
(43, 9, 1, 'Payment has been made for Bill ID: 77', 'payment', 1, '2026-04-24 20:48:32'),
(44, 9, 1, 'Payment has been made for Bill ID: 79', 'payment', 1, '2026-04-24 20:50:18'),
(45, 1, 17, 'New bill generated. Amount: ₱18.00', 'billing', 1, '2026-04-24 23:48:28'),
(46, 17, 1, 'Payment has been made for Bill ID: 80', 'payment', 1, '2026-04-24 23:48:40'),
(47, 1, 20, 'New bill generated. Amount: ₱36.00', 'billing', 1, '2026-04-25 21:13:53'),
(48, 20, 1, 'Payment has been made for Bill ID: 81', 'payment', 1, '2026-04-25 21:15:11'),
(49, 1, 19, 'New bill generated. Amount: ₱72.00', 'billing', 0, '2026-04-25 21:16:27'),
(50, 19, 1, 'Payment has been made for Bill ID: 82', 'payment', 1, '2026-04-25 21:16:47'),
(51, 1, 20, 'New bill generated. Amount: ₱792.00', 'billing', 1, '2026-04-25 21:42:37'),
(52, 1, 26, 'New bill generated. Amount: ₱18.00', 'billing', 1, '2026-04-26 02:52:28'),
(53, 1, 26, 'New bill generated. Amount: ₱18.00', 'billing', 1, '2026-04-26 02:52:49'),
(54, 26, 1, 'Payment has been made for Bill ID: 84', 'payment', 1, '2026-04-26 03:36:30'),
(55, 26, 1, 'Payment has been made for Bill ID: 85', 'payment', 1, '2026-04-26 03:37:24'),
(56, 1, 26, 'New bill generated. Amount: ₱18.00', 'billing', 1, '2026-04-26 03:42:24'),
(57, 1, 26, 'New bill generated. Amount: ₱882.00', 'billing', 1, '2026-04-26 11:41:16'),
(58, 26, 1, 'Payment has been made for Bill ID: 87', 'payment', 1, '2026-04-26 11:44:26'),
(59, 1, 26, 'New bill generated. Amount: ₱72.00', 'billing', 1, '2026-04-26 12:08:18'),
(60, 26, 1, 'Payment has been made for Bill ID: 86', 'payment', 1, '2026-04-26 12:08:44'),
(61, 1, 26, 'New bill generated. Amount: ₱54.00', 'billing', 1, '2026-04-26 13:03:44'),
(62, 26, 1, 'Payment has been made for Bill ID: 89', 'payment', 1, '2026-04-26 13:04:25'),
(63, 1, 28, 'New bill generated. Amount: ₱54.00', 'billing', 1, '2026-04-26 13:06:09'),
(64, 28, 1, 'Payment has been made for Bill ID: 90', 'payment', 1, '2026-04-26 13:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `bill_id`, `amount_paid`, `payment_date`) VALUES
(35, 84, 18.00, '2026-04-26'),
(36, 85, 18.00, '2026-04-25'),
(37, 87, 882.00, '2026-04-26'),
(38, 86, 18.00, '2026-04-26'),
(39, 89, 54.00, '2026-04-26'),
(40, 90, 54.00, '2026-04-26');

-- --------------------------------------------------------

--
-- Table structure for table `readings`
--

CREATE TABLE `readings` (
  `id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `prev_reading` decimal(10,2) NOT NULL,
  `curr_reading` decimal(10,2) NOT NULL,
  `reading_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `readings`
--

INSERT INTO `readings` (`id`, `consumer_id`, `prev_reading`, `curr_reading`, `reading_date`) VALUES
(84, 45, 100.00, 200.00, '2026-04-25'),
(85, 45, 100.00, 200.00, '2026-04-25'),
(86, 45, 300.00, 400.00, '2026-04-25'),
(87, 45, 100.00, 5000.00, '2026-04-26'),
(88, 45, 200.00, 600.00, '2026-04-01'),
(89, 45, 100.00, 400.00, '2026-04-26'),
(90, 47, 100.00, 400.00, '2026-04-26');

-- --------------------------------------------------------

--
-- Table structure for table `signup_requests`
--

CREATE TABLE `signup_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','customer') NOT NULL,
  `consumer_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_otp` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `consumer_id`, `email`, `reset_token`, `reset_expiry`, `reset_otp`, `otp_expiry`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'sample', '$2y$10$e37zAsrFv/MsPpvWwpEgEe2E1bZuPz9cdMu91lInIFW2uWKI8ljPW', 'customer', NULL, 'eglosa24@bpsu.edu.ph', NULL, NULL, NULL, NULL),
(28, 'jay', '$2y$10$.X.xDMM1X.riXz1R2r.TQOP3tUvp/4iHnL6d73qyeqQYCqsciFeG.', 'customer', NULL, 'eljaygenegalosa@gmail.com', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reading_id` (`reading_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `consumers`
--
ALTER TABLE `consumers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meter_no` (`meter_no`),
  ADD UNIQUE KEY `meter_no_2` (`meter_no`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_consumers_users` (`user_id`);

--
-- Indexes for table `consumer_requests`
--
ALTER TABLE `consumer_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `readings`
--
ALTER TABLE `readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- Indexes for table `signup_requests`
--
ALTER TABLE `signup_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `consumers`
--
ALTER TABLE `consumers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `consumer_requests`
--
ALTER TABLE `consumer_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `readings`
--
ALTER TABLE `readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `signup_requests`
--
ALTER TABLE `signup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`reading_id`) REFERENCES `readings` (`id`);

--
-- Constraints for table `consumers`
--
ALTER TABLE `consumers`
  ADD CONSTRAINT `consumers_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_consumers_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`);

--
-- Constraints for table `readings`
--
ALTER TABLE `readings`
  ADD CONSTRAINT `readings_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `consumers` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `consumers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

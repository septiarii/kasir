-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 12, 2026 at 07:04 AM
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
-- Database: `kasir_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(8, 'aksesoris', 'aksesoris HP', '2026-08-31 03:38:52'),
(9, 'headset', 'headset kabel', '2026-08-31 03:39:49'),
(10, 'charger', 'charger HP', '2026-08-31 03:40:50');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `barcode` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `price`, `stock`, `barcode`, `created_at`, `updated_at`) VALUES
(8, 'OPPO', 10, 20000.00, 4, 'BAR2026083105415640', '2026-08-31 03:41:56', '2026-09-08 01:59:58'),
(9, 'Ugreen', 10, 50000.00, 6, 'BAR2026083105422210', '2026-08-31 03:42:22', '2026-09-12 01:02:40'),
(10, 'SAMSUNG', 10, 80000.00, 2, 'BAR2026083105424552', '2026-08-31 03:42:45', '2026-09-08 01:58:39'),
(11, 'OPPO', 9, 25000.00, 3, 'BAR2026083105431995', '2026-08-31 03:43:19', '2026-09-08 02:00:21'),
(12, 'Knowledge Zenith', 9, 25000.00, 7, 'BAR2026083105434459', '2026-08-31 03:43:44', '2026-09-11 02:31:22'),
(13, 'Baseus', 8, 30000.00, 6, 'BAR2026083105450227', '2026-08-31 03:45:02', '2026-09-08 02:16:48'),
(14, 'Ugreen', 8, 30000.00, 7, 'BAR2026083105452514', '2026-08-31 03:45:25', '2026-09-06 00:59:51'),
(15, 'Selikon OPPO', 8, 20000.00, 9, 'BAR2026083105455197', '2026-08-31 03:45:51', '2026-09-08 02:02:52');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `group`, `description`, `created_at`, `updated_at`) VALUES
(1, 'shop_name', 'Hermas Cell', 'shop', 'Nama toko', '2026-08-24 12:33:56', '2026-08-28 02:48:15'),
(2, 'shop_address', 'Gunung Malang, Gereneng Timur', 'shop', 'Alamat toko', '2026-08-24 12:33:56', '2026-08-28 02:57:08'),
(3, 'shop_phone', '0812-3456-7890', 'shop', 'Nomor telepon toko', '2026-08-24 12:33:56', '2026-08-24 12:33:56'),
(4, 'shop_email', 'hermascell@gmail.com', 'shop', 'Email toko', '2026-08-24 12:33:56', '2026-08-24 12:33:56'),
(5, 'app_name', 'Hermas cell', 'system', 'Nama aplikasi', '2026-08-24 12:33:56', '2026-08-28 03:06:10'),
(6, 'app_footer', '2026 Hermas POS | v1.0', 'system', 'Footer aplikasi', '2026-08-24 12:33:56', '2026-08-28 03:06:37'),
(7, 'currency', 'Rp', 'system', 'Mata uang', '2026-08-24 12:33:56', '2026-08-24 12:33:56'),
(8, 'tax_percent', '0', 'system', 'Pajak dalam persen', '2026-08-24 12:33:56', '2026-08-24 12:33:56'),
(9, 'receipt_footer', 'Barang yang sudah dibeli tidak dapat dikembalikan', 'receipt', 'Footer struk', '2026-08-24 12:33:56', '2026-08-24 12:33:56'),
(10, 'backup_auto', '1', 'system', 'Auto backup (1=on, 0=off)', '2026-08-24 12:33:56', '2026-08-24 12:33:56');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `invoice_number` varchar(20) NOT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `customer_name`, `total_amount`, `payment_amount`, `change_amount`, `invoice_number`, `transaction_date`, `created_at`) VALUES
(29, 1, 'Umum', 10000.00, 50000.00, 40000.00, 'INV202608280001', '2026-08-28', '2026-08-28 02:02:49'),
(30, 1, 'Umum', 5000.00, 10000.00, 5000.00, 'INV202608280002', '2026-08-28', '2026-08-28 02:20:11'),
(31, 1, 'Umum', 5000.00, 5000.00, 0.00, 'INV202608280003', '2026-08-28', '2026-08-28 02:39:02'),
(32, 1, 'Umum', 5000.00, 10000.00, 5000.00, 'INV202608280004', '2026-08-28', '2026-08-28 03:00:20'),
(33, 1, 'Umum', 5000.00, 5000.00, 0.00, 'INV202608290001', '2026-08-29', '2026-08-29 12:01:11'),
(34, 1, 'Umum', 5000.00, 10000.00, 5000.00, 'INV202608290002', '2026-08-29', '2026-08-29 12:10:47'),
(35, 1, 'Umum', 15000.00, 20000.00, 5000.00, 'INV202608290003', '2026-08-29', '2026-08-29 12:10:56'),
(36, 1, 'Umum', 20000.00, 20000.00, 0.00, 'INV202608290004', '2026-08-29', '2026-08-29 12:11:11'),
(37, 1, 'Umum', 15000.00, 15000.00, 0.00, 'INV202608290005', '2026-08-29', '2026-08-29 12:11:25'),
(38, 1, 'Umum', 225000.00, 225000.00, 0.00, 'INV202609050001', '2026-09-05', '2026-09-05 13:43:09'),
(39, 1, 'Umum', 120000.00, 130000.00, 10000.00, 'INV202609050002', '2026-09-05', '2026-09-05 13:43:40'),
(40, 1, 'Umum', 200000.00, 200000.00, 0.00, 'INV202609050003', '2026-09-05', '2026-09-05 13:46:43'),
(41, 1, 'Umum', 90000.00, 100000.00, 10000.00, 'INV202609060001', '2026-09-06', '2026-09-06 00:59:51'),
(42, 1, 'Umum', 160000.00, 200000.00, 40000.00, 'INV202609060002', '2026-09-06', '2026-09-06 01:00:50'),
(43, 1, 'Umum', 30000.00, 30000.00, 0.00, 'INV202609070001', '2026-09-07', '2026-09-07 09:44:13'),
(44, 1, 'Umum', 30000.00, 30000.00, 0.00, 'INV202609070002', '2026-09-07', '2026-09-07 09:44:33'),
(45, 1, 'Umum', 80000.00, 100000.00, 20000.00, 'INV202609070003', '2026-09-07', '2026-09-07 15:13:44'),
(46, 1, 'Umum', 65000.00, 65000.00, 0.00, 'INV202609070004', '2026-09-07', '2026-09-07 15:16:16'),
(47, 1, 'Umum', 80000.00, 80000.00, 0.00, 'INV202609070005', '2026-09-07', '2026-09-07 15:17:29'),
(48, 1, 'Umum', 80000.00, 80000.00, 0.00, 'INV202609070006', '2026-09-07', '2026-09-07 15:17:57'),
(49, 1, 'Umum', 25000.00, 25000.00, 0.00, 'INV202609070007', '2026-09-07', '2026-09-07 15:18:18'),
(50, 1, 'Umum', 65000.00, 65000.00, 0.00, 'INV202609070008', '2026-09-07', '2026-09-07 15:19:23'),
(51, 1, 'Umum', 25000.00, 25000.00, 0.00, 'INV202609070009', '2026-09-07', '2026-09-07 15:23:20'),
(52, 1, 'Umum', 30000.00, 30000.00, 0.00, 'INV202609070010', '2026-09-07', '2026-09-07 15:23:52'),
(53, 1, 'Umum', 25000.00, 25000.00, 0.00, 'INV202609070011', '2026-09-07', '2026-09-07 15:24:14'),
(54, 1, 'Umum', 30000.00, 30000.00, 0.00, 'INV202609070012', '2026-09-07', '2026-09-07 15:27:48'),
(55, 1, 'Umum', 50000.00, 50000.00, 0.00, 'INV202609070013', '2026-09-07', '2026-09-07 15:30:58'),
(56, 1, 'Umum', 80000.00, 80000.00, 0.00, 'INV202609070014', '2026-09-07', '2026-09-07 15:34:11'),
(57, 1, 'Umum', 30000.00, 30000.00, 0.00, 'INV202609070015', '2026-09-07', '2026-09-07 15:36:48'),
(58, 1, 'Umum', 80000.00, 80000.00, 0.00, 'INV202609070016', '2026-09-07', '2026-09-07 15:39:17'),
(59, 1, 'Umum', 65000.00, 65000.00, 0.00, 'INV202609070017', '2026-09-07', '2026-09-07 15:45:47'),
(60, 1, 'Umum', 25000.00, 25000.00, 0.00, 'INV202609070018', '2026-09-07', '2026-09-07 15:52:58'),
(61, 1, 'Umum', 65000.00, 65000.00, 0.00, 'INV202609080001', '2026-09-08', '2026-09-08 01:39:20'),
(62, 1, 'Umum', 20000.00, 20000.00, 0.00, 'INV202609080002', '2026-09-08', '2026-09-08 02:02:52'),
(63, 1, 'Umum', 30000.00, 30000.00, 0.00, 'INV202609080003', '2026-09-08', '2026-09-08 02:07:13'),
(64, 1, 'Umum', 50000.00, 50000.00, 0.00, 'INV202609080004', '2026-09-08', '2026-09-08 02:16:04'),
(65, 1, 'Umum', 30000.00, 30000.00, 0.00, 'INV202609080005', '2026-09-08', '2026-09-08 02:16:48'),
(66, 1, 'Umum', 25000.00, 25000.00, 0.00, 'INV202609110001', '2026-09-11', '2026-09-11 02:31:22'),
(67, 1, 'Umum', 100000.00, 100000.00, 0.00, 'INV202609120001', '2026-09-12', '2026-09-12 01:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `transaction_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(45, 38, 10, 1, 80000.00, 80000.00),
(46, 38, 13, 1, 30000.00, 30000.00),
(47, 38, 9, 1, 50000.00, 50000.00),
(48, 38, 8, 1, 65000.00, 65000.00),
(49, 39, 15, 4, 30000.00, 120000.00),
(50, 40, 10, 1, 80000.00, 80000.00),
(51, 40, 11, 1, 25000.00, 25000.00),
(52, 40, 15, 1, 30000.00, 30000.00),
(53, 40, 8, 1, 65000.00, 65000.00),
(54, 41, 14, 3, 30000.00, 90000.00),
(55, 42, 11, 2, 25000.00, 50000.00),
(56, 42, 10, 1, 80000.00, 80000.00),
(57, 42, 15, 1, 30000.00, 30000.00),
(58, 43, 15, 1, 30000.00, 30000.00),
(59, 44, 13, 1, 30000.00, 30000.00),
(60, 45, 10, 1, 80000.00, 80000.00),
(61, 46, 8, 1, 65000.00, 65000.00),
(62, 47, 10, 1, 80000.00, 80000.00),
(63, 48, 10, 1, 80000.00, 80000.00),
(64, 49, 11, 1, 25000.00, 25000.00),
(65, 50, 8, 1, 65000.00, 65000.00),
(66, 51, 11, 1, 25000.00, 25000.00),
(67, 52, 15, 1, 30000.00, 30000.00),
(68, 53, 11, 1, 25000.00, 25000.00),
(69, 54, 15, 1, 30000.00, 30000.00),
(70, 55, 9, 1, 50000.00, 50000.00),
(71, 56, 10, 1, 80000.00, 80000.00),
(72, 57, 15, 1, 30000.00, 30000.00),
(73, 58, 10, 1, 80000.00, 80000.00),
(74, 59, 8, 1, 65000.00, 65000.00),
(75, 60, 11, 1, 25000.00, 25000.00),
(76, 61, 8, 1, 65000.00, 65000.00),
(77, 62, 15, 1, 20000.00, 20000.00),
(78, 63, 13, 1, 30000.00, 30000.00),
(79, 64, 12, 2, 25000.00, 50000.00),
(80, 65, 13, 1, 30000.00, 30000.00),
(81, 66, 12, 1, 25000.00, 25000.00),
(82, 67, 9, 2, 50000.00, 100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('admin','kasir','owner') DEFAULT 'kasir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `created_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'Administrator', 'admin', '2026-08-28 02:28:41'),
(6, 'pemilik', '59e9eb8bcdc5d14ebb629d41e0613ffa', 'ari', 'owner', '2026-08-28 02:28:41'),
(7, 'kasir', 'de28f8f7998f23ab4194b51a6029416f', 'ari', 'kasir', '2026-08-28 03:07:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

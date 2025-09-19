-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 21, 2025 at 05:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aquaguard_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ponds`
--

CREATE TABLE `ponds` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(120) DEFAULT 'Pond 1',
  `size_sqm` int(11) DEFAULT 100,
  `fish_type` varchar(80) DEFAULT 'Tilapia',
  `pond_type` varchar(80) DEFAULT 'Earthen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ponds`
--

INSERT INTO `ponds` (`id`, `user_id`, `name`, `size_sqm`, `fish_type`, `pond_type`) VALUES
(1, 1, 'Pond A', 120, 'Tilapia', 'Earthen');

-- --------------------------------------------------------

--
-- Table structure for table `readings`
--

CREATE TABLE `readings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pond_id` int(11) DEFAULT NULL,
  `recorded_at` datetime NOT NULL,
  `temperature_c` decimal(5,2) DEFAULT NULL,
  `turbidity_ntu` decimal(6,2) DEFAULT NULL,
  `ph_level` decimal(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `readings`
--

INSERT INTO `readings` (`id`, `user_id`, `pond_id`, `recorded_at`, `temperature_c`, `turbidity_ntu`, `ph_level`) VALUES
(1, 1, 1, '2025-08-19 16:00:00', 23.70, 33.50, 6.90),
(2, 1, 1, '2025-08-19 17:00:00', 23.34, 26.26, 7.15),
(3, 1, 1, '2025-08-19 18:00:00', 24.64, 28.85, 7.18),
(4, 1, 1, '2025-08-19 19:00:00', 26.34, 24.08, 7.08),
(5, 1, 1, '2025-08-19 20:00:00', 26.22, 24.36, 7.19),
(6, 1, 1, '2025-08-19 21:00:00', 27.65, 22.62, 7.17),
(7, 1, 1, '2025-08-19 22:00:00', 25.89, 21.60, 7.32),
(8, 1, 1, '2025-08-19 23:00:00', 25.05, 26.43, 7.35),
(9, 1, 1, '2025-08-20 00:00:00', 28.63, 20.95, 7.29),
(10, 1, 1, '2025-08-20 01:00:00', 27.73, 16.71, 7.38),
(11, 1, 1, '2025-08-20 02:00:00', 27.10, 20.84, 7.45),
(12, 1, 1, '2025-08-20 03:00:00', 25.64, 21.00, 7.36),
(13, 1, 1, '2025-08-20 04:00:00', 25.42, 11.24, 7.46),
(14, 1, 1, '2025-08-20 05:00:00', 23.68, 12.39, 7.33),
(15, 1, 1, '2025-08-20 06:00:00', 24.45, 11.19, 7.48),
(16, 1, 1, '2025-08-20 07:00:00', 23.49, 8.29, 7.35),
(17, 1, 1, '2025-08-20 08:00:00', 20.73, 6.71, 7.27),
(18, 1, 1, '2025-08-20 09:00:00', 19.42, 11.57, 7.27),
(19, 1, 1, '2025-08-20 10:00:00', 22.57, 13.20, 7.21),
(20, 1, 1, '2025-08-20 11:00:00', 19.30, 8.10, 7.22),
(21, 1, 1, '2025-08-20 12:00:00', 19.62, 8.28, 7.15),
(22, 1, 1, '2025-08-20 13:00:00', 23.32, 9.74, 7.19),
(23, 1, 1, '2025-08-20 14:00:00', 23.28, 9.85, 7.22),
(24, 1, 1, '2025-08-20 15:00:00', 22.78, 17.00, 7.01),
(25, 1, 1, '2025-08-20 16:00:00', 21.66, 13.16, 7.10),
(26, 1, 1, '2025-08-20 17:00:00', 23.10, 12.91, 7.10),
(27, 1, 1, '2025-08-20 18:00:00', 26.55, 19.40, 6.89),
(28, 1, 1, '2025-08-20 19:00:00', 26.65, 15.89, 6.87),
(29, 1, 1, '2025-08-20 20:00:00', 26.67, 20.14, 6.93),
(30, 1, 1, '2025-08-20 21:00:00', 27.77, 19.71, 6.85),
(31, 1, 1, '2025-08-20 22:00:00', 28.21, 25.54, 6.85),
(32, 1, 1, '2025-08-20 23:00:00', 26.08, 21.99, 6.71),
(33, 1, 1, '2025-08-21 00:00:00', 27.87, 27.12, 6.60),
(34, 1, 1, '2025-08-21 01:00:00', 26.07, 24.49, 6.71),
(35, 1, 1, '2025-08-21 02:00:00', 28.10, 30.06, 6.65),
(36, 1, 1, '2025-08-21 03:00:00', 24.57, 27.61, 6.71),
(37, 1, 1, '2025-08-21 04:00:00', 25.24, 25.80, 6.59),
(38, 1, 1, '2025-08-21 05:00:00', 26.52, 27.03, 6.57),
(39, 1, 1, '2025-08-21 06:00:00', 25.67, 33.69, 6.70),
(40, 1, 1, '2025-08-21 07:00:00', 22.64, 27.57, 6.64),
(41, 1, 1, '2025-08-21 08:00:00', 21.77, 24.57, 6.58),
(42, 1, 1, '2025-08-21 09:00:00', 23.00, 25.22, 6.58),
(43, 1, 1, '2025-08-21 10:00:00', 21.96, 27.04, 6.61),
(44, 1, 1, '2025-08-21 11:00:00', 21.69, 24.44, 6.58),
(45, 1, 1, '2025-08-21 12:00:00', 21.70, 26.57, 6.82),
(46, 1, 1, '2025-08-21 13:00:00', 19.80, 22.87, 6.67),
(47, 1, 1, '2025-08-21 14:00:00', 20.87, 18.86, 6.76),
(48, 1, 1, '2025-08-21 15:00:00', 21.21, 15.51, 6.82),
(49, 1, 1, '2025-08-21 16:00:00', 21.09, 16.24, 6.94),
(50, 1, 1, '2025-08-21 17:00:00', 21.07, 18.12, 6.87),
(51, 1, 1, '2025-08-21 18:00:00', 24.20, 16.99, 6.93),
(52, 1, 1, '2025-08-21 19:00:00', 24.45, 10.38, 7.13),
(53, 1, 1, '2025-08-21 20:00:00', 24.66, 15.84, 7.09),
(54, 1, 1, '2025-08-21 21:00:00', 25.99, 11.70, 7.18),
(55, 1, 1, '2025-08-21 22:00:00', 28.41, 12.19, 7.28),
(56, 1, 1, '2025-08-21 23:00:00', 27.08, 7.63, 7.17),
(57, 1, 1, '2025-08-22 00:00:00', 26.97, 8.54, 7.23),
(58, 1, 1, '2025-08-22 01:00:00', 25.88, 14.73, 7.25),
(59, 1, 1, '2025-08-22 02:00:00', 28.10, 6.99, 7.41),
(60, 1, 1, '2025-08-22 03:00:00', 26.05, 14.12, 7.45),
(61, 1, 1, '2025-08-22 04:00:00', 27.55, 7.71, 7.47),
(62, 1, 1, '2025-08-22 05:00:00', 26.23, 13.43, 7.43),
(63, 1, 1, '2025-08-22 06:00:00', 26.12, 18.25, 7.47),
(64, 1, 1, '2025-08-22 07:00:00', 22.07, 15.44, 7.35),
(65, 1, 1, '2025-08-22 08:00:00', 21.64, 12.37, 7.40),
(66, 1, 1, '2025-08-22 09:00:00', 22.65, 22.98, 7.31),
(67, 1, 1, '2025-08-22 10:00:00', 20.16, 21.84, 7.45),
(68, 1, 1, '2025-08-22 11:00:00', 20.91, 26.70, 7.41),
(69, 1, 1, '2025-08-22 12:00:00', 19.82, 24.91, 7.37),
(70, 1, 1, '2025-08-22 13:00:00', 20.70, 20.83, 7.25),
(71, 1, 1, '2025-08-22 14:00:00', 20.87, 22.22, 7.18),
(72, 1, 1, '2025-08-22 15:00:00', 21.13, 22.63, 7.16);

-- --------------------------------------------------------

--
-- Table structure for table `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recommendations`
--

INSERT INTO `recommendations` (`id`, `user_id`, `title`, `body`, `created_at`) VALUES
(1, 1, 'Prescriptive Suggestions', 'Maintain temperature between 24–30°C. If turbidity exceeds 40 NTU, increase filtration. Keep pH in the 6.5–8 range. Schedule checks every 3 hours during hot days.', '2025-08-21 14:34:28');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reading_frequency` enum('1m','15m','1h','1d') DEFAULT '15m',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `user_id`, `reading_frequency`, `updated_at`) VALUES
(1, 1, '1h', '2025-08-21 14:34:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(120) DEFAULT 'Client',
  `role` varchar(50) DEFAULT 'Owner',
  `phone` varchar(30) DEFAULT NULL,
  `farm_name` varchar(120) DEFAULT 'Taste From The Sea',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `name`, `role`, `phone`, `farm_name`, `created_at`) VALUES
(1, 'demo@example.com', '$2y$10$q1d/cpeP2phueZ.H9UKcQOA15PvwKE4kKfAXCmuFBXmqsbV.tXHoi', 'Xhyllah Serrano', 'Owner', '+63 9323481924', 'Taste From The Sea', '2025-08-21 14:34:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ponds`
--
ALTER TABLE `ponds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `readings`
--
ALTER TABLE `readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pond_id` (`pond_id`),
  ADD KEY `user_id` (`user_id`,`recorded_at`);

--
-- Indexes for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ponds`
--
ALTER TABLE `ponds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `readings`
--
ALTER TABLE `readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ponds`
--
ALTER TABLE `ponds`
  ADD CONSTRAINT `ponds_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `readings`
--
ALTER TABLE `readings`
  ADD CONSTRAINT `readings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `readings_ibfk_2` FOREIGN KEY (`pond_id`) REFERENCES `ponds` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD CONSTRAINT `recommendations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

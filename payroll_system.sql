-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2026 at 04:40 AM
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
-- Database: `payroll_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `is_hr` tinyint(1) DEFAULT 0 COMMENT '1 = HR privileges',
  `is_admin` tinyint(1) DEFAULT 0 COMMENT '1 = Admin privileges',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `firstname`, `lastname`, `email`, `password`, `phone`, `role_id`, `department_id`, `shift_id`, `hire_date`, `status`, `is_hr`, `is_admin`, `created_at`) VALUES
(1, 'EMP001', 'Admin', 'User', 'admin@company.com', 'admin123', '', 1, 1, 2, '2024-01-01', 'active', 1, 1, '2026-05-13 01:40:24'),
(2, 'EMP849', 'Jo-ann', 'blanco', 'jopaydelacruzblanco@gmail.com', 'joann', '123456789', 3, 3, 1, '2026-07-21', 'active', 0, 0, '2026-07-21 02:23:28'),
(3, 'EMP474', 'Anna Tricia', 'Sagadal', 'shankianeash052617@gmail.com', 'tricia123', '123456666', 1, 1, 1, '2026-07-21', 'active', 0, 0, '2026-07-21 02:24:16'),
(4, 'EMP273', 'Angele Grace', 'Ramirez', 'angelgracee.ramirez@gmail.com', 'grace123', '98765432111', 2, 5, 1, '2026-07-21', 'active', 0, 0, '2026-07-21 02:24:51'),
(5, 'EMP733', 'Christian Roy', 'Bejerano', 'cbejerano14@gmail.com', 'chan123', '123456788568', 5, 2, 1, '2026-07-21', 'active', 0, 0, '2026-07-21 02:25:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------
--
-- Table structure for table `employee_smtp_accounts`
--

CREATE TABLE `employee_smtp_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 465,
  `smtp_encryption` varchar(20) NOT NULL DEFAULT 'ssl',
  `smtp_username` varchar(255) NOT NULL,
  `smtp_password` varchar(255) NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `fk_employee_smtp_accounts_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

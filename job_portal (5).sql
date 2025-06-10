-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2025 at 11:49 AM
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
-- Database: `job_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `full_name`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$YOUR_HASHED_PASSWORD', 'Admin User', 'admin@example.com', '2025-06-09 08:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_details`
--

CREATE TABLE `applicant_details` (
  `seeker_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cv_path` varchar(255) DEFAULT NULL COMMENT 'PDF format, max size 2MB',
  `certificates_path` varchar(255) DEFAULT NULL COMMENT 'PDF/ZIP format, max size 5MB',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `cover_letter` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_details`
--

INSERT INTO `applicant_details` (`seeker_id`, `name`, `phone_number`, `email`, `cv_path`, `certificates_path`, `status`, `created_at`, `updated_at`, `last_login`, `cover_letter`) VALUES
(6, '', '', '', 'uploads/cvs/cv_6_1749544874.pdf', NULL, 'active', '2025-06-10 08:41:14', '2025-06-10 08:41:14', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `seeker_id` int(11) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Submitted','Under Review','Shortlisted','Rejected','Hired') DEFAULT 'Submitted',
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `seeker_id`, `application_date`, `status`, `admin_notes`) VALUES
(1, 48, 6, '2025-06-10 08:41:16', 'Submitted', NULL),
(2, 50, 6, '2025-06-10 08:44:40', 'Submitted', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `job_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `location` varchar(100) NOT NULL,
  `job_type` enum('Full-time','Part-time','Contract','Temporary') NOT NULL,
  `shift_schedule` varchar(100) DEFAULT NULL,
  `salary` varchar(50) DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `posted_by` int(11) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `job_categories` enum('Marketing','Sales','Education','Development','Tally Experts','Other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`job_id`, `title`, `description`, `requirements`, `location`, `job_type`, `shift_schedule`, `salary`, `benefits`, `is_active`, `posted_by`, `updated_at`, `job_categories`, `created_at`) VALUES
(48, 'hello', 'khi', 'abc', 'mumbai', 'Full-time', '10-7', '50000', '', 1, 1, '2025-06-09 10:12:56', 'Marketing', '2025-06-09 10:12:56'),
(49, 'data analysis', 'accha aadmi ho', 'communication', 'mumbai', 'Part-time', '10-7', '50000', 'health insurance', 0, 1, '2025-06-10 08:43:12', 'Other', '2025-06-10 08:43:05'),
(50, 'abc', 'blabla', 'mdo', 'mumbai', 'Full-time', '10-7', '100000', '', 1, 1, '2025-06-10 08:43:56', 'Development', '2025-06-10 08:43:56');

-- --------------------------------------------------------

--
-- Table structure for table `job_seekers`
--

CREATE TABLE `job_seekers` (
  `seeker_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_seekers`
--

INSERT INTO `job_seekers` (`seeker_id`, `username`, `password`, `email`, `full_name`, `phone`, `city`, `status`, `created_at`, `updated_at`) VALUES
(4, 'manasa', '$2y$10$EWHphZhdQTxPnA6cDYDU/OszUBQfy278uwH87O/o4NYSn7ZWdIso2', 'manasa.p8912@gmail.com', 'Manasa', '098195 46622', NULL, 'active', '2025-06-05 10:25:23', '2025-06-05 10:25:23'),
(5, 'sdks', '$2y$10$jZ.3rXKfbKrlHBUdglIx/uxI4nyha7Nm30.GGVcAZ/NWDtBuZLD0S', 'maheshshedge4@gmail.com', 'Manasa', '9812', NULL, 'active', '2025-06-05 11:59:14', '2025-06-05 11:59:14'),
(6, 'm1', '$2y$10$rx8Kw15uHk/wesM8cHX8jejd27R9nCGuSE4WQqpOFAyX8xACEXkqS', 'a@gmail.com', 'a', '3800', NULL, 'active', '2025-06-05 12:02:40', '2025-06-05 12:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `applicant_details`
--
ALTER TABLE `applicant_details`
  ADD PRIMARY KEY (`seeker_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `seeker_id` (`seeker_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `job_seekers`
--
ALTER TABLE `job_seekers`
  ADD PRIMARY KEY (`seeker_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `applicant_details`
--
ALTER TABLE `applicant_details`
  MODIFY `seeker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `job_seekers`
--
ALTER TABLE `job_seekers`
  MODIFY `seeker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`seeker_id`) REFERENCES `job_seekers` (`seeker_id`);

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`posted_by`) REFERENCES `admins` (`admin_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

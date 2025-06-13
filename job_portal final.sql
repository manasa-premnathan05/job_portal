-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2025 at 01:04 PM
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
  `cv_path` varchar(255) DEFAULT NULL COMMENT 'PDF format, max size 2MB',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `cover_letters` text DEFAULT NULL COMMENT 'JSON array of cover letters with job IDs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_details`
--

INSERT INTO `applicant_details` (`seeker_id`, `cv_path`, `status`, `created_at`, `updated_at`, `last_login`, `cover_letter`, `applied_at`, `cover_letters`) VALUES
(6, 'uploads/cvs/cv_6_1749801205.pdf', 'active', '2025-06-13 07:53:25', '2025-06-13 07:54:33', NULL, 'Manasa\r\n[Your Location]\r\n[Your Email] | [Your Phone Number]\r\n\r\nDate: June 13, 2025\r\n\r\nTo,\r\nThe Hiring Team\r\n[Company Name]\r\n\r\nSubject: Application for Software Developer Position\r\n\r\nDear Sir/Madam,\r\n\r\nI am Manasa, and I am applying for the Software Developer position at your company. I have experience in coding with languages like Java, Python, and PHP, and I enjoy building web applications and solving technical problems.\r\n\r\nI am passionate about software development and eager to grow my skills while contributing to your team. Thank you for considering my application.\r\n\r\nSincerely,\r\nManasa', NULL, NULL),
(7, 'uploads/cvs/cv_7_1749801093.pdf', 'active', '2025-06-13 07:51:33', '2025-06-13 07:52:34', NULL, 'Maya [Last Name]\r\nKamothe, Navi Mumbai\r\nEmail: [your.email@example.com]\r\nPhone: [your contact number]\r\n\r\nDate: June 13, 2025\r\n\r\nTo,\r\nThe Hiring Manager\r\nSpectra\r\n\r\nSubject: Application for Tally Expert Position\r\n\r\nDear Hiring Manager,\r\n\r\nI am writing to express my interest in the Tally Expert position at Spectra. My name is Maya, and I am based in Kamothe. With a strong background in accounting and hands-on expertise in Tally ERP software, I am confident in my ability to contribute effectively to your finance and operations team.\r\n\r\nOver the past few years, I have worked on various aspects of accounting, including GST filing, inventory management, and financial reporting, all using Tally. My attention to detail and understanding of accounting principles have helped me maintain accurate records and streamline processes for previous employers. I am also comfortable working with Excel and other business tools that complement Tally.\r\n\r\nWhat draws me to Spectra is your company\'s reputation for innovation and growth. I am excited about the opportunity to be part of a dynamic team where I can apply my skills and continue to grow professionally.\r\n\r\nI would appreciate the opportunity to discuss how my experience aligns with your needs. Thank you for considering my application.\r\n\r\nWarm regards,\r\nMaya [Last Name]\r\nKamothe, Navi Mumbai', NULL, NULL),
(9, 'uploads/cvs/cv_9_1749807653.pdf', 'active', '2025-06-13 09:40:53', '2025-06-13 09:42:45', NULL, 'interested for job', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `seeker_id` int(11) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `cover_letter` text DEFAULT NULL,
  `status` enum('Submitted','Under Review','Shortlisted','Rejected','Hired') DEFAULT 'Submitted',
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `seeker_id`, `application_date`, `cover_letter`, `status`, `admin_notes`) VALUES
(15, 53, 7, '2025-06-13 07:52:34', 'Maya [Last Name]\r\nKamothe, Navi Mumbai\r\nEmail: [your.email@example.com]\r\nPhone: [your contact number]\r\n\r\nDate: June 13, 2025\r\n\r\nTo,\r\nThe Hiring Manager\r\nSpectra\r\n\r\nSubject: Application for Tally Expert Position\r\n\r\nDear Hiring Manager,\r\n\r\nI am writing to express my interest in the Tally Expert position at Spectra. My name is Maya, and I am based in Kamothe. With a strong background in accounting and hands-on expertise in Tally ERP software, I am confident in my ability to contribute effectively to your finance and operations team.\r\n\r\nOver the past few years, I have worked on various aspects of accounting, including GST filing, inventory management, and financial reporting, all using Tally. My attention to detail and understanding of accounting principles have helped me maintain accurate records and streamline processes for previous employers. I am also comfortable working with Excel and other business tools that complement Tally.\r\n\r\nWhat draws me to Spectra is your company\'s reputation for innovation and growth. I am excited about the opportunity to be part of a dynamic team where I can apply my skills and continue to grow professionally.\r\n\r\nI would appreciate the opportunity to discuss how my experience aligns with your needs. Thank you for considering my application.\r\n\r\nWarm regards,\r\nMaya [Last Name]\r\nKamothe, Navi Mumbai', 'Rejected', NULL),
(16, 48, 7, '2025-06-13 07:52:52', 'Maya [Last Name]\r\nKamothe, Navi Mumbai\r\nEmail: [your.email@example.com]\r\nPhone: [your contact number]\r\n\r\nDate: June 13, 2025\r\n\r\nTo,\r\nThe Hiring Manager\r\nSpectra\r\n\r\nSubject: Application for Tally Expert Position\r\n\r\nDear Hiring Manager,\r\n\r\nI am writing to express my interest in the Tally Expert position at Spectra. My name is Maya, and I am based in Kamothe. With a strong background in accounting and hands-on expertise in Tally ERP software, I am confident in my ability to contribute effectively to your finance and operations team.\r\n\r\nOver the past few years, I have worked on various aspects of accounting, including GST filing, inventory management, and financial reporting, all using Tally. My attention to detail and understanding of accounting principles have helped me maintain accurate records and streamline processes for previous employers. I am also comfortable working with Excel and other business tools that complement Tally.\r\n\r\nWhat draws me to Spectra is your company\'s reputation for innovation and growth. I am excited about the opportunity to be part of a dynamic team where I can apply my skills and continue to grow professionally.\r\n\r\nI would appreciate the opportunity to discuss how my experience aligns with your needs. Thank you for considering my application.\r\n\r\nWarm regards,\r\nMaya [Last Name]\r\nKamothe, Navi Mumbai', 'Submitted', NULL),
(17, 48, 6, '2025-06-13 07:54:33', 'Manasa\r\n[Your Location]\r\n[Your Email] | [Your Phone Number]\r\n\r\nDate: June 13, 2025\r\n\r\nTo,\r\nThe Hiring Team\r\n[Company Name]\r\n\r\nSubject: Application for Software Developer Position\r\n\r\nDear Sir/Madam,\r\n\r\nI am Manasa, and I am applying for the Software Developer position at your company. I have experience in coding with languages like Java, Python, and PHP, and I enjoy building web applications and solving technical problems.\r\n\r\nI am passionate about software development and eager to grow my skills while contributing to your team. Thank you for considering my application.\r\n\r\nSincerely,\r\nManasa', 'Submitted', NULL),
(18, 53, 9, '2025-06-13 09:42:45', 'interested for job', 'Submitted', NULL);

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
(48, 'developer', 'make websites', 'abc', 'mumbai', 'Full-time', '10-7', '50000', 'basic package', 1, 1, '2025-06-12 09:54:16', 'Development', '2025-06-09 10:12:56'),
(51, 'tally expert', 'responsible', 'tally', 'mumbai', 'Full-time', '10-7', '50000', 'others', 1, 1, '2025-06-11 08:26:45', 'Tally Experts', '2025-06-11 08:26:45'),
(53, 'customer support', 'Customer support specialists assist customers with inquiries or concerns related to a company\'s products or services. In addition, they inform customers about specifications and features for an improved customer experience. They may also work with sales teams to ensure a smooth transition to ownership.', 'Communication skills ,Patience', 'mumbai', 'Full-time', '10-7', '', 'basic benefics pack', 1, 1, '2025-06-13 06:02:35', 'Other', '2025-06-13 06:02:35');

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
  `address` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_seekers`
--

INSERT INTO `job_seekers` (`seeker_id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(4, 'manasa', '$2y$10$EWHphZhdQTxPnA6cDYDU/OszUBQfy278uwH87O/o4NYSn7ZWdIso2', 'manasa.p8912@gmail.com', 'Manasa', '098195 46622', NULL, 'active', '2025-06-05 10:25:23', '2025-06-05 10:25:23'),
(5, 'sdks', '$2y$10$jZ.3rXKfbKrlHBUdglIx/uxI4nyha7Nm30.GGVcAZ/NWDtBuZLD0S', 'maheshshedge4@gmail.com', 'Manasa', '9812', NULL, 'active', '2025-06-05 11:59:14', '2025-06-05 11:59:14'),
(6, 'm1', '$2y$10$rx8Kw15uHk/wesM8cHX8jejd27R9nCGuSE4WQqpOFAyX8xACEXkqS', 'a@gmail.com', 'a', '3800', NULL, 'active', '2025-06-05 12:02:40', '2025-06-05 12:02:40'),
(7, 'g1', '$2y$10$iElrfY6M4ZxeLoSMTANSyeZAKXnoJqB4XLudTlvMnl1sMRDFXmI4a', 'g@gmail.com', 'gauri', '8077018661', 'jins,id,', 'active', '2025-06-11 06:22:46', '2025-06-11 06:22:46'),
(8, 'pooja', '$2y$10$4s9UYoY6WBxKmSgoDbbaVu3PeAER2YhmUiJ8c07e0AJYl9dGYqQEe', 'p@gmail.com', 'pooja', '9769503044', 'A-502,Shubh Nil Shivam Sector-11,plot 40 ,kamothe ', 'active', '2025-06-12 04:03:08', '2025-06-12 04:03:08'),
(9, 'maya', '$2y$10$7ocy82L3JSpviBvMEcDC/uweyhKjqSoleFKBVKY6kw6Tsh9jWuqfy', 'Maya@gmail.com', 'Maya', '098195 46622', 'A-502 ,Shubh Nil Shivam,Kamothe,410209', 'active', '2025-06-13 06:09:00', '2025-06-13 06:09:00'),
(11, 'amal_v', '$2y$10$dyCDS6lTOx7btg4rzlvA/uILOBM0/gQTwt0gou75RlpIDo1VMMiiG', 'amalv@gmail.com', 'Amal', '8077018661', 'A-502 ,Shubh Nil Shivam,Kamothe,410209', 'active', '2025-06-13 06:26:26', '2025-06-13 06:26:26');

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
  MODIFY `seeker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `job_seekers`
--
ALTER TABLE `job_seekers`
  MODIFY `seeker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant_details`
--
ALTER TABLE `applicant_details`
  ADD CONSTRAINT `fk_applicant_details_seeker` FOREIGN KEY (`seeker_id`) REFERENCES `job_seekers` (`seeker_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

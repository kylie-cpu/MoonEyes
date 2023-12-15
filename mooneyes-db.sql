-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 12, 2023 at 04:22 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `moon eyes`
--

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `agent_id` varchar(19) NOT NULL,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `badge_number` text NOT NULL,
  `modified_at` datetime NOT NULL,
  `modified_by` varchar(19) NOT NULL,
  `role` text NOT NULL,
  `email` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`agent_id`, `username`, `password`, `name`, `badge_number`, `modified_at`, `modified_by`, `role`, `email`) VALUES
('AGENT-0000000000001', 'Admin', '$2y$10$20cmJ7C3UFP/emeQ.Z4xR.tyv1Qc4fmLm/mXMka0HHwABQ3Lom37W', 'John Doe', '789789789', '2023-11-11 22:44:13', 'AGENT-653d5cac34453', 'admin', 'johndoe@aol.com');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `type` text NOT NULL,
  `agent` text NOT NULL,
  `form` text NOT NULL,
  `timestamp` datetime NOT NULL,
  `id` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `case_id` varchar(18) NOT NULL,
  `day_modified` datetime NOT NULL,
  `modified_by` varchar(19) NOT NULL,
  `title` text NOT NULL,
  `purpose` text NOT NULL,
  `status` text NOT NULL,
  `invoice` text NOT NULL,
  `notes` text NOT NULL,
  `ud1` text NOT NULL,
  `ud2` text NOT NULL,
  `ud3` text NOT NULL,
  `ud4` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_agent`
--

CREATE TABLE `case_agent` (
  `case_id` varchar(18) NOT NULL,
  `agent_id` varchar(19) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_client`
--

CREATE TABLE `case_client` (
  `case_id` varchar(18) NOT NULL,
  `client_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_subject`
--

CREATE TABLE `case_subject` (
  `case_id` varchar(18) NOT NULL,
  `subject_id` varchar(21) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` varchar(20) NOT NULL,
  `client_name` text NOT NULL,
  `email` text NOT NULL,
  `address` text NOT NULL,
  `phone_num` text NOT NULL,
  `lawyer` varchar(20) NOT NULL,
  `notes` text NOT NULL,
  `modified_by` varchar(19) NOT NULL,
  `day_modified` datetime NOT NULL,
  `ud1` text NOT NULL,
  `ud2` text NOT NULL,
  `ud3` text NOT NULL,
  `ud4` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `file_id` varchar(18) NOT NULL,
  `entity_id` varchar(25) NOT NULL,
  `fileName` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lawyers`
--

CREATE TABLE `lawyers` (
  `lawyer_id` varchar(20) NOT NULL,
  `lawyer_name` text NOT NULL,
  `lawyer_email` text NOT NULL,
  `lawyer_ph` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` varchar(21) NOT NULL,
  `subject_name` text NOT NULL,
  `address` text NOT NULL,
  `phone_nums` text NOT NULL,
  `associates` text NOT NULL,
  `vehicle_info` text NOT NULL,
  `place_of_work` text NOT NULL,
  `day_modified` datetime NOT NULL,
  `modified_by` varchar(19) NOT NULL,
  `notes` text NOT NULL,
  `lawyer` varchar(20) NOT NULL,
  `ud1` text NOT NULL,
  `ud2` text NOT NULL,
  `ud3` text NOT NULL,
  `ud4` text NOT NULL,
  `gps` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` varchar(17) NOT NULL,
  `name` text NOT NULL,
  `day_modified` datetime NOT NULL,
  `modified_by` varchar(19) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tag_assoc`
--

CREATE TABLE `tag_assoc` (
  `tag_id` varchar(17) NOT NULL,
  `assoc_id` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`agent_id`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`case_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`file_id`);

--
-- Indexes for table `lawyers`
--
ALTER TABLE `lawyers`
  ADD PRIMARY KEY (`lawyer_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tag_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

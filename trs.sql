-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2025 at 10:30 AM
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
-- Database: `trs`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `fullname`) VALUES
(1, 'trsadmin@smccnasipit.edu.ph', 'trsadmin', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `adviser`
--

CREATE TABLE `adviser` (
  `adviser_id` int(11) NOT NULL,
  `school_id` int(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adviser`
--

INSERT INTO `adviser` (`adviser_id`, `school_id`, `password`, `fullname`, `department`) VALUES
(29, 321, '123', 'REA MIE OMAS-AS', 'CCIS'),
(31, 123, '123', '123', '123');

-- --------------------------------------------------------

--
-- Table structure for table `departmentcourse`
--

CREATE TABLE `departmentcourse` (
  `id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departmentcourse`
--

INSERT INTO `departmentcourse` (`id`, `department`, `course`) VALUES
(1, 'CCIS', 'BS of Information Technology\'s'),
(2, 'CCIS', 'BS in Computer Science'),
(3, 'CTHM', 'Bachelor of Science in Tourism Management'),
(4, 'CTHM', 'Bachelor of Science in Hospitality Management'),
(5, 'CBM', 'BSBA - Financial Management'),
(6, 'CBM', 'BSBA - Human Resource Management'),
(7, 'CBM', 'BSBA - Marketing Management'),
(8, 'CTE', 'Bachelor of Elementary Education');

-- --------------------------------------------------------

--
-- Table structure for table `finaldocufinal_files`
--

CREATE TABLE `finaldocufinal_files` (
  `finaldocu_id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `finaldocu` varchar(255) DEFAULT NULL,
  `adviser_id` varchar(255) DEFAULT NULL,
  `panel1_id` varchar(50) DEFAULT NULL,
  `panel2_id` varchar(50) DEFAULT NULL,
  `panel3_id` varchar(50) DEFAULT NULL,
  `panel4_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `controlNo` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finaldocufinal_files`
--

INSERT INTO `finaldocufinal_files` (`finaldocu_id`, `student_id`, `finaldocu`, `adviser_id`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(13, '24', '../../../uploads/Certificate_of_Endorsement (23).pdf', '29', '20', '21', '22', '23', 'CCIS', '2025-04-29 10:19:58', 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `finaldocuproposal_files`
--

CREATE TABLE `finaldocuproposal_files` (
  `finaldocu_id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `finaldocu` varchar(255) DEFAULT NULL,
  `panel1_id` varchar(50) DEFAULT NULL,
  `panel2_id` varchar(50) DEFAULT NULL,
  `panel3_id` varchar(50) DEFAULT NULL,
  `panel4_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `adviser_id` int(255) NOT NULL,
  `controlNo` varchar(245) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finaldocuproposal_files`
--

INSERT INTO `finaldocuproposal_files` (`finaldocu_id`, `student_id`, `finaldocu`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(39, '24', '../../../uploads/file (36).pdf', '20', '21', '22', '23', 'CCIS', '2025-04-28 06:25:03', 29, 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `final_monitoring_form`
--

CREATE TABLE `final_monitoring_form` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `panel_name` varchar(255) NOT NULL,
  `date_submitted` date NOT NULL,
  `chapter` varchar(255) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `paragraph_number` int(11) DEFAULT NULL,
  `page_number` int(11) DEFAULT NULL,
  `date_released` date DEFAULT NULL,
  `docuRoute1` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adviser_id` int(11) NOT NULL,
  `adviser_name` varchar(255) NOT NULL,
  `route1_id` int(11) DEFAULT NULL,
  `route2_id` int(11) DEFAULT NULL,
  `docuRoute2` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `route3_id` int(255) NOT NULL,
  `docuRoute3` varchar(255) NOT NULL,
  `finaldocu_id` int(255) NOT NULL,
  `finaldocu` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_monitoring_form`
--

INSERT INTO `final_monitoring_form` (`id`, `panel_id`, `student_id`, `panel_name`, `date_submitted`, `chapter`, `feedback`, `paragraph_number`, `page_number`, `date_released`, `docuRoute1`, `created_at`, `adviser_id`, `adviser_name`, `route1_id`, `route2_id`, `docuRoute2`, `status`, `route3_id`, `docuRoute3`, `finaldocu_id`, `finaldocu`) VALUES
(34, 20, 24, 'Kenneth Barrera', '2025-04-29', '123', '123', 123, 123, '2025-04-29', '../../../uploads/curriculum kto12.pdf', '2025-04-29 08:13:51', 0, '', 14, NULL, NULL, 'Approved', 0, '', 0, ''),
(35, 0, 24, '', '2025-04-29', '321', '321', 321, 321, '2025-04-29', '../../../uploads/curriculum kto12.pdf', '2025-04-29 08:14:24', 29, 'REA MIE OMAS-AS', 14, NULL, NULL, 'Approved', 0, '', 0, ''),
(36, 21, 24, 'Apple', '2025-04-29', '123', '123', 123, 123, '2025-04-29', '../../../uploads/curriculum kto12.pdf', '2025-04-29 08:14:59', 0, '', 14, NULL, NULL, 'Approved', 0, '', 0, ''),
(37, 22, 24, 'Daisa O. Gupit', '2025-04-29', '123', '123', 123, 123, '2025-04-29', '../../../uploads/curriculum kto12.pdf', '2025-04-29 08:15:31', 0, '', 14, NULL, NULL, 'Approved', 0, '', 0, ''),
(38, 23, 24, 'Marlon Juhn Timogan', '2025-04-29', '123', '123', 123, 123, '2025-04-29', '../../../uploads/curriculum kto12.pdf', '2025-04-29 08:16:13', 0, '', 14, NULL, NULL, 'Approved', 0, '', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `panel`
--

CREATE TABLE `panel` (
  `panel_id` int(11) NOT NULL,
  `school_id` int(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panel`
--

INSERT INTO `panel` (`panel_id`, `school_id`, `password`, `fullname`, `department`, `position`) VALUES
(20, 123, '123', 'Kenneth Barrera', 'CCIS', 'panel1'),
(21, 1234, '123', 'Apple', 'CCIS', 'panel2'),
(22, 12345, '123', 'Daisa O. Gupit', 'CCIS', 'panel3'),
(23, 123456, '123', 'Marlon Juhn Timogan', 'CCIS', 'panel4'),
(28, 1231, '123', 'Kenneth Bars', 'Research Office', 'panel1');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_monitoring_form`
--

CREATE TABLE `proposal_monitoring_form` (
  `id` int(11) NOT NULL,
  `panel_id` varchar(50) DEFAULT NULL,
  `student_id` int(255) NOT NULL,
  `panel_name` varchar(100) DEFAULT NULL,
  `date_submitted` date DEFAULT NULL,
  `chapter` varchar(255) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `paragraph_number` int(11) DEFAULT NULL,
  `page_number` int(11) DEFAULT NULL,
  `date_released` date DEFAULT NULL,
  `docuRoute1` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adviser_id` int(255) NOT NULL,
  `adviser_name` varchar(255) NOT NULL,
  `route1_id` int(255) NOT NULL,
  `route2_id` int(255) NOT NULL,
  `docuRoute2` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `route3_id` int(255) NOT NULL,
  `docuRoute3` varchar(255) NOT NULL,
  `finaldocu_id` int(255) NOT NULL,
  `finaldocu` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal_monitoring_form`
--

INSERT INTO `proposal_monitoring_form` (`id`, `panel_id`, `student_id`, `panel_name`, `date_submitted`, `chapter`, `feedback`, `paragraph_number`, `page_number`, `date_released`, `docuRoute1`, `created_at`, `adviser_id`, `adviser_name`, `route1_id`, `route2_id`, `docuRoute2`, `status`, `route3_id`, `docuRoute3`, `finaldocu_id`, `finaldocu`) VALUES
(138, NULL, 24, NULL, '2025-04-28', '123', '123', 123, 123, '2025-04-28', '../../../uploads/file (36).pdf', '2025-04-28 03:48:33', 29, 'REA MIE OMAS-AS', 47, 0, '', 'Approved', 0, '', 0, ''),
(139, '20', 24, '123', '2025-04-28', '123', '123', 123, 123, '2025-04-28', '../../../uploads/file (36).pdf', '2025-04-28 03:49:48', 0, '', 47, 0, '', 'Approved', 0, '', 0, ''),
(140, '21', 24, 'Apple', '2025-04-28', '123', '123', 123, 123, '2025-04-28', '../../../uploads/file (36).pdf', '2025-04-28 03:50:14', 0, '', 47, 0, '', 'Approved', 0, '', 0, ''),
(141, '22', 24, 'Daisa O. Gupit', '2025-04-28', '123', '123', 123, 123, '2025-04-28', '../../../uploads/file (36).pdf', '2025-04-28 03:50:33', 0, '', 47, 0, '', 'Approved', 0, '', 0, ''),
(142, '23', 24, 'Marlon Juhn Timogan', '2025-04-28', '123', '123', 123, 123, '2025-04-28', '../../../uploads/file (36).pdf', '2025-04-28 03:51:05', 0, '', 47, 0, '', 'Approved', 0, '', 0, ''),
(143, NULL, 24, NULL, '2025-04-28', '123', '123', 123, 123, '2025-04-28', NULL, '2025-04-28 03:51:49', 29, 'REA MIE OMAS-AS', 0, 19, '../../../uploads/file (36).pdf', 'Approved', 0, '', 0, ''),
(144, NULL, 24, NULL, '2025-04-28', '123', '123', 123, 123, '2025-04-28', NULL, '2025-04-28 04:25:20', 29, 'REA MIE OMAS-AS', 0, 0, '', 'Approved', 17, '../../../uploads/file (36).pdf', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `route1final_files`
--

CREATE TABLE `route1final_files` (
  `route1_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `docuRoute1` varchar(255) NOT NULL,
  `panel1_id` int(11) NOT NULL,
  `panel2_id` int(11) NOT NULL,
  `panel3_id` int(11) NOT NULL,
  `panel4_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `date_submitted` date NOT NULL,
  `controlNo` varchar(244) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route1final_files`
--

INSERT INTO `route1final_files` (`route1_id`, `student_id`, `docuRoute1`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(14, 24, '../../../uploads/curriculum kto12.pdf', 20, 21, 22, 23, 29, 'CCIS', '2025-04-28', 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `route1proposal_files`
--

CREATE TABLE `route1proposal_files` (
  `route1_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `docuRoute1` varchar(255) NOT NULL,
  `panel1_id` int(255) NOT NULL,
  `panel2_id` int(255) NOT NULL,
  `panel3_id` int(255) NOT NULL,
  `panel4_id` int(255) NOT NULL,
  `adviser_id` int(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `controlNo` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route1proposal_files`
--

INSERT INTO `route1proposal_files` (`route1_id`, `student_id`, `docuRoute1`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(47, 24, '../../../uploads/file (36).pdf', 20, 21, 22, 23, 29, 'CCIS', '2025-04-28 05:49:23', 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `route2final_files`
--

CREATE TABLE `route2final_files` (
  `route2_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `docuRoute2` varchar(255) NOT NULL,
  `panel1_id` int(11) NOT NULL,
  `panel2_id` int(11) NOT NULL,
  `panel3_id` int(11) NOT NULL,
  `panel4_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `date_submitted` date NOT NULL,
  `controlNo` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route2final_files`
--

INSERT INTO `route2final_files` (`route2_id`, `student_id`, `docuRoute2`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(8, 24, '../../../uploads/Certificate_of_Endorsement (23).pdf', 20, 21, 22, 23, 29, 'CCIS', '2025-04-29', 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `route2proposal_files`
--

CREATE TABLE `route2proposal_files` (
  `route2_id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `docuRoute2` varchar(255) DEFAULT NULL,
  `panel1_id` varchar(50) DEFAULT NULL,
  `panel2_id` varchar(50) DEFAULT NULL,
  `panel3_id` varchar(50) DEFAULT NULL,
  `panel4_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `adviser_id` int(255) NOT NULL,
  `controlNo` varchar(222) NOT NULL,
  `fullname` varchar(244) NOT NULL,
  `group_number` int(244) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route2proposal_files`
--

INSERT INTO `route2proposal_files` (`route2_id`, `student_id`, `docuRoute2`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(23, '24', '../../../uploads/file (36).pdf', '20', '21', '22', '23', 'CCIS', '2025-04-28 06:23:40', 29, 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `route3final_files`
--

CREATE TABLE `route3final_files` (
  `route3_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `docuRoute3` varchar(255) NOT NULL,
  `panel1_id` int(11) NOT NULL,
  `panel2_id` int(11) NOT NULL,
  `panel3_id` int(11) NOT NULL,
  `panel4_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `date_submitted` date NOT NULL,
  `controlNo` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route3final_files`
--

INSERT INTO `route3final_files` (`route3_id`, `student_id`, `docuRoute3`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(11, 24, '../../../uploads/Certificate_of_Endorsement (21).pdf', 20, 21, 22, 23, 29, 'CCIS', '2025-04-29', 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `route3proposal_files`
--

CREATE TABLE `route3proposal_files` (
  `route3_id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `docuRoute3` varchar(255) DEFAULT NULL,
  `panel1_id` varchar(50) DEFAULT NULL,
  `panel2_id` varchar(50) DEFAULT NULL,
  `panel3_id` varchar(50) DEFAULT NULL,
  `panel4_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `adviser_id` int(255) NOT NULL,
  `controlNo` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route3proposal_files`
--

INSERT INTO `route3proposal_files` (`route3_id`, `student_id`, `docuRoute3`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`) VALUES
(17, '24', '../../../uploads/file (36).pdf', '20', '21', '22', '23', 'CCIS', '2025-04-28 06:09:01', 29, 'RBSIT124002', 'Ell jay Lacaran', 2, 'Thesis Routing System For Saint Michael College of Caraga');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `school_id` int(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `confirm_password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `school_year` int(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL,
  `adviser` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `group_members` text DEFAULT NULL,
  `controlNo` varchar(100) DEFAULT NULL,
  `title` varchar(215) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `school_id`, `password`, `confirm_password`, `fullname`, `school_year`, `department`, `course`, `adviser`, `group_number`, `group_members`, `controlNo`, `title`) VALUES
(24, 202251330, '123', '123', 'Ell jay Lacaran', 2024, 'CCIS', 'BSIT', 'Rea Mie Omas-as', 2, '[\"Jake Castillon\",\"Jenessa Ocay\",\"Rylvin Celnar Tiempo\",\"John Lester Saladores\"]', 'RBSIT124002', 'Thesis Routing System For Saint Michael College of Caraga');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `adviser`
--
ALTER TABLE `adviser`
  ADD PRIMARY KEY (`adviser_id`);

--
-- Indexes for table `departmentcourse`
--
ALTER TABLE `departmentcourse`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finaldocufinal_files`
--
ALTER TABLE `finaldocufinal_files`
  ADD PRIMARY KEY (`finaldocu_id`);

--
-- Indexes for table `finaldocuproposal_files`
--
ALTER TABLE `finaldocuproposal_files`
  ADD PRIMARY KEY (`finaldocu_id`);

--
-- Indexes for table `final_monitoring_form`
--
ALTER TABLE `final_monitoring_form`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `panel`
--
ALTER TABLE `panel`
  ADD PRIMARY KEY (`panel_id`);

--
-- Indexes for table `proposal_monitoring_form`
--
ALTER TABLE `proposal_monitoring_form`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `route1final_files`
--
ALTER TABLE `route1final_files`
  ADD PRIMARY KEY (`route1_id`);

--
-- Indexes for table `route1proposal_files`
--
ALTER TABLE `route1proposal_files`
  ADD PRIMARY KEY (`route1_id`);

--
-- Indexes for table `route2final_files`
--
ALTER TABLE `route2final_files`
  ADD PRIMARY KEY (`route2_id`);

--
-- Indexes for table `route2proposal_files`
--
ALTER TABLE `route2proposal_files`
  ADD PRIMARY KEY (`route2_id`);

--
-- Indexes for table `route3final_files`
--
ALTER TABLE `route3final_files`
  ADD PRIMARY KEY (`route3_id`);

--
-- Indexes for table `route3proposal_files`
--
ALTER TABLE `route3proposal_files`
  ADD PRIMARY KEY (`route3_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `adviser`
--
ALTER TABLE `adviser`
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `departmentcourse`
--
ALTER TABLE `departmentcourse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `finaldocufinal_files`
--
ALTER TABLE `finaldocufinal_files`
  MODIFY `finaldocu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `finaldocuproposal_files`
--
ALTER TABLE `finaldocuproposal_files`
  MODIFY `finaldocu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `final_monitoring_form`
--
ALTER TABLE `final_monitoring_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `panel`
--
ALTER TABLE `panel`
  MODIFY `panel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `proposal_monitoring_form`
--
ALTER TABLE `proposal_monitoring_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `route1final_files`
--
ALTER TABLE `route1final_files`
  MODIFY `route1_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `route1proposal_files`
--
ALTER TABLE `route1proposal_files`
  MODIFY `route1_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `route2final_files`
--
ALTER TABLE `route2final_files`
  MODIFY `route2_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `route2proposal_files`
--
ALTER TABLE `route2proposal_files`
  MODIFY `route2_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `route3final_files`
--
ALTER TABLE `route3final_files`
  MODIFY `route3_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `route3proposal_files`
--
ALTER TABLE `route3proposal_files`
  MODIFY `route3_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

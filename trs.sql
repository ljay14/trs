-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 07:09 AM
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
  `school_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adviser`
--

INSERT INTO `adviser` (`adviser_id`, `school_id`, `password`, `fullname`, `department`, `email`) VALUES
(29, 'reamie', '123', 'REA MIE OMAS-AS', 'College of Computing and Information Science', 'ljaylacaran@gmail.com'),
(32, '201', '123', 'Lianne Pace', 'College of Tourism and Hospitality Management', '');

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
(1, 'College of Computing and Information Science', 'Bachelor of Science in Information Technology'),
(2, 'College of Computing and Information Science', 'Bachelor of Science in Computer Science'),
(3, 'College of Tourism and Hospitality Management', 'Bachelor of Science in Tourism Management'),
(4, 'College of Tourism and Hospitality Management', 'Bachelor of Science in Hospitality Management'),
(5, 'College of Business and Management', 'Bachelor of Science in Business Administration major in Financial Management'),
(6, 'College of Business and Management', 'Bachelor of Science in Business Administration major in Human Resource Management'),
(7, 'College of Business and Management', 'Bachelor of Science in Business Administration major in Marketing Management'),
(8, 'College of Teacher Education', 'Bachelor of Elementary Education'),
(9, 'College of Arts and Sciences', 'Bachelor of Arts major in English Language'),
(10, 'College of Computing and Information Science', 'Bachelor of Science in Information System'),
(11, 'College of Criminal Justice Education', 'Bachelor of Science in Criminology'),
(12, 'College of Teacher Education', 'Bachelor of Secondary Education major in English'),
(13, 'College of Teacher Education', 'Bachelor of Secondary Education major in Science'),
(14, 'College of Teacher Education', 'Bachelor of Secondary Education major in Social Studies');

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
  `panel5_id` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `controlNo` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `routeNumber` varchar(254) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finaldocufinal_files`
--

INSERT INTO `finaldocufinal_files` (`finaldocu_id`, `student_id`, `finaldocu`, `adviser_id`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `panel5_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `routeNumber`) VALUES
(22, '39', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '29', '30', '21', '22', '23', '34', 'College of Computing and Information Science', '2025-05-23 05:39:57', 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '');

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
  `panel5_id` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `adviser_id` int(255) NOT NULL,
  `controlNo` varchar(245) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `minutes` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finaldocuproposal_files`
--

INSERT INTO `finaldocuproposal_files` (`finaldocu_id`, `student_id`, `finaldocu`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `panel5_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `minutes`, `routeNumber`) VALUES
(49, '39', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '20', '21', '22', '23', '34', 'College of Computing and Information Science', '2025-05-23 05:10:32', 29, 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '', '');

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
  `finaldocu` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_monitoring_form`
--

INSERT INTO `final_monitoring_form` (`id`, `panel_id`, `student_id`, `panel_name`, `date_submitted`, `chapter`, `feedback`, `paragraph_number`, `page_number`, `date_released`, `docuRoute1`, `created_at`, `adviser_id`, `adviser_name`, `route1_id`, `route2_id`, `docuRoute2`, `status`, `route3_id`, `docuRoute3`, `finaldocu_id`, `finaldocu`, `panel5_id`, `routeNumber`) VALUES
(86, 0, 39, '', '2025-05-23', '1', '1', 11, 1, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 03:19:37', 29, 'REA MIE OMAS-AS', 23, NULL, NULL, 'Approved', 0, '', 0, '', '', 'Route 1'),
(87, 0, 39, '', '2025-05-23', '123', '123', 123, 123, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 03:21:41', 29, 'REA MIE OMAS-AS', 23, NULL, NULL, 'Approved', 0, '', 0, '', '', 'Route 1'),
(88, 30, 39, 'Rennel Uayan', '2025-05-23', '2', '2', 2, 22, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 03:22:22', 0, '', 23, NULL, NULL, 'Approved', 0, '', 0, '', '', 'Route 1'),
(89, 22, 39, 'Daisa O. Gupit', '2025-05-23', '3', '3', 33, 3, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 03:29:54', 0, '', 23, NULL, NULL, 'Approved', 0, '', 0, '', '', 'Route 1'),
(90, 21, 39, 'Lealil Palacio', '2025-05-23', '4', '4', 44, 4, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 03:30:20', 0, '', 23, NULL, NULL, 'Approved', 0, '', 0, '', '', 'Route 1'),
(91, 23, 39, 'Marlon Juhn Timogan', '2025-05-23', '5', '5', 55, 5, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 03:30:58', 0, '', 23, NULL, NULL, 'Approved', 0, '', 0, '', '', 'Route 1'),
(92, 34, 39, 'Marisol Rosario', '2025-05-23', '6', '6', 66, 6, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 03:31:19', 0, '', 23, NULL, NULL, 'Approved', 0, '', 0, '', '', 'Route 1'),
(93, 0, 39, '', '2025-05-23', '1', '11', 1, 1, '2025-05-23', NULL, '2025-05-23 03:35:39', 29, 'REA MIE OMAS-AS', NULL, NULL, NULL, 'Approved', 24, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(94, 30, 39, 'Rennel Uayan', '2025-05-23', '2', '22', 22, 2, '2025-05-23', NULL, '2025-05-23 03:36:03', 0, '', NULL, NULL, NULL, 'Approved', 24, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(95, 21, 39, 'Lealil Palacio', '2025-05-23', '3', '33', 3, 3, '2025-05-23', NULL, '2025-05-23 03:36:43', 0, '', NULL, NULL, NULL, 'Approved', 24, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(96, 22, 39, 'Daisa O. Gupit', '2025-05-23', '4', '44', 4, 4, '2025-05-23', NULL, '2025-05-23 03:37:16', 0, '', NULL, NULL, NULL, 'Approved', 24, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(97, 23, 39, 'Marlon Juhn Timogan', '2025-05-23', '5', '5', 5, 5, '2025-05-23', NULL, '2025-05-23 03:38:29', 0, '', NULL, NULL, NULL, 'Approved', 24, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(98, 34, 39, 'Marisol Rosario', '2025-05-23', '6', '6', 6, 6, '2025-05-23', NULL, '2025-05-23 03:39:01', 0, '', NULL, NULL, NULL, 'Approved', 24, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3');

-- --------------------------------------------------------

--
-- Table structure for table `panel`
--

CREATE TABLE `panel` (
  `panel_id` int(11) NOT NULL,
  `school_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panel`
--

INSERT INTO `panel` (`panel_id`, `school_id`, `password`, `fullname`, `department`, `position`, `email`) VALUES
(20, 'kenneth', '123', 'Kenneth Barrera', 'Research Office', 'panel1', 'lokolomi143@gmail.com'),
(21, 'apple', '123', 'Lealil Palacio', 'College of Computing and Information Science', 'panel2', ''),
(22, 'Daisa', '123', 'Daisa O. Gupit', 'College of Computing and Information Science', 'panel3', ''),
(23, 'marlon', '123', 'Marlon Juhn Timogan', 'College of Computing and Information Science', 'panel4', ''),
(30, '1111', '123', 'Rennel Uayan', 'College of Computing and Information Science', 'panel5', ''),
(34, '123', '123', 'Marisol Rosario', 'College of Computing and Information Science', 'panel1', '123@smccnasipit.edu.ph');

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
  `finaldocu` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal_monitoring_form`
--

INSERT INTO `proposal_monitoring_form` (`id`, `panel_id`, `student_id`, `panel_name`, `date_submitted`, `chapter`, `feedback`, `paragraph_number`, `page_number`, `date_released`, `docuRoute1`, `created_at`, `adviser_id`, `adviser_name`, `route1_id`, `route2_id`, `docuRoute2`, `status`, `route3_id`, `docuRoute3`, `finaldocu_id`, `finaldocu`, `panel5_id`, `routeNumber`) VALUES
(223, NULL, 39, NULL, '2025-05-23', '2', '2', 23, 3, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 02:49:52', 29, 'REA MIE OMAS-AS', 97, 0, '', 'Approved', 0, '', 0, '', '', 'Route 1'),
(224, '20', 39, 'Kenneth Barrera', '2025-05-23', '3', '3', 33, 3, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 02:52:53', 0, '', 97, 0, '', 'Approved', 0, '', 0, '', '', 'Route 1'),
(225, '21', 39, 'Lealil Palacio', '2025-05-23', '4', '44', 4, 4, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 02:53:14', 0, '', 97, 0, '', 'Approved', 0, '', 0, '', '', 'Route 1'),
(226, '23', 39, 'Marlon Juhn Timogan', '2025-05-23', '5', '5', 55, 5, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 02:53:59', 0, '', 97, 0, '', 'Approved', 0, '', 0, '', '', 'Route 1'),
(227, '22', 39, 'Daisa O. Gupit', '2025-05-23', '6', '6', 66, 6, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 02:54:32', 0, '', 97, 0, '', 'Approved', 0, '', 0, '', '', 'Route 1'),
(228, '34', 39, 'Marisol Rosario', '2025-05-23', '7', '7', 77, 7, '2025-05-23', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '2025-05-23 02:54:53', 0, '', 97, 0, '', 'Approved', 0, '', 0, '', '', 'Route 1'),
(229, NULL, 39, NULL, '2025-05-23', 'All', 'Document approved. No additional comments.', 0, 0, '2025-05-23', NULL, '2025-05-23 02:59:42', 29, 'REA MIE OMAS-AS', 0, 41, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 'Approved', 0, '', 0, '', '', 'Route 2'),
(230, '20', 39, 'Kenneth Barrera', '2025-05-23', '1', '11', 111, 1, '2025-05-23', NULL, '2025-05-23 03:08:42', 0, '', 0, 0, '', 'Approved', 38, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(231, '21', 39, 'Lealil Palacio', '2025-05-23', '2', '22', 22, 2, '2025-05-23', NULL, '2025-05-23 03:09:03', 0, '', 0, 0, '', 'Approved', 38, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(232, '23', 39, 'Marlon Juhn Timogan', '2025-05-23', '3', '3', 33, 3, '2025-05-23', NULL, '2025-05-23 03:09:24', 0, '', 0, 0, '', 'Approved', 38, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(233, '22', 39, 'Daisa O. Gupit', '2025-05-23', '4', '4', 44, 4, '2025-05-23', NULL, '2025-05-23 03:09:50', 0, '', 0, 0, '', 'Approved', 38, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3'),
(234, '34', 39, 'Marisol Rosario', '2025-05-23', '5', '5', 55, 5, '2025-05-23', NULL, '2025-05-23 03:10:14', 0, '', 0, 0, '', 'Approved', 38, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 0, '', '', 'Route 3');

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
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route1final_files`
--

INSERT INTO `route1final_files` (`route1_id`, `student_id`, `docuRoute1`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`, `routeNumber`) VALUES
(23, 39, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 30, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-05-23', 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '34', '');

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
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `minutes` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route1proposal_files`
--

INSERT INTO `route1proposal_files` (`route1_id`, `student_id`, `docuRoute1`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`, `minutes`, `routeNumber`) VALUES
(96, 38, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 30, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-05-23 04:51:12', 'SMCC12345', '123', 123, 'TRS', '2024-2025', '34', '', ''),
(97, 39, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 30, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-05-23 04:51:12', 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '34', '../../../uploads/minutes/group4-finalmanuscript.pdf', '');

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
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route2final_files`
--

INSERT INTO `route2final_files` (`route2_id`, `student_id`, `docuRoute2`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`, `routeNumber`) VALUES
(18, 39, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 30, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-05-23', 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '34', '');

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
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `minutes` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route2proposal_files`
--

INSERT INTO `route2proposal_files` (`route2_id`, `student_id`, `docuRoute2`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`, `minutes`, `routeNumber`) VALUES
(41, '39', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '20', '21', '22', '23', 'College of Computing and Information Science', '2025-05-23 04:57:57', 29, 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '34', '', '');

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
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route3final_files`
--

INSERT INTO `route3final_files` (`route3_id`, `student_id`, `docuRoute3`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`, `routeNumber`) VALUES
(24, 39, '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', 30, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-05-23', 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '34', '');

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
  `title` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `panel5_id` varchar(255) NOT NULL,
  `minutes` varchar(255) NOT NULL,
  `routeNumber` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route3proposal_files`
--

INSERT INTO `route3proposal_files` (`route3_id`, `student_id`, `docuRoute3`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`, `minutes`, `routeNumber`) VALUES
(38, '39', '../../../uploads/TEMPLATE-RESEARCH-CCIS-LACARAN.pdf', '20', '21', '22', '23', 'College of Computing and Information Science', '2025-05-23 05:07:36', 29, 'SMCC12345', 'Jake Castillon', 2, 'TRS', '2024-2025', '34', '', '');

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
  `school_year` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL,
  `adviser` varchar(255) NOT NULL,
  `group_number` int(255) NOT NULL,
  `group_members` text DEFAULT NULL,
  `controlNo` varchar(100) DEFAULT NULL,
  `title` varchar(215) NOT NULL,
  `semester` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `adviser_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `school_id`, `password`, `confirm_password`, `fullname`, `school_year`, `department`, `course`, `adviser`, `group_number`, `group_members`, `controlNo`, `title`, `semester`, `email`, `adviser_email`) VALUES
(39, 202251252, '123', '123', 'Jake Castillon', '2024-2025', 'College of Computing and Information Science', 'Bachelor of Science in Information Technology', 'REA MIE OMAS-AS', 2, '[\"\"]', 'SMCC12345', 'TRS', 'Second Semester', '123@smccnasipit.edu.ph', 'ljaylacaran@gmail.com');

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
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `departmentcourse`
--
ALTER TABLE `departmentcourse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `finaldocufinal_files`
--
ALTER TABLE `finaldocufinal_files`
  MODIFY `finaldocu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `finaldocuproposal_files`
--
ALTER TABLE `finaldocuproposal_files`
  MODIFY `finaldocu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `final_monitoring_form`
--
ALTER TABLE `final_monitoring_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `panel`
--
ALTER TABLE `panel`
  MODIFY `panel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `proposal_monitoring_form`
--
ALTER TABLE `proposal_monitoring_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=235;

--
-- AUTO_INCREMENT for table `route1final_files`
--
ALTER TABLE `route1final_files`
  MODIFY `route1_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `route1proposal_files`
--
ALTER TABLE `route1proposal_files`
  MODIFY `route1_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `route2final_files`
--
ALTER TABLE `route2final_files`
  MODIFY `route2_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `route2proposal_files`
--
ALTER TABLE `route2proposal_files`
  MODIFY `route2_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `route3final_files`
--
ALTER TABLE `route3final_files`
  MODIFY `route3_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `route3proposal_files`
--
ALTER TABLE `route3proposal_files`
  MODIFY `route3_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

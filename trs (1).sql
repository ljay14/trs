-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 03:21 PM
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
  `department` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adviser`
--

INSERT INTO `adviser` (`adviser_id`, `school_id`, `password`, `fullname`, `department`) VALUES
(29, 'reamie', '123', 'REA MIE OMAS-AS', 'College of Computing and Information Science'),
(32, '201', '123', 'Lianne Pace', 'College of Tourism and Hospitality Management');

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
(1, 'College of Computing and Information Science', 'BS of Information Technology\'s'),
(2, 'College of Computing and Information Science', 'BS in Computer Science'),
(3, 'College of Tourism and Hospitality Management', 'Bachelor of Science in Tourism Management'),
(4, 'College of Tourism and Hospitality Management', 'Bachelor of Science in Hospitality Management'),
(5, 'College of Business and Management', 'BSBA - Financial Management'),
(6, 'College of Business and Management', 'BSBA - Human Resource Management'),
(7, 'College of Business and Management', 'BSBA - Marketing Management'),
(8, 'College of Teacher Education', 'Bachelor of Elementary Education');

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
  `school_year` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finaldocufinal_files`
--

INSERT INTO `finaldocufinal_files` (`finaldocu_id`, `student_id`, `finaldocu`, `adviser_id`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `panel5_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`) VALUES
(16, '30', '../../../uploads/Certificate_of_Endorsement (22).pdf', '29', '20', '21', '22', '23', '0', 'College of Computing and Information Science', '2025-04-30 10:40:47', 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025');

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
  `school_year` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finaldocuproposal_files`
--

INSERT INTO `finaldocuproposal_files` (`finaldocu_id`, `student_id`, `finaldocu`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `panel5_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`) VALUES
(43, '30', '../../../uploads/Certificate_of_Endorsement (21).pdf', '20', '21', '22', '23', '', 'College of Computing and Information Science', '2025-04-30 10:16:50', 29, 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_monitoring_form`
--

INSERT INTO `final_monitoring_form` (`id`, `panel_id`, `student_id`, `panel_name`, `date_submitted`, `chapter`, `feedback`, `paragraph_number`, `page_number`, `date_released`, `docuRoute1`, `created_at`, `adviser_id`, `adviser_name`, `route1_id`, `route2_id`, `docuRoute2`, `status`, `route3_id`, `docuRoute3`, `finaldocu_id`, `finaldocu`, `panel5_id`) VALUES
(50, 0, 30, '', '2025-04-30', '2', 'Feedback', 2, 2, '2025-04-30', '../../../uploads/Certificate_of_Endorsement (24).pdf', '2025-04-30 08:21:29', 29, 'REA MIE OMAS-AS', 17, NULL, NULL, 'Approved', 0, '', 0, '', ''),
(51, 20, 30, 'Kenneth Barrera', '2025-04-30', '2', 'change the font size', 3, 3, '2025-04-30', '../../../uploads/Certificate_of_Endorsement (24).pdf', '2025-04-30 08:22:03', 0, '', 17, NULL, NULL, 'Approved', 0, '', 0, '', ''),
(52, 21, 30, 'Lealil Palacio', '2025-04-30', '3', 'feedback', 3, 3, '2025-04-30', '../../../uploads/Certificate_of_Endorsement (24).pdf', '2025-04-30 08:22:26', 0, '', 17, NULL, NULL, 'Approved', 0, '', 0, '', ''),
(53, 22, 30, 'Daisa O. Gupit', '2025-04-30', '3', 'Feedback', 3, 3, '2025-04-30', '../../../uploads/Certificate_of_Endorsement (24).pdf', '2025-04-30 08:22:52', 0, '', 17, NULL, NULL, 'Approved', 0, '', 0, '', ''),
(54, 23, 30, 'Marlon Juhn Timogan', '2025-04-30', '3', 'Change the font size', 3, 3, '2025-04-30', '../../../uploads/Certificate_of_Endorsement (24).pdf', '2025-04-30 08:23:17', 0, '', 17, NULL, NULL, 'Approved', 0, '', 0, '', ''),
(55, 21, 30, 'Lealil Palacio', '2025-04-30', '2', 'Feedback', 2, 3, '2025-04-30', NULL, '2025-04-30 08:24:52', 0, '', NULL, 12, '../../../uploads/Certificate_of_Endorsement (22).pdf', 'Approved', 0, '', 0, '', ''),
(56, 21, 30, 'Lealil Palacio', '2025-04-30', '3', 'Change font size', 3, 3, '2025-04-30', NULL, '2025-04-30 08:40:25', 0, '', NULL, NULL, NULL, 'Approved', 17, '../../../uploads/Certificate_of_Endorsement (23).pdf', 0, '', '');

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
  `position` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panel`
--

INSERT INTO `panel` (`panel_id`, `school_id`, `password`, `fullname`, `department`, `position`) VALUES
(20, 'kenneth', '123', 'Kenneth Barrera', 'Research Office', 'panel1'),
(21, 'apple', '123', 'Lealil Palacio', 'College of Computing and Information Science', 'panel2'),
(22, 'Daisa', '123', 'Daisa O. Gupit', 'College of Computing and Information Science', 'panel3'),
(23, 'marlon', '123', 'Marlon Juhn Timogan', 'College of Computing and Information Science', 'panel4'),
(29, '1123', '123', '123', 'College of Computing and Information Science', 'panel4');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal_monitoring_form`
--

INSERT INTO `proposal_monitoring_form` (`id`, `panel_id`, `student_id`, `panel_name`, `date_submitted`, `chapter`, `feedback`, `paragraph_number`, `page_number`, `date_released`, `docuRoute1`, `created_at`, `adviser_id`, `adviser_name`, `route1_id`, `route2_id`, `docuRoute2`, `status`, `route3_id`, `docuRoute3`, `finaldocu_id`, `finaldocu`, `panel5_id`) VALUES
(153, NULL, 30, NULL, '2025-04-30', '2', 'Feedback', 2, 2, '2025-04-30', '../../../uploads/TPM-CCIS-BERNALDEZ.docx', '2025-04-30 07:38:21', 29, 'REA MIE OMAS-AS', 50, 0, '', 'Approved', 0, '', 0, '', ''),
(154, '20', 30, 'Kenneth Barrera', '2025-04-30', '2', 'feedback', 2, 3, '2025-04-30', '../../../uploads/TPM-CCIS-BERNALDEZ.docx', '2025-04-30 08:03:27', 0, '', 50, 0, '', 'Approved', 0, '', 0, '', ''),
(155, '21', 30, 'Lealil Palacio', '2025-04-30', '1', 'change the font size', 3, 3, '2025-04-30', '../../../uploads/TPM-CCIS-BERNALDEZ.docx', '2025-04-30 08:05:02', 0, '', 50, 0, '', 'Approved', 0, '', 0, '', ''),
(156, '22', 30, 'Daisa O. Gupit', '2025-04-30', '4', 'feedback', 2, 2, '2025-04-30', '../../../uploads/TPM-CCIS-BERNALDEZ.docx', '2025-04-30 08:05:32', 0, '', 50, 0, '', 'Approved', 0, '', 0, '', ''),
(157, '23', 30, 'Marlon Juhn Timogan', '2025-04-30', '1', 'Change the font size', 2, 2, '2025-04-30', '../../../uploads/TPM-CCIS-BERNALDEZ.docx', '2025-04-30 08:06:09', 0, '', 50, 0, '', 'Approved', 0, '', 0, '', ''),
(158, NULL, 30, NULL, '2025-04-30', '3', 'Feedback', 2, 2, '2025-04-30', NULL, '2025-04-30 08:16:30', 29, 'REA MIE OMAS-AS', 0, 0, '', 'Approved', 23, '../../../uploads/Certificate_of_Endorsement (23).pdf', 0, '', '');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route1final_files`
--

INSERT INTO `route1final_files` (`route1_id`, `student_id`, `docuRoute1`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`) VALUES
(17, 30, '../../../uploads/Certificate_of_Endorsement (24).pdf', 20, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-04-30', 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025', '');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route1proposal_files`
--

INSERT INTO `route1proposal_files` (`route1_id`, `student_id`, `docuRoute1`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`) VALUES
(51, 32, '../../../uploads/Certificate_of_Endorsement (22).pdf', 20, 21, 22, 29, 29, 'College of Computing and Information Science', '2025-04-30 09:54:37', 'BSIT212', 'Rylvin Celnar Tiempo', 2, 'TRS', '2024-2025', '0'),
(52, 33, '../../../uploads/Certificate_of_Endorsement (21).pdf', 0, 0, 0, 0, 32, 'College of Tourism and Hospitality Management', NULL, 'CTHM201', 'John Lester Saladores', 2, 'MICA', '2024-2025', ''),
(53, 30, '../../../uploads/Certificate_of_Endorsement (24).pdf', 20, 21, 22, 29, 29, 'College of Computing and Information Science', '2025-05-01 08:51:09', 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025', '0');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route2final_files`
--

INSERT INTO `route2final_files` (`route2_id`, `student_id`, `docuRoute2`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`) VALUES
(12, 30, '../../../uploads/Certificate_of_Endorsement (22).pdf', 20, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-04-30', 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025', '');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route2proposal_files`
--

INSERT INTO `route2proposal_files` (`route2_id`, `student_id`, `docuRoute2`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`) VALUES
(25, '30', '../../../uploads/Certificate_of_Endorsement (23).pdf', '20', '21', '22', '23', 'College of Computing and Information Science', '2025-04-30 10:06:32', 29, 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025', '');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route3final_files`
--

INSERT INTO `route3final_files` (`route3_id`, `student_id`, `docuRoute3`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `adviser_id`, `department`, `date_submitted`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`) VALUES
(17, 30, '../../../uploads/Certificate_of_Endorsement (23).pdf', 20, 21, 22, 23, 29, 'College of Computing and Information Science', '2025-04-30', 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025', '');

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
  `panel5_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route3proposal_files`
--

INSERT INTO `route3proposal_files` (`route3_id`, `student_id`, `docuRoute3`, `panel1_id`, `panel2_id`, `panel3_id`, `panel4_id`, `department`, `date_submitted`, `adviser_id`, `controlNo`, `fullname`, `group_number`, `title`, `school_year`, `panel5_id`) VALUES
(23, '30', '../../../uploads/Certificate_of_Endorsement (23).pdf', '20', '21', '22', '23', 'College of Computing and Information Science', '2025-04-30 10:15:09', 29, 'BSIT2024', 'Jake Castillon', 2, 'Thesis Routing System of Saint Michael College', '2024-2025', '');

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
  `title` varchar(215) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `school_id`, `password`, `confirm_password`, `fullname`, `school_year`, `department`, `course`, `adviser`, `group_number`, `group_members`, `controlNo`, `title`) VALUES
(29, 202251330, '123', '123', 'Ell jay Lacaran', '2024-2025', 'College of Computing and Information Science', 'BS of Information Technology\'s', 'Rea Mie', 2, '[\"Jake Castillon\",\"Jenessa Ocay\",\"Rylvin Celnar Tiempo\",\"John Lester Saladores\"]', 'BSIT2024', 'Thesis Routing System of Saint Michael College of Caraga'),
(30, 202251243, '123', '123', 'Jake Castillon', '2024-2025', 'College of Computing and Information Science', 'BS of Information Technology\'s', 'REA MIE OMAS-AS', 2, '[\"Ell jay Lacaran\"]', 'BSIT2024', 'Thesis Routing System of Saint Michael College'),
(31, 123, '123', '123', 'Jenessa Ocay', '2024-2025', 'College of Computing and Information Science', 'BS of Information Technology\'s', 'REA MIE OMAS-AS	', 2, '[\"Rylvin Celnar Tiempo\"]', 'CTHM2024', 'TRS'),
(32, 202251468, '123', '123', 'Rylvin Celnar Tiempo', '2024-2025', 'College of Computing and Information Science', 'BS of Information Technology\'s', 'REA MIE OMAS-AS', 2, '[\"Jenessa Ocay\"]', 'BSIT212', 'TRS'),
(33, 202251252, '123', '123', 'John Lester Saladores', '2024-2025', 'College of Tourism and Hospitality Management', 'Bachelor of Science in Hospitality Management', 'Lianne Pace', 2, '[\"Art Olita\"]', 'CTHM201', 'MICA');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `finaldocufinal_files`
--
ALTER TABLE `finaldocufinal_files`
  MODIFY `finaldocu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `finaldocuproposal_files`
--
ALTER TABLE `finaldocuproposal_files`
  MODIFY `finaldocu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `final_monitoring_form`
--
ALTER TABLE `final_monitoring_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `panel`
--
ALTER TABLE `panel`
  MODIFY `panel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `proposal_monitoring_form`
--
ALTER TABLE `proposal_monitoring_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `route1final_files`
--
ALTER TABLE `route1final_files`
  MODIFY `route1_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `route1proposal_files`
--
ALTER TABLE `route1proposal_files`
  MODIFY `route1_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `route2final_files`
--
ALTER TABLE `route2final_files`
  MODIFY `route2_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `route2proposal_files`
--
ALTER TABLE `route2proposal_files`
  MODIFY `route2_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `route3final_files`
--
ALTER TABLE `route3final_files`
  MODIFY `route3_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `route3proposal_files`
--
ALTER TABLE `route3proposal_files`
  MODIFY `route3_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

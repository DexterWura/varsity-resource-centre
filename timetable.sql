-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2024 at 02:29 PM
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
-- Database: `timetable`
--

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculties`
--

INSERT INTO `faculties` (`id`, `name`) VALUES
(1, 'Accounting Sciences'),
(2, 'Economic Sciences'),
(3, 'Information & Marketing Sciences'),
(4, 'Supply Chain, Insurance & Risk Sciences'),
(5, 'Management Sciences'),
(6, 'Tourism, Hospitality and Leisure Sciences');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `module_code` varchar(50) NOT NULL,
  `module_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `faculty_id`, `module_code`, `module_name`) VALUES
(1, 1, 'ACC144', 'Introduction to Accounting'),
(2, 1, 'ACC450', 'Advanced Accounting'),
(3, 2, 'EC132', 'Microeconomics'),
(4, 2, 'EC453', 'Macroeconomics'),
(5, 3, 'INFO139', 'Information Systems Basics'),
(6, 4, 'SCIRS101', 'Introduction to Supply Chain'),
(7, 5, 'THM239', 'Tourism and Hospitality Management'),
(8, 1, 'ACC144', 'Introduction to Accounting'),
(9, 1, 'ACC450', 'Advanced Accounting'),
(10, 1, 'ACC241', 'Accounting 241'),
(11, 1, 'ACC456', 'Accounting 456'),
(12, 1, 'ACC249', 'Accounting 249'),
(13, 1, 'ACC451', 'Accounting 451'),
(14, 1, 'ACC140', 'Accounting 140'),
(15, 1, 'ACC248', 'Accounting 248'),
(16, 2, 'EC132', 'Microeconomics'),
(17, 2, 'EC453', 'Macroeconomics'),
(18, 2, 'EC236', 'Economics 236'),
(19, 2, 'EC233', 'Economics 233'),
(20, 2, 'EC451', 'Economics 451'),
(21, 2, 'EC135', 'Economics 135'),
(22, 3, 'INFO139', 'Information Systems Basics'),
(23, 3, 'INFO233', 'Information Systems 233'),
(24, 3, 'INFO437', 'Enterprise Architecture'),
(25, 3, 'DSI236', 'Data Systems 236'),
(26, 3, 'DSI133', 'Data Systems 133'),
(27, 3, 'INFO410', 'Information Management 410'),
(28, 3, 'INFO138', 'Information Systems 138'),
(29, 4, 'SCIRS101', 'Introduction to Supply Chain'),
(30, 4, 'RMI232', 'Risk Management 232'),
(31, 4, 'RMI435', 'Risk Management 435'),
(32, 4, 'RMI431', 'Risk Management 431'),
(33, 4, 'RMI130', 'Risk Management 130'),
(34, 4, 'RMI237', 'Risk Management 237'),
(35, 5, 'BM443', 'Business Management 443'),
(36, 5, 'BM431', 'Business Management 431'),
(37, 5, 'BM137', 'Business Management 137'),
(38, 5, 'BM249', 'Business Management 249'),
(39, 6, 'THM239', 'Tourism and Hospitality Management'),
(40, 6, 'THM240', 'Tourism and Hospitality Management 240'),
(41, 6, 'THM434', 'Tourism and Hospitality Management 434'),
(42, 6, 'THM435', 'Tourism and Hospitality Management 435'),
(43, 6, 'THM136', 'Tourism and Hospitality Management 136'),
(60, 3, 'INFO437', 'ENTREPRISE ACHITECTURE'),
(61, 3, 'INFO439', 'COMPUTER GRAPHICS');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `module_id`, `day_of_week`, `start_time`, `end_time`, `venue`) VALUES
(1, 1, 'Monday', '08:00:00', '11:00:00', 'LR6'),
(2, 1, 'Wednesday', '08:00:00', '11:00:00', 'LR6'),
(3, 2, 'Thursday', '08:00:00', '11:00:00', 'LR16'),
(4, 3, 'Monday', '08:00:00', '11:00:00', 'LR8'),
(5, 4, 'Friday', '08:00:00', '11:00:00', 'LR8'),
(6, 5, 'Monday', '11:00:00', '14:00:00', 'N2'),
(7, 1, 'Monday', '08:00:00', '11:00:00', 'LR6'),
(8, 4, 'Monday', '08:00:00', '11:00:00', 'LR9'),
(9, 2, 'Monday', '08:00:00', '11:00:00', 'LR8'),
(10, 5, 'Monday', '08:00:00', '11:00:00', 'LR13'),
(11, 3, 'Monday', '08:00:00', '11:00:00', 'N2'),
(12, 1, 'Monday', '14:00:00', '17:00:00', 'LR16'),
(13, 2, 'Tuesday', '08:00:00', '11:00:00', 'LR8'),
(14, 5, 'Tuesday', '08:00:00', '11:00:00', 'LR13'),
(15, 3, 'Tuesday', '08:00:00', '11:00:00', 'N2'),
(16, 6, 'Tuesday', '08:00:00', '11:00:00', 'LR15'),
(17, 4, 'Wednesday', '08:00:00', '11:00:00', 'LR9'),
(18, 1, 'Wednesday', '08:00:00', '11:00:00', 'LR6'),
(19, 2, 'Wednesday', '08:00:00', '11:00:00', 'LR8'),
(20, 6, 'Wednesday', '08:00:00', '11:00:00', 'LR11'),
(21, 2, 'Thursday', '08:00:00', '11:00:00', 'LR8'),
(22, 5, 'Thursday', '08:00:00', '11:00:00', 'LR13'),
(23, 3, 'Thursday', '08:00:00', '11:00:00', 'N2'),
(24, 6, 'Thursday', '08:00:00', '11:00:00', 'LR15'),
(25, 4, 'Friday', '08:00:00', '11:00:00', 'LR9'),
(26, 1, 'Friday', '08:00:00', '11:00:00', 'LR16'),
(27, 6, 'Friday', '08:00:00', '11:00:00', 'LR11'),
(28, 3, 'Friday', '08:00:00', '11:00:00', 'N2'),
(29, 5, 'Friday', '08:00:00', '11:00:00', 'LR13'),
(30, 24, 'Monday', '14:00:00', '17:00:00', 'N2'),
(31, 61, 'Tuesday', '11:00:00', '14:00:00', 'N2'),
(32, 61, 'Thursday', '11:00:00', '14:00:00', 'N2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `faculties`
--
ALTER TABLE `faculties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

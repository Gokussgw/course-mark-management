-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2025 at 12:24 AM
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
-- Database: `course_mark_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `advisor_notes`
--

CREATE TABLE `advisor_notes` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `advisor_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('quiz','assignment','midterm','final_exam','other') NOT NULL,
  `weightage` decimal(5,2) NOT NULL,
  `max_mark` decimal(10,2) NOT NULL,
  `is_final_exam` tinyint(1) DEFAULT 0,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `course_id`, `name`, `type`, `weightage`, `max_mark`, `is_final_exam`, `date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Assignment 1', 'assignment', 10.00, 100.00, 0, '2025-09-15', '2025-07-11 18:55:08', '2025-07-11 18:55:08'),
(2, 1, 'Midterm Exam', 'midterm', 30.00, 100.00, 0, '2025-10-15', '2025-07-11 18:55:08', '2025-07-11 18:55:08'),
(3, 1, 'Final Exam', 'final_exam', 60.00, 100.00, 1, '2025-12-15', '2025-07-11 18:55:08', '2025-07-11 18:55:08'),
(4, 2, 'Assignment 1', 'assignment', 15.00, 100.00, 0, '2025-09-20', '2025-07-11 18:55:08', '2025-07-11 18:55:08'),
(5, 2, 'Final Project', 'assignment', 25.00, 100.00, 0, '2025-11-30', '2025-07-11 18:55:08', '2025-07-11 18:55:08'),
(6, 2, 'Final Exam', 'final_exam', 60.00, 100.00, 1, '2025-12-20', '2025-07-11 18:55:08', '2025-07-11 18:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `component_marks`
--

CREATE TABLE `component_marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `assignment_mark` decimal(5,2) DEFAULT 0.00,
  `quiz_mark` decimal(5,2) DEFAULT 0.00,
  `test_mark` decimal(5,2) DEFAULT 0.00,
  `assignment_percentage` decimal(5,2) GENERATED ALWAYS AS (`assignment_mark` * 0.25) STORED,
  `quiz_percentage` decimal(5,2) GENERATED ALWAYS AS (`quiz_mark` * 0.20) STORED,
  `test_percentage` decimal(5,2) GENERATED ALWAYS AS (`test_mark` * 0.25) STORED,
  `component_total` decimal(5,2) GENERATED ALWAYS AS (`assignment_percentage` + `quiz_percentage` + `test_percentage`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `lecturer_id`, `semester`, `academic_year`, `created_at`, `updated_at`) VALUES
(1, 'CS101', 'Introduction to Computer Science', 2, 'Fall', '2025-2026', '2025-07-11 18:54:58', '2025-07-11 18:54:58'),
(2, 'CS201', 'Data Structures and Algorithms', 2, 'Fall', '2025-2026', '2025-07-11 18:54:58', '2025-07-11 18:54:58'),
(3, 'CS301', 'Database Systems', 2, 'Fall', '2025-2026', '2025-07-17 23:02:23', '2025-07-17 23:02:23'),
(4, 'CS302', 'Software Engineering', 2, 'Spring', '2025-2026', '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(5, 'CS401', 'Computer Networks', 2, 'Fall', '2025-2026', '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(6, 'CS402', 'Machine Learning', 2, 'Spring', '2025-2026', '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(7, 'MATH201', 'Linear Algebra', 2, 'Fall', '2025-2026', '2025-07-23 00:07:27', '2025-07-23 00:07:27');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `academic_year`, `semester`, `created_at`) VALUES
(86, 10, 1, '2025-2026', 'Fall', '2025-07-23 20:14:00'),
(87, 11, 1, '2025-2026', 'Fall', '2025-07-23 20:14:00'),
(88, 10, 2, '2025-2026', 'Fall', '2025-07-23 20:14:00'),
(89, 12, 2, '2025-2026', 'Fall', '2025-07-23 20:14:00'),
(90, 13, 2, '2025-2026', 'Fall', '2025-07-23 20:14:00'),
(91, 14, 2, '2025-2026', 'Fall', '2025-07-23 20:14:00'),
(92, 12, 3, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(93, 14, 3, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(94, 16, 3, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(95, 17, 3, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(96, 11, 4, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(97, 13, 4, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(98, 17, 4, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(99, 10, 5, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(100, 11, 5, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(101, 12, 5, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(102, 14, 5, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(103, 13, 6, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(104, 15, 6, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(105, 16, 6, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(106, 17, 6, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(107, 11, 7, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(108, 16, 7, '2024/2025', 'Semester 1', '2025-07-23 20:22:03'),
(109, 17, 7, '2024/2025', 'Semester 1', '2025-07-23 20:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `final_marks`
--

CREATE TABLE `final_marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `component_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `final_exam_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `final_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `grade` char(2) NOT NULL DEFAULT '',
  `gpa_points` decimal(3,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `calculated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_marks_custom`
--

CREATE TABLE `final_marks_custom` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `assignment_mark` decimal(5,2) DEFAULT 0.00,
  `assignment_percentage` decimal(5,2) DEFAULT 0.00,
  `quiz_mark` decimal(5,2) DEFAULT 0.00,
  `quiz_percentage` decimal(5,2) DEFAULT 0.00,
  `test_mark` decimal(5,2) DEFAULT 0.00,
  `test_percentage` decimal(5,2) DEFAULT 0.00,
  `final_exam_mark` decimal(5,2) DEFAULT 0.00,
  `final_exam_percentage` decimal(5,2) DEFAULT 0.00,
  `component_total` decimal(5,2) DEFAULT 0.00,
  `final_grade` decimal(5,2) DEFAULT 0.00,
  `letter_grade` varchar(2) DEFAULT 'F',
  `gpa` decimal(3,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_marks_custom`
--

INSERT INTO `final_marks_custom` (`id`, `student_id`, `course_id`, `assignment_mark`, `assignment_percentage`, `quiz_mark`, `quiz_percentage`, `test_mark`, `test_percentage`, `final_exam_mark`, `final_exam_percentage`, `component_total`, `final_grade`, `letter_grade`, `gpa`, `created_at`, `updated_at`) VALUES
(41, 10, 1, 95.00, 23.75, 85.00, 12.75, 87.00, 26.10, 100.00, 30.00, 62.60, 92.60, 'A+', 4.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(42, 10, 2, 94.00, 23.50, 91.00, 13.65, 93.00, 27.90, 96.00, 28.80, 65.05, 93.85, 'A+', 4.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(43, 11, 1, 83.00, 20.75, 83.00, 12.45, 81.00, 24.30, 81.00, 24.30, 57.50, 81.80, 'A-', 3.70, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(44, 11, 2, 75.00, 18.75, 68.00, 10.20, 72.00, 21.60, 75.00, 22.50, 50.55, 73.05, 'B', 3.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(45, 12, 1, 76.00, 19.00, 68.00, 10.20, 75.00, 22.50, 82.00, 24.60, 51.70, 76.30, 'B', 3.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(46, 12, 2, 77.00, 19.25, 73.00, 10.95, 85.00, 25.50, 90.00, 27.00, 55.70, 82.70, 'A-', 3.70, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(47, 13, 1, 44.00, 11.00, 64.00, 9.60, 71.00, 21.30, 83.00, 24.90, 41.90, 66.80, 'C', 2.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(48, 13, 2, 43.00, 10.75, 63.00, 9.45, 85.00, 25.50, 76.00, 22.80, 45.70, 68.50, 'C+', 2.30, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(49, 14, 1, 82.00, 20.50, 88.00, 13.20, 79.00, 23.70, 56.00, 16.80, 57.40, 74.20, 'B', 3.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(50, 14, 2, 87.00, 21.75, 84.00, 12.60, 75.00, 22.50, 55.00, 16.50, 56.85, 73.35, 'B', 3.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(51, 15, 1, 41.00, 10.25, 82.00, 12.30, 47.00, 14.10, 67.00, 20.10, 36.65, 56.75, 'D', 1.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(52, 15, 2, 53.00, 13.25, 59.00, 8.85, 72.00, 21.60, 56.00, 16.80, 43.70, 60.50, 'C-', 1.70, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(53, 16, 1, 41.00, 10.25, 21.00, 3.15, 41.00, 12.30, 43.00, 12.90, 25.70, 38.60, 'F', 0.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(54, 16, 2, 40.00, 10.00, 24.00, 3.60, 48.00, 14.40, 51.00, 15.30, 28.00, 43.30, 'F', 0.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(55, 17, 1, 40.00, 10.00, 55.00, 8.25, 59.00, 17.70, 55.00, 16.50, 35.95, 52.45, 'D', 1.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(56, 17, 2, 69.00, 17.25, 77.00, 11.55, 79.00, 23.70, 80.00, 24.00, 52.50, 76.50, 'B', 3.00, '2025-07-22 23:56:54', '2025-07-23 20:49:09'),
(57, 10, 3, 85.00, 21.25, 90.00, 13.50, 86.00, 25.80, 89.00, 26.70, 60.55, 87.25, 'A-', 3.70, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(58, 10, 4, 94.00, 23.50, 89.00, 13.35, 86.00, 25.80, 90.00, 27.00, 62.65, 89.65, 'A-', 3.70, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(59, 10, 5, 90.00, 22.50, 88.00, 13.20, 87.00, 26.10, 88.00, 26.40, 61.80, 88.20, 'A', 4.00, '2025-07-23 00:07:27', '2025-07-23 20:45:49'),
(60, 10, 6, 89.00, 22.25, 89.00, 13.35, 90.00, 27.00, 94.00, 28.20, 62.60, 90.80, 'A', 4.00, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(61, 10, 7, 88.00, 22.00, 90.00, 13.50, 85.00, 25.50, 84.00, 25.20, 61.00, 86.20, 'A-', 3.70, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(62, 11, 3, 54.50, 13.63, 72.50, 10.88, 81.50, 24.45, 55.50, 16.65, 48.95, 65.60, 'C+', 2.30, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(63, 11, 4, 66.00, 16.50, 54.00, 8.10, 71.00, 21.30, 39.00, 11.70, 45.90, 57.60, 'C-', 1.70, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(64, 11, 5, 50.50, 12.63, 50.50, 7.58, 53.50, 16.05, 42.50, 12.75, 36.25, 49.00, 'F', 0.00, '2025-07-23 00:07:27', '2025-07-23 20:45:49'),
(65, 11, 6, 30.00, 7.50, 47.00, 7.05, 34.00, 10.20, 41.00, 12.30, 24.75, 37.05, 'F', 0.00, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(66, 11, 7, 35.50, 8.88, 33.50, 5.03, 51.50, 15.45, 31.50, 9.45, 29.35, 38.80, 'F', 0.00, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(67, 12, 3, 71.50, 17.88, 69.50, 10.43, 74.50, 22.35, 70.50, 21.15, 50.65, 71.80, 'B-', 2.70, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(68, 12, 4, 83.00, 20.75, 76.00, 11.40, 87.00, 26.10, 68.00, 20.40, 58.25, 78.65, 'B', 3.00, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(69, 12, 5, 77.50, 19.38, 89.50, 13.43, 81.50, 24.45, 77.50, 23.25, 57.25, 80.50, 'A-', 3.70, '2025-07-23 00:07:27', '2025-07-23 20:45:49'),
(70, 12, 6, 89.00, 22.25, 86.00, 12.90, 99.00, 29.70, 78.00, 23.40, 64.85, 88.25, 'A-', 3.70, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(71, 12, 7, 100.00, 25.00, 97.50, 14.63, 100.00, 30.00, 93.50, 28.05, 69.63, 97.68, 'A', 4.00, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(72, 13, 3, 72.00, 18.00, 65.00, 9.75, 59.00, 17.70, 34.00, 10.20, 45.45, 55.65, 'C-', 1.70, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(73, 13, 4, 55.00, 13.75, 55.00, 8.25, 54.00, 16.20, 50.00, 15.00, 38.20, 53.20, 'D+', 1.30, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(74, 13, 5, 66.00, 16.50, 66.00, 9.90, 51.00, 15.30, 59.00, 17.70, 41.70, 59.40, 'D+', 1.30, '2025-07-23 00:07:27', '2025-07-23 20:45:49'),
(75, 13, 6, 43.00, 10.75, 54.00, 8.10, 68.00, 20.40, 45.00, 13.50, 39.25, 52.75, 'D+', 1.30, '2025-07-23 00:07:27', '2025-07-23 00:07:27'),
(76, 13, 7, 40.00, 10.00, 66.00, 9.90, 69.00, 20.70, 75.00, 22.50, 40.60, 63.10, 'C', 2.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(77, 14, 3, 64.00, 16.00, 68.00, 10.20, 63.00, 18.90, 79.00, 23.70, 45.10, 68.80, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(78, 14, 4, 66.00, 16.50, 66.00, 9.90, 71.00, 21.30, 59.00, 17.70, 47.70, 65.40, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(79, 14, 5, 85.00, 21.25, 66.00, 9.90, 70.00, 21.00, 58.00, 17.40, 52.15, 69.55, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(80, 14, 6, 66.00, 16.50, 75.00, 11.25, 76.00, 22.80, 59.00, 17.70, 50.55, 68.25, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(81, 14, 7, 70.00, 17.50, 68.00, 10.20, 57.00, 17.10, 74.00, 22.20, 44.80, 67.00, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(82, 15, 3, 64.00, 16.00, 66.00, 9.90, 69.00, 20.70, 53.00, 15.90, 46.60, 62.50, 'C', 2.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(83, 15, 4, 56.00, 14.00, 71.00, 10.65, 74.00, 22.20, 69.00, 20.70, 46.85, 67.55, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(84, 15, 5, 56.00, 14.00, 67.00, 10.05, 72.00, 21.60, 63.00, 18.90, 45.65, 64.55, 'C', 2.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(85, 15, 6, 56.00, 14.00, 73.00, 10.95, 76.00, 22.80, 60.00, 18.00, 47.75, 65.75, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(86, 15, 7, 62.00, 15.50, 71.00, 10.65, 78.00, 23.40, 51.00, 15.30, 49.55, 64.85, 'C', 2.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(87, 16, 3, 31.50, 7.88, 32.50, 4.88, 23.50, 7.05, 33.50, 10.05, 19.80, 29.85, 'F', 0.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(88, 16, 4, 41.00, 10.25, 26.00, 3.90, 34.00, 10.20, 40.00, 12.00, 24.35, 36.35, 'F', 0.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(89, 16, 5, 23.50, 5.88, 27.50, 4.13, 38.50, 11.55, 16.50, 4.95, 21.55, 26.50, 'F', 0.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(90, 16, 6, 29.00, 7.25, 25.00, 3.75, 26.00, 7.80, 35.00, 10.50, 18.80, 29.30, 'F', 0.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(91, 16, 7, 8.50, 2.13, 10.50, 1.58, 26.50, 7.95, 19.50, 5.85, 11.65, 17.50, 'F', 0.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(92, 17, 3, 41.50, 10.38, 54.50, 8.18, 76.50, 22.95, 78.50, 23.55, 41.50, 65.05, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(93, 17, 4, 65.00, 16.25, 76.00, 11.40, 60.00, 18.00, 67.00, 20.10, 45.65, 65.75, 'C+', 2.30, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(94, 17, 5, 52.50, 13.13, 80.50, 12.08, 87.50, 26.25, 82.50, 24.75, 51.45, 76.20, 'B', 3.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(95, 17, 6, 63.00, 15.75, 68.00, 10.20, 91.00, 27.30, 86.00, 25.80, 53.25, 79.05, 'B', 3.00, '2025-07-23 00:07:28', '2025-07-23 00:07:28'),
(96, 17, 7, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 70.00, 100.00, 'A', 4.00, '2025-07-23 00:07:28', '2025-07-23 20:24:24');

-- --------------------------------------------------------

--
-- Table structure for table `lecturer_feedback`
--

CREATE TABLE `lecturer_feedback` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `feedback_type` enum('general','performance','improvement','commendation','concern') NOT NULL DEFAULT 'general',
  `subject` varchar(255) NOT NULL,
  `feedback` text NOT NULL,
  `is_visible_to_student` tinyint(1) DEFAULT 1,
  `is_visible_to_advisor` tinyint(1) DEFAULT 1,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `mark` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `content`, `related_id`, `sender_id`, `is_read`, `created_at`) VALUES
(2, 2, 'remark_request', 'Student One has requested a remark for CS101 Midterm Exam.', 1, NULL, 0, '2025-07-11 18:55:28'),
(4, 2, 'mark', 'Mark update confirmed for Student One in CS101 - Assignment: 85%. Student has been notified automatically.', 1, 2, 0, '2025-07-22 21:26:58'),
(6, 2, 'mark', 'Mark update confirmed for Student One in CS101 - Assignment: 95%. Student has been notified automatically.', 1, 2, 0, '2025-07-22 21:31:06'),
(8, 2, 'mark', 'Mark update confirmed for Student One in CS101 - Quiz: 89%. Student has been notified automatically.', 1, 2, 0, '2025-07-22 21:31:06'),
(10, 2, 'mark', 'Mark update confirmed for Student One in CS101 - Test: 91%. Student has been notified automatically.', 1, 2, 0, '2025-07-22 21:31:06'),
(12, 2, 'mark', 'Mark update confirmed for Student One in CS101 - Final Exam: 96%. Student has been notified automatically.', 1, 2, 0, '2025-07-22 21:31:06'),
(13, 17, 'mark', 'Your Assignment mark for MATH201 - Linear Algebra has been updated. New mark: 100%. Please check your academic dashboard for details.', 7, 2, 0, '2025-07-23 20:24:24'),
(14, 2, 'mark', 'Mark update confirmed for Marcus Williams in MATH201 - Assignment: 100%. Student has been notified automatically.', 7, 2, 0, '2025-07-23 20:24:24'),
(15, 17, 'mark', 'Your Quiz mark for MATH201 - Linear Algebra has been updated. New mark: 100%. Please check your academic dashboard for details.', 7, 2, 0, '2025-07-23 20:24:24'),
(16, 2, 'mark', 'Mark update confirmed for Marcus Williams in MATH201 - Quiz: 100%. Student has been notified automatically.', 7, 2, 0, '2025-07-23 20:24:24'),
(17, 17, 'mark', 'Your Test mark for MATH201 - Linear Algebra has been updated. New mark: 100%. Please check your academic dashboard for details.', 7, 2, 0, '2025-07-23 20:24:24'),
(18, 2, 'mark', 'Mark update confirmed for Marcus Williams in MATH201 - Test: 100%. Student has been notified automatically.', 7, 2, 0, '2025-07-23 20:24:24'),
(19, 17, 'mark', 'Your Final Exam mark for MATH201 - Linear Algebra has been updated. New mark: 100%. Please check your academic dashboard for details.', 7, 2, 0, '2025-07-23 20:24:24'),
(20, 2, 'mark', 'Mark update confirmed for Marcus Williams in MATH201 - Final Exam: 100%. Student has been notified automatically.', 7, 2, 0, '2025-07-23 20:24:24'),
(21, 10, 'mark', 'Your Final Exam mark for CS101 - Introduction to Computer Science has been updated. New mark: 100%. Please check your academic dashboard for details.', 1, 2, 0, '2025-07-23 20:26:26'),
(22, 2, 'mark', 'Mark update confirmed for Emma Thompson in CS101 - Final Exam: 100%. Student has been notified automatically.', 1, 2, 0, '2025-07-23 20:26:26');

-- --------------------------------------------------------

--
-- Table structure for table `remark_requests`
--

CREATE TABLE `remark_requests` (
  `id` int(11) NOT NULL,
  `mark_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `justification` text NOT NULL,
  `status` enum('pending','approved','rejected','resolved') DEFAULT 'pending',
  `lecturer_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `action`, `description`, `user_id`, `created_at`) VALUES
(1, 'user_login', 'User login successful', 1, '2025-07-11 18:55:28'),
(2, 'user_create', 'Created new student account: Student Two', 1, '2025-07-11 18:55:28'),
(3, 'backup', 'Database backup initiated', NULL, '2025-07-17 20:56:05'),
(4, 'backup', 'Database backup initiated', NULL, '2025-07-17 20:56:09'),
(5, 'backup', 'Database backup initiated', NULL, '2025-07-17 20:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('lecturer','student','advisor','admin') NOT NULL,
  `matric_number` varchar(20) DEFAULT NULL,
  `pin` varchar(255) DEFAULT NULL,
  `advisor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `matric_number`, `pin`, `advisor_id`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$3Ibm8E0J1OeP3HYzdq8FGeg8c9wufMu2YI2eJ.3OpERcyKkuY5chK', 'admin', NULL, NULL, NULL, '2025-07-11 18:54:58', '2025-07-23 21:39:16'),
(2, 'Lecturer One', 'lecturer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer', NULL, NULL, NULL, '2025-07-11 18:54:58', '2025-07-11 18:54:58'),
(3, 'Advisor One', 'advisor1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'advisor', NULL, NULL, NULL, '2025-07-11 18:54:58', '2025-07-11 18:54:58'),
(10, 'Emma Thompson', 'emma.thompson@university.edu', '$2y$10$GI30Yz0yi1rNtmEPzfwhr.ueLsbzwA6TvQu2zju7vANK9PHe788Ri', 'student', 'CS2023001', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54'),
(11, 'James Rodriguez', 'james.rodriguez@university.edu', '$2y$10$U9B5bMi38EilsT/r8OA8meoXEjrC8L1Vln4bZxP.Wj8bcmimM50aW', 'student', 'CS2023002', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54'),
(12, 'Sarah Chen', 'sarah.chen@university.edu', '$2y$10$xHcXCAACPnkBoxAmVOn46OnCXe..g0tkOkxtkQECeuPNkt6kp76i6', 'student', 'CS2023003', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54'),
(13, 'Michael Johnson', 'michael.johnson@university.edu', '$2y$10$BgSC7Ra0N1qekwky5FrR1uSMYLrPJWZrHTLJiT1ph9Dp/misAFfMy', 'student', 'CS2023004', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54'),
(14, 'Priya Patel', 'priya.patel@university.edu', '$2y$10$gh7mdbphamW/VUoEw0BxsupRMvvFdonj9BXzqRB4I8asm4QjTmAUu', 'student', 'CS2023005', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54'),
(15, 'Ahmed Al-Rashid', 'ahmed.alrashid@university.edu', '$2y$10$syF1JFDbUlJF2GPLBCzTZubERqyUx7xm5fS.QN8fSz6MiMGoov1SS', 'student', 'CS2023006', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54'),
(16, 'Lisa Wang', 'lisa.wang@university.edu', '$2y$10$EatrPJDXIiJBoMkixKznhemQjSPE5Dd6N/hQiIoV782wXnbYw41oi', 'student', 'CS2023007', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54'),
(17, 'Marcus Williams', 'marcus.williams@university.edu', '$2y$10$fR7koWE.iDBcmr0jl1kD9eTA31fI2ZX5yFAiRVku.Rdm1LDkDLpiy', 'student', 'CS2023008', NULL, 3, '2025-07-22 23:56:54', '2025-07-22 23:56:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advisor_notes`
--
ALTER TABLE `advisor_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `advisor_id` (`advisor_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `component_marks`
--
ALTER TABLE `component_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`course_id`,`academic_year`,`semester`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `final_marks`
--
ALTER TABLE `final_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `calculated_by` (`calculated_by`);

--
-- Indexes for table `final_marks_custom`
--
ALTER TABLE `final_marks_custom`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_final_marks_student_course` (`student_id`,`course_id`),
  ADD KEY `idx_final_marks_assignment` (`assignment_mark`),
  ADD KEY `idx_final_marks_quiz` (`quiz_mark`),
  ADD KEY `idx_final_marks_test` (`test_mark`);

--
-- Indexes for table `lecturer_feedback`
--
ALTER TABLE `lecturer_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lecturer_feedback_student` (`student_id`),
  ADD KEY `idx_lecturer_feedback_course` (`course_id`),
  ADD KEY `idx_lecturer_feedback_lecturer` (`lecturer_id`),
  ADD KEY `idx_lecturer_feedback_created` (`created_at`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`assessment_id`),
  ADD KEY `assessment_id` (`assessment_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `remark_requests`
--
ALTER TABLE `remark_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mark_id` (`mark_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `matric_number` (`matric_number`),
  ADD KEY `advisor_id` (`advisor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advisor_notes`
--
ALTER TABLE `advisor_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `component_marks`
--
ALTER TABLE `component_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `final_marks`
--
ALTER TABLE `final_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `final_marks_custom`
--
ALTER TABLE `final_marks_custom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `lecturer_feedback`
--
ALTER TABLE `lecturer_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `remark_requests`
--
ALTER TABLE `remark_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advisor_notes`
--
ALTER TABLE `advisor_notes`
  ADD CONSTRAINT `advisor_notes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `advisor_notes_ibfk_2` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `component_marks`
--
ALTER TABLE `component_marks`
  ADD CONSTRAINT `component_marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `component_marks_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `final_marks`
--
ALTER TABLE `final_marks`
  ADD CONSTRAINT `final_marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `final_marks_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `final_marks_ibfk_3` FOREIGN KEY (`calculated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `final_marks_custom`
--
ALTER TABLE `final_marks_custom`
  ADD CONSTRAINT `final_marks_custom_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `final_marks_custom_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lecturer_feedback`
--
ALTER TABLE `lecturer_feedback`
  ADD CONSTRAINT `lecturer_feedback_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecturer_feedback_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecturer_feedback_ibfk_3` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `remark_requests`
--
ALTER TABLE `remark_requests`
  ADD CONSTRAINT `remark_requests_ibfk_1` FOREIGN KEY (`mark_id`) REFERENCES `marks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `remark_requests_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `remark_requests_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

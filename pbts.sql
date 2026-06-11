-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2026 at 04:34 PM
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
-- Database: `pbts`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `created_by`, `created_at`) VALUES
(1, 'Budget Transparency Portal Launch', 'The new transparency portal is now available for all students.', 1, '2026-06-09 08:47:33'),
(2, 'Leadership Summit Registration', 'Students may now register for the upcoming summit.', 4, '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `created_at`) VALUES
(1, 1, 'Created Budget', 'budgets', 1, '2026-06-09 08:47:33'),
(2, 2, 'Added Expenditure', 'expenditures', 1, '2026-06-09 08:47:33'),
(3, 3, 'Created Project', 'projects', 1, '2026-06-09 08:47:33'),
(4, 4, 'Reviewed Feedback', 'feedback', 1, '2026-06-09 08:47:33'),
(5, 12, 'Deleted User', 'users', 8, '2026-06-09 09:45:31'),
(6, 12, 'Added User', 'users', 13, '2026-06-09 11:49:13'),
(7, 12, 'Deleted User', 'users', 13, '2026-06-09 11:50:00'),
(8, 12, 'Added User', 'users', 14, '2026-06-09 11:50:40'),
(9, 12, 'Deleted User', 'users', 14, '2026-06-09 11:51:46'),
(10, 12, 'Added User', 'users', 15, '2026-06-09 12:21:14'),
(11, 12, 'Deleted User', 'users', 15, '2026-06-09 12:21:24'),
(12, 12, 'Updated User', 'users', 12, '2026-06-09 12:34:56'),
(13, 12, 'Updated User', 'users', 12, '2026-06-09 12:35:02'),
(14, 12, 'Added User', 'users', 16, '2026-06-09 12:44:20'),
(15, 16, 'Added User', 'users', 17, '2026-06-09 12:47:02'),
(16, 12, 'Added User', 'users', 18, '2026-06-11 11:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `budget_id` int(11) NOT NULL,
  `budget_title` varchar(150) DEFAULT NULL,
  `fiscal_year` year(4) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`budget_id`, `budget_title`, `fiscal_year`, `total_amount`, `created_by`, `created_at`) VALUES
(1, 'Student Development Fund', '2025', 500000.00, 2, '2026-06-09 08:47:33'),
(2, 'Campus Improvement Fund', '2025', 800000.00, 2, '2026-06-09 08:47:33'),
(3, 'Research and Innovation Fund', '2025', 350000.00, 2, '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `expenditures`
--

CREATE TABLE `expenditures` (
  `expenditure_id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenditures`
--

INSERT INTO `expenditures` (`expenditure_id`, `budget_id`, `description`, `amount`, `expense_date`, `created_by`, `created_at`) VALUES
(1, 1, 'Leadership Seminar Materials', 25000.00, '2025-04-15', 2, '2026-06-09 08:47:33'),
(2, 1, 'Student Organization Support', 50000.00, '2025-05-01', 2, '2026-06-09 08:47:33'),
(3, 2, 'Library Renovation Supplies', 120000.00, '2025-05-20', 2, '2026-06-09 08:47:33'),
(4, 3, 'Research Equipment Purchase', 80000.00, '2025-06-01', 2, '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Pending','Reviewed','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `message`, `status`, `created_at`) VALUES
(1, 5, 'The WiFi connection in Building A needs improvement.', 'Reviewed', '2026-06-09 08:47:33'),
(2, 6, 'Please provide more study areas in the library.', 'Pending', '2026-06-09 08:47:33'),
(3, 7, 'The student portal is easy to use.', 'Resolved', '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `role_target` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `title`, `message`, `role_target`, `created_at`) VALUES
(1, 'New Budget Posted', 'Fiscal Year 2025 budget has been published.', 'student', '2026-06-09 08:47:33'),
(2, 'Project Approval Needed', 'There are projects waiting for approval.', 'super_admin', '2026-06-09 08:47:33'),
(3, 'Feedback Submitted', 'A new student feedback has been received.', 'student_affairs', '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `project_name` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `allocated_budget` decimal(12,2) DEFAULT NULL,
  `status` enum('Pending','Approved','Ongoing','Completed') DEFAULT 'Pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_name`, `description`, `allocated_budget`, `status`, `start_date`, `end_date`, `created_by`, `created_at`) VALUES
(1, 'Campus WiFi Upgrade', 'Upgrade internet connectivity across campus.', 300000.00, 'Ongoing', '2025-06-01', '2025-09-30', 3, '2026-06-09 08:47:33'),
(2, 'Student Leadership Summit', 'Annual leadership training program.', 150000.00, 'Approved', '2025-07-01', '2025-07-15', 3, '2026-06-09 08:47:33'),
(3, 'Library Modernization', 'Improve library facilities and equipment.', 500000.00, 'Pending', '2025-08-01', '2025-12-31', 3, '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `project_updates`
--

CREATE TABLE `project_updates` (
  `update_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `progress_percent` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_updates`
--

INSERT INTO `project_updates` (`update_id`, `project_id`, `progress_percent`, `remarks`, `created_at`) VALUES
(1, 1, 20, 'Initial network assessment completed.', '2026-06-09 08:47:33'),
(2, 1, 45, 'Procurement of networking equipment ongoing.', '2026-06-09 08:47:33'),
(3, 2, 10, 'Planning committee formed.', '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_title` varchar(255) DEFAULT NULL,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `report_title`, `report_type`, `generated_by`, `created_at`) VALUES
(1, 'Annual Budget Summary 2025', 'Budget Report', 2, '2026-06-09 08:47:33'),
(2, 'Project Progress Report', 'Project Report', 3, '2026-06-09 08:47:33'),
(3, 'Student Feedback Analysis', 'Feedback Report', 4, '2026-06-09 08:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','super_admin','budget_officer','project_coordinator','student_affairs') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'System Administrator', 'admin@pup.edu.ph', '123456', 'super_admin', 'active', '2026-06-09 08:45:10'),
(2, 'Maria Santos', 'budget@pup.edu.ph', '123456', 'budget_officer', 'active', '2026-06-09 08:45:10'),
(3, 'Juan Dela Cruz', 'project@pup.edu.ph', '123456', 'project_coordinator', 'active', '2026-06-09 08:45:10'),
(4, 'Ana Reyes', 'affairs@pup.edu.ph', '123456', 'student_affairs', 'active', '2026-06-09 08:45:10'),
(5, 'Mark Mendoza', 'student1@pup.edu.ph', '123456', 'student', 'active', '2026-06-09 08:45:10'),
(6, 'Jane Cruz', 'student2@pup.edu.ph', '123456', 'student', 'active', '2026-06-09 08:45:10'),
(7, 'Carlo Ramos', 'student3@pup.edu.ph', '123456', 'student', 'active', '2026-06-09 08:45:10'),
(10, 'System Administrator', 'admin1@pup.edu.ph', '$2y$10$Y4D3mS6rWz4Y9xN6j2l7nO7rPq8sTuVwXyZaBcDeFgHiJkLmNoPqS', 'super_admin', 'active', '2026-06-09 09:14:42'),
(12, 'System Administrators', '1admin@pup.edu.ph', '$2y$10$KW/RWAfMUPki7SdYfwGlTuYdq.OxyjAsHLg197LL5O0Sm/r80fIwK', 'super_admin', 'active', '2026-06-09 09:22:00'),
(16, 'jhon marco', '2admin@pup.edu.ph', '$2y$10$RTtzXdPBBC0P0rwHh4LsFuus4We7Ugdzk9qGYHS8wQ/zZ8G4pqByO', 'super_admin', 'active', '2026-06-09 12:44:20'),
(17, 'marga', 'admin3@pup.edu.ph', '$2y$10$TWi7heUkuI6169zB6ZszuOSTmcuxrtY7VT8NLeA4MVEa08YNOExBG', 'super_admin', 'active', '2026-06-09 12:47:02'),
(18, 'marga', 'student@pupstc.edu.ph', '$2y$10$1rSEDgeoq/PbyehEYSXTn.kfKxK9njiUEJUgRebGT96TPJOuxZFNS', 'student', 'active', '2026-06-11 11:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `vote_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`vote_id`, `project_id`, `student_id`, `created_at`) VALUES
(1, 1, 5, '2026-06-09 08:47:33'),
(2, 1, 6, '2026-06-09 08:47:33'),
(3, 2, 5, '2026-06-09 08:47:33'),
(4, 2, 7, '2026-06-09 08:47:33'),
(5, 3, 6, '2026-06-09 08:47:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD PRIMARY KEY (`expenditure_id`),
  ADD KEY `budget_id` (`budget_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `project_updates`
--
ALTER TABLE `project_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `expenditure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_updates`
--
ALTER TABLE `project_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD CONSTRAINT `expenditures_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`),
  ADD CONSTRAINT `expenditures_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `project_updates`
--
ALTER TABLE `project_updates`
  ADD CONSTRAINT `project_updates_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

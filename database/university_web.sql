-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2026 at 05:45 PM
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
-- Database: `university_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `moderation_logs`
--

CREATE TABLE `moderation_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `open_reports`
-- (See below for the actual view)
--
CREATE TABLE `open_reports` (
`report_id` bigint(20) unsigned
,`post_id` bigint(20) unsigned
,`content` text
,`reported_by` varchar(50)
,`reason` text
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `pending_posts`
-- (See below for the actual view)
--
CREATE TABLE `pending_posts` (
`id` bigint(20) unsigned
,`content` text
,`username` varchar(50)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' CHECK (`status` in ('pending','approved','rejected')),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `content`, `status`, `created_at`) VALUES
(1, 2, 'Αυτό είναι το πρώτο μου post και περιμένει έγκριση.', 'approved', '2026-03-17 16:03:27'),
(2, 2, 'Αυτό το post είναι ήδη ορατό στο feed.', 'approved', '2026-03-17 16:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open' CHECK (`status` in ('open','reviewed')),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `post_id`, `reported_by`, `reason`, `status`, `created_at`) VALUES
(1, 2, 1, 'Spam περιεχόμενο', 'open', '2026-03-17 16:03:27');

--
-- Triggers `reports`
--
DELIMITER $$
CREATE TRIGGER `trigger_auto_reject` AFTER INSERT ON `reports` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM reports WHERE post_id = NEW.post_id AND status = 'open') >= 5 THEN
        UPDATE posts SET status = 'rejected' WHERE id = NEW.post_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` varchar(20) NOT NULL CHECK (`role` in ('USER','ADMIN')),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `role`, `created_at`) VALUES
(1, 'Panagiotis_Admin', 'ADMIN', '2026-03-17 16:03:27'),
(2, 'Student_Tester', 'USER', '2026-03-17 16:03:27');

-- --------------------------------------------------------

--
-- Structure for view `open_reports`
--
DROP TABLE IF EXISTS `open_reports`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `open_reports`  AS SELECT `r`.`id` AS `report_id`, `p`.`id` AS `post_id`, `p`.`content` AS `content`, `u`.`username` AS `reported_by`, `r`.`reason` AS `reason`, `r`.`created_at` AS `created_at` FROM ((`reports` `r` join `posts` `p` on(`p`.`id` = `r`.`post_id`)) join `users` `u` on(`u`.`id` = `r`.`reported_by`)) WHERE `r`.`status` = 'open' ;

-- --------------------------------------------------------

--
-- Structure for view `pending_posts`
--
DROP TABLE IF EXISTS `pending_posts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pending_posts`  AS SELECT `p`.`id` AS `id`, `p`.`content` AS `content`, `u`.`username` AS `username`, `p`.`created_at` AS `created_at` FROM (`posts` `p` join `users` `u` on(`u`.`id` = `p`.`user_id`)) WHERE `p`.`status` = 'pending' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_id` (`post_id`,`reported_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

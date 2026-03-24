SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. ΔΟΜΗ ΠΙΝΑΚΩΝ

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`post_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  CONSTRAINT `fk_reports_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. ΔΕΔΟΜΕΝΑ ΔΟΚΙΜΩΝ 

INSERT INTO `users` (`user_id`, `username`, `password`, `email`) VALUES
(1, 'AdminUser', '123456', 'admin@test.com'),
(2, 'Maria_Poly', 'pass123', 'maria@uni.gr'),
(3, 'Kostas_Geo', 'pass123', 'kostas@uni.gr'),
(4, 'Eleni_Dev', 'pass123', 'eleni@uni.gr');

INSERT INTO `posts` (`user_id`, `title`, `content`, `status`) VALUES
(2, 'Σημειώσεις Δικτύων', 'Ανέβασα τις σημειώσεις για το μάθημα των Δικτύων.', 1),
(3, 'Αναβολή Εργασίας', 'Ισχύει η παράταση για το Feature 4;', 1),
(4, 'Update Κώδικα', 'Έκανα push τις αλλαγές στο CSS.', 1),
(1, '[VERIFIED] Πρώτη Δοκιμή', 'Αυτό το post έχει ήδη εγκριθεί.', 2);

-- 3. TRIGGERS 

DELIMITER $$

-- Trigger 1: Αυτόματο [VERIFIED] στον τίτλο 
CREATE TRIGGER `trg_mark_as_verified` BEFORE UPDATE ON `posts` FOR EACH ROW 
BEGIN
    IF NEW.status = 2 AND OLD.status != 2 THEN
        SET NEW.title = CONCAT('[VERIFIED] ', NEW.title);
    END IF;
END$$

-- Trigger 2: Αυτόματη απόρριψη αν ένα post λάβει 5+ reports
CREATE TRIGGER `auto_reject_after_5_reports` AFTER INSERT ON `reports` FOR EACH ROW 
BEGIN
    IF (SELECT COUNT(*) FROM reports WHERE post_id = NEW.post_id) >= 5 THEN
        UPDATE posts SET status = 0 WHERE post_id = NEW.post_id;
    END IF;
END$$

DELIMITER ;
COMMIT;
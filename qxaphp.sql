-- phpMyAdmin SQL Dump
-- version 4.4.15.7
-- http://www.phpmyadmin.net
--
-- MySQL version: 5.6.37
-- PHP version: 7.1.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database `QxAPHP`
--

-- --------------------------------------------------------

--
-- Table `answers`
--

CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `answer` text NOT NULL,
  `added_at` int(11) NOT NULL,
  `modified_at` int(11) DEFAULT NULL,
  `votes` int(11) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `followed_questions`
--

CREATE TABLE IF NOT EXISTS `followed_questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `followed_topics`
--

CREATE TABLE IF NOT EXISTS `followed_topics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `hidden_questions`
--

CREATE TABLE IF NOT EXISTS `hidden_questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `notifications`
-- 'type': "na" - new answer, "nq" - new question, "cq" - closed question, "da" - deleted answer, "dq" - deleted question
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL,
  `to_user` int(11) NOT NULL,
  `question_id` int(11) NOT NULL DEFAULT '0',
  `answer_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(2) NOT NULL,
  `from_user` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `viewed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `url` varchar(50) NOT NULL,
  `added_at` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Pages table';

-- --------------------------------------------------------

--
-- Table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `url` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `added_at` int(11) NOT NULL COMMENT 'timestamp',
  `modified_at` int(11) DEFAULT NULL COMMENT 'timestamp',
  `deleted_at` int(11) DEFAULT NULL COMMENT 'timestamp',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT '++',
  `votes` int(11) NOT NULL DEFAULT '0' COMMENT '++ / --',
  `followers` int(11) NOT NULL DEFAULT '1' COMMENT '++ / --',
  `answers` int(11) NOT NULL DEFAULT '0' COMMENT '++ / --',
  `active` int(11) NOT NULL DEFAULT '1' COMMENT '[0 - deleted | 1 - enabled | 2 - banned]',
  `is_closed` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Questions table';

-- --------------------------------------------------------

--
-- Table `question_images`
--

CREATE TABLE IF NOT EXISTS `question_images` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `image` varchar(64) NOT NULL DEFAULT 'no_image.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `question_topics`
--

CREATE TABLE IF NOT EXISTS `question_topics` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `remembered_logins`
--

CREATE TABLE IF NOT EXISTS `remembered_logins` (
  `token_hash` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires_date` datetime NOT NULL,
  `added_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `reports`
--

CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL,
  `from_user` int(11) NOT NULL,
  `question_id` int(11) NOT NULL DEFAULT '0',
  `answer_id` int(11) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `timestamp` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `app_name` varchar(20) NOT NULL DEFAULT 'QxA PHP',
  `app_version` varchar(10) DEFAULT NULL,
  `app_email` varchar(64) DEFAULT NULL,
  `email_option` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 - PHP mail() | 1 - Mailgun | 2 - PHPMailer',
  `mailgun_api_key` varchar(64) DEFAULT NULL,
  `mailgun_domain` varchar(128) DEFAULT NULL,
  `email_activation` tinyint(1) NOT NULL DEFAULT '0',
  `smtp_host` varchar(64) DEFAULT NULL,
  `smtp_username` varchar(64) DEFAULT NULL,
  `smtp_password` varchar(64) DEFAULT NULL,
  `smtp_secure` varchar(5) DEFAULT 'tls',
  `smtp_port` int(5) DEFAULT '587',
  `tracking_code` text DEFAULT NULL,
  `per_page_admin` int(11) NOT NULL DEFAULT '10',
  `per_page_user` int(11) NOT NULL DEFAULT '10',
  `question_price` int(10) NOT NULL DEFAULT '1',
  `question_max_title` int(3) NOT NULL DEFAULT '200',
  `question_max_images` int(3) NOT NULL DEFAULT '5',
  `question_max_description` int(10) NOT NULL DEFAULT '1000',
  `question_max_topics` int(3) NOT NULL DEFAULT '5',
  `banned_words` text DEFAULT NULL,
  `banner_top` text DEFAULT NULL,
  `banner_top_status` tinyint(1) NOT NULL DEFAULT '0',
  `banner_left` text DEFAULT NULL,
  `banner_left_status` tinyint(1) NOT NULL DEFAULT '0',
  `analytics_code` text DEFAULT NULL,
  `analytics_code_status` tinyint(1) NOT NULL DEFAULT '0',
  `email_notifications` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 - disabled | 1 - enabled (1 notification = 1 email) | 2 - enabled cron (1 email - notifications per n time)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Application settings';

--
-- Inserting in `settings` default settings
--

INSERT INTO `settings` (`app_name`, `app_version`, `app_email`, `email_option`, `mailgun_api_key`, `mailgun_domain`, `email_activation`, `smtp_host`, `smtp_username`, `smtp_password`, `smtp_secure`, `smtp_port`, `tracking_code`, `per_page_admin`, `per_page_user`, `question_price`, `question_max_title`, `question_max_images`, `question_max_description`, `question_max_topics`, `banned_words`, `banner_top`, `banner_top_status`, `banner_left`, `banner_left_status`, `analytics_code`, `analytics_code_status`, `email_notifications`) VALUES
('QxA PHP', '1.0.0', 'admin@qxaphp.com', 0, NULL, NULL, 0, NULL, NULL, NULL, 'tls', 587, NULL, 10, 10, 1, 120, 5, 1000, 5, NULL, NULL, 0, NULL, 0, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Table `topics`
--

CREATE TABLE IF NOT EXISTS `topics` (
  `id` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  `description` text,
  `image` varchar(64) NOT NULL DEFAULT 'topic.png',
  `url` varchar(64) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 - disabled | 1 - enabled',
  `followers` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Topics table';

-- --------------------------------------------------------

--
-- Table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(65) NOT NULL,
  `name` varchar(80) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `signup_stamp` int(11) DEFAULT NULL,
  `signup_ip` varchar(20) DEFAULT NULL,
  `password_reset_hash` varchar(64) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `activation_hash` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Email activation',
  `birth_date` varchar(10) DEFAULT NULL COMMENT 'YYYY-mm-dd',
  `location` varchar(64) DEFAULT NULL,
  `about` text,
  `photo` varchar(64) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 - deleted | 1 - active',
  `points` int(11) NOT NULL DEFAULT '10' COMMENT '++ / --',
  `gender` tinyint(1) DEFAULT NULL COMMENT '1 - male | 2 - female',
  `last_visit` int(11) DEFAULT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `name`, `is_admin`, `signup_stamp`, `signup_ip`, `password_reset_hash`, `password_reset_expires_at`, `activation_hash`, `is_active`, `birth_date`, `location`, `about`, `photo`, `active`, `points`, `gender`, `last_visit`, `email_notifications`) VALUES
(1, 'admin', '$2y$10$qpLBp06H6MFMYbIbILguj.Hu6dv.ptCpYqmjVXJRAkHW4Edhon62G', 'admin@admin.com', 'Admin Account', 1, 1546247631, '127.0.0.1', NULL, NULL, '43a151defe5b46f277f6a2f226141e5ab93d251d65bddd0650ea2e2276e251e0', 1, NULL, NULL, NULL, NULL, 1, 10, NULL, 1546247648, 1);

-- --------------------------------------------------------

--
-- Table `voted_answers`
--

CREATE TABLE IF NOT EXISTS `voted_answers` (
  `id` int(11) NOT NULL,
  `answer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `value` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table `voted_questions`
--

CREATE TABLE IF NOT EXISTS `voted_questions` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `value` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`) USING BTREE;

--
-- Indexes for `followed_questions`
--
ALTER TABLE `followed_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_question` (`user_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for `followed_topics`
--
ALTER TABLE `followed_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for `hidden_questions`
--
ALTER TABLE `hidden_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_question` (`user_id`,`question_id`) USING BTREE,
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`to_user`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `answer_id` (`answer_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `url` (`url`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for `question_images`
--
ALTER TABLE `question_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for `question_topics`
--
ALTER TABLE `question_topics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `question_topic` (`question_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for `remembered_logins`
--
ALTER TABLE `remembered_logins`
  ADD PRIMARY KEY (`token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user` (`from_user`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `answer_id` (`answer_id`);

--
-- Indexes for `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `url` (`url`),
  ADD UNIQUE KEY `image` (`image`);

--
-- Indexes for `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `password_reset_hash` (`password_reset_hash`),
  ADD UNIQUE KEY `activation_hash` (`activation_hash`);

--
-- Indexes for `voted_answers`
--
ALTER TABLE `voted_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `answer_id_index` (`answer_id`) USING BTREE,
  ADD KEY `user_id_index` (`user_id`) USING BTREE;

--
-- Indexes for `voted_questions`
--
ALTER TABLE `voted_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `question_user` (`question_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for таблицы `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for таблицы `followed_questions`
--
ALTER TABLE `followed_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `followed_topics`
--
ALTER TABLE `followed_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `hidden_questions`
--
ALTER TABLE `hidden_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for таблицы `question_images`
--
ALTER TABLE `question_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `question_topics`
--
ALTER TABLE `question_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for таблицы `voted_answers`
--
ALTER TABLE `voted_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for таблицы `voted_questions`
--
ALTER TABLE `voted_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Foreign key constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `followed_questions`
--
ALTER TABLE `followed_questions`
  ADD CONSTRAINT `followed_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `followed_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `followed_topics`
--
ALTER TABLE `followed_topics`
  ADD CONSTRAINT `followed_topics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `followed_topics_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `hidden_questions`
--
ALTER TABLE `hidden_questions`
  ADD CONSTRAINT `hidden_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hidden_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`to_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `question_images`
--
ALTER TABLE `question_images`
  ADD CONSTRAINT `question_images_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `question_topics`
--
ALTER TABLE `question_topics`
  ADD CONSTRAINT `question_topics_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `question_topics_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `remembered_logins`
--
ALTER TABLE `remembered_logins`
  ADD CONSTRAINT `remembered_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `voted_answers`
--
ALTER TABLE `voted_answers`
  ADD CONSTRAINT `voted_answers_ibfk_1` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `voted_answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Foreign key constraints for table `voted_questions`
--
ALTER TABLE `voted_questions`
  ADD CONSTRAINT `voted_questions_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `voted_questions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Adding question and answer id = 0 for deleted questions and answers
--
INSERT INTO `questions`(`id`, `title`, `description`, `url`, `user_id`, `added_at`, `modified_at`, `deleted_at`, `views`, `votes`, `followers`, `answers`, `active`, `is_closed`) VALUES 
(0, '-/-', '-/-', '-/-' ,1 , 1, NULL, NULL, 0, 0, 0, 0, 0, 1);
INSERT INTO `answers`(`id`, `question_id`, `user_id`, `answer`, `added_at`, `modified_at`, `votes`, `active`) VALUES 
(0, 0, 1, '-/-', 1, NULL, 0, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

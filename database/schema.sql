CREATE DATABASE IF NOT EXISTS `movie_tracker` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `movie_tracker`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `User_watchlist`;
DROP TABLE IF EXISTS `Title_Genre`;
DROP TABLE IF EXISTS `Genre`;
DROP TABLE IF EXISTS `Title`;
DROP TABLE IF EXISTS `User`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `User` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `secret_pin` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Title` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `release_year` INT(4) NOT NULL,
    `age_rating` VARCHAR(20) NOT NULL,
    `poster_url` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Genre` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `genre` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Title_Genre` (
    `title_id` INT NOT NULL,
    `genre_id` INT NOT NULL,
    PRIMARY KEY (`title_id`, `genre_id`),
    CONSTRAINT `fk_tg_title` FOREIGN KEY (`title_id`) REFERENCES `Title` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tg_genre` FOREIGN KEY (`genre_id`) REFERENCES `Genre` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `User_watchlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title_id` INT NOT NULL,
    `status` ENUM('watchlist', 'watching', 'finished') NOT NULL DEFAULT 'watchlist',
    `rating` TINYINT NULL,
    `notes` TEXT NULL,
    `finished_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_uw_user` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uw_title` FOREIGN KEY (`title_id`) REFERENCES `Title` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_title` (`user_id`, `title_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Seed Kategori Genre Dasar
INSERT IGNORE INTO `Genre` (`id`, `genre`) VALUES
(1, 'Action'),
(2, 'Sci-Fi'),
(3, 'Drama'),
(4, 'Comedy'),
(5, 'Thriller'),
(6, 'Animation'),
(7, 'Adventure'),
(8, 'Romance'),
(9, 'Horror'),
(10, 'Fantasy'),
(11, 'Mystery'),
(12, 'Crime');
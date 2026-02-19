-- BookHub Database Backup
-- Generated: 2026-02-19 15:11:02
-- Database: bookhub
-- Host: localhost

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `books`;

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `cover_image` varchar(255) DEFAULT NULL,
  `published_year` int(11) DEFAULT NULL,
  `condition_status` enum('new','used','rare') DEFAULT 'new',
  `is_bestseller` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`book_id`),
  UNIQUE KEY `isbn` (`isbn`),
  KEY `idx_title` (`title`),
  KEY `idx_author` (`author`),
  KEY `idx_genre` (`genre`),
  KEY `idx_price` (`price`),
  KEY `idx_isbn` (`isbn`),
  KEY `idx_bestseller` (`is_bestseller`),
  FULLTEXT KEY `idx_search` (`title`,`author`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `books` VALUES
('1', 'Pride and Prejudice', 'Austen, Jane', NULL, NULL, 'fiction', '450.00', '5', 'assets/images/books/fiction/Pride_and_Prejudice.jpg', '1955', 'new', '0', '0', '2026-02-15 14:25:29', '2026-02-15 14:25:29'),
('2', 'A Room with a View', 'Forster, E. M. (Edward Morgan)', NULL, NULL, 'fiction', '613.00', '5', 'assets/images/books/fiction/A_Room_with_a_View.jpg', '1989', 'new', '0', '0', '2026-02-15 14:25:37', '2026-02-15 14:25:37'),
('3', 'The Strange Case of Dr. Jekyll and Mr. Hyde', 'Stevenson, Robert Louis', NULL, NULL, 'fiction', '205.00', '4', 'assets/images/books/fiction/The_Strange_Case_of_Dr__Jekyll_and_Mr__Hyde.jpg', '1938', 'new', '0', '0', '2026-02-15 14:25:48', '2026-02-16 20:31:20'),
('4', 'Alice\'s Adventures in Wonderland', 'Carroll, Lewis', NULL, NULL, 'fiction', '418.00', '1', 'assets/images/books/fiction/Alice_s_Adventures_in_Wonderland.jpg', '2014', 'new', '0', '0', '2026-02-15 14:25:57', '2026-02-15 14:25:57'),
('5', 'Crime and Punishment', 'Dostoyevsky, Fyodor', NULL, NULL, 'fiction', '900.00', '2', 'assets/images/books/fiction/Crime_and_Punishment.jpg', '1979', 'new', '0', '0', '2026-02-15 14:26:02', '2026-02-15 14:26:02'),
('6', 'Little Women; Or, Meg, Jo, Beth, and Amy', 'Alcott, Louisa May', NULL, NULL, 'fiction', '668.00', '3', 'assets/images/books/fiction/Little_Women__Or__Meg__Jo__Beth__and_Amy.jpg', '1939', 'new', '0', '0', '2026-02-15 14:26:08', '2026-02-15 14:26:08'),
('7', 'Jane Eyre: An Autobiography', 'Brontë, Charlotte', NULL, NULL, 'fiction', '453.00', '2', 'assets/images/books/fiction/Jane_Eyre__An_Autobiography.jpg', '1952', 'new', '0', '0', '2026-02-15 14:26:13', '2026-02-15 14:26:13'),
('8', 'The Enchanted April', 'Von Arnim, Elizabeth', NULL, NULL, 'fiction', '914.00', '2', 'assets/images/books/fiction/The_Enchanted_April.jpg', '1945', 'new', '0', '0', '2026-02-15 14:26:30', '2026-02-15 14:26:30'),
('9', 'Wuthering Heights', 'Brontë, Emily', NULL, NULL, 'fiction', '432.00', '5', 'assets/images/books/fiction/Wuthering_Heights.jpg', '1926', 'new', '0', '0', '2026-02-15 14:26:35', '2026-02-15 14:26:35'),
('10', 'The Brothers Karamazov', 'Dostoyevsky, Fyodor', NULL, NULL, 'fiction', '199.00', '0', 'assets/images/books/fiction/The_Brothers_Karamazov.jpg', '1939', 'new', '0', '0', '2026-02-15 14:28:26', '2026-02-15 20:49:16'),
('11', 'The war of the worlds', 'Wells, H. G. (Herbert George)', NULL, NULL, 'science', '750.00', '2', 'assets/images/books/science/The_war_of_the_worlds.jpg', '1995', 'new', '0', '0', '2026-02-15 14:33:48', '2026-02-15 14:33:48'),
('12', 'The Picture of Dorian Gray', 'Wilde, Oscar', NULL, NULL, 'history', '366.00', '2', 'assets/images/books/history/The_Picture_of_Dorian_Gray.jpg', '1984', 'new', '0', '0', '2026-02-15 14:36:12', '2026-02-15 14:36:12'),
('13', 'A Tale of Two Cities', 'Dickens, Charles', NULL, NULL, 'history', '711.00', '2', 'assets/images/books/history/A_Tale_of_Two_Cities.jpg', '1954', 'new', '0', '0', '2026-02-15 14:36:18', '2026-02-15 19:45:03'),
('14', 'The Scarlet Letter', 'Hawthorne, Nathaniel', NULL, NULL, 'history', '308.00', '4', 'assets/images/books/history/The_Scarlet_Letter.jpg', '1996', 'new', '0', '0', '2026-02-15 14:36:24', '2026-02-15 14:36:24'),
('15', 'Leviathan', 'Hobbes, Thomas', NULL, NULL, 'history', '301.00', '3', 'assets/images/books/history/Leviathan.jpg', '1990', 'new', '0', '0', '2026-02-15 14:36:29', '2026-02-15 14:36:29'),
('16', 'Narrative of the Life of Frederick Douglass, an American Slave', 'Douglass, Frederick', NULL, NULL, 'history', '151.00', '1', 'assets/images/books/history/Narrative_of_the_Life_of_Frederick_Douglass__an_American_Slave.jpg', '1927', 'new', '0', '0', '2026-02-15 14:37:26', '2026-02-15 14:37:26'),
('17', 'The Interesting Narrative of the Life of Olaudah Equiano, Or Gustavus Vassa, The African: Written By Himself', 'Equiano, Olaudah', NULL, NULL, 'history', '802.00', '5', 'assets/images/books/history/The_Interesting_Narrative_of_the_Life_of_Olaudah_Equiano__Or_Gustavus_Vassa__The_African__Written_By_Himself.jpg', '1962', 'new', '0', '0', '2026-02-15 14:37:37', '2026-02-15 14:37:37'),
('18', 'Paradise Lost', 'Milton, John', NULL, NULL, 'history', '503.00', '3', 'assets/images/books/history/Paradise_Lost.jpg', '1992', 'new', '0', '0', '2026-02-15 14:37:43', '2026-02-15 14:37:43'),
('19', 'The history of human marriage', 'Westermarck, Edward', NULL, NULL, 'history', '141.00', '4', 'assets/images/books/history/The_history_of_human_marriage.jpg', '1933', 'new', '0', '0', '2026-02-15 14:37:49', '2026-02-15 14:37:49'),
('20', 'The Natural History of Pliny, Volume 2 (of 6)', 'Pliny, the Elder', NULL, NULL, 'history', '310.00', '5', 'assets/images/books/history/The_Natural_History_of_Pliny__Volume_2__of_6_.jpg', '1994', 'new', '0', '0', '2026-02-15 14:37:53', '2026-02-15 14:37:53'),
('21', 'The Reign of Greed', 'Rizal, José', NULL, NULL, 'history', '405.00', '0', 'assets/images/books/history/The_Reign_of_Greed.jpg', '1988', 'new', '0', '0', '2026-02-15 14:37:58', '2026-02-18 20:08:25'),
('22', 'The Travels of Marco Polo — Volume 1', 'Polo, Marco', NULL, NULL, 'history', '684.00', '2', 'assets/images/books/history/The_Travels_of_Marco_Polo_____Volume_1.jpg', '1959', 'new', '0', '0', '2026-02-15 14:38:03', '2026-02-15 14:38:03'),
('23', 'The Legend of Sleepy Hollow', 'Irving, Washington', NULL, NULL, 'history', '117.00', '1', 'assets/images/books/history/The_Legend_of_Sleepy_Hollow.jpg', '1991', 'new', '0', '0', '2026-02-15 14:38:08', '2026-02-15 14:38:08'),
('24', 'An Inquiry into the Nature and Causes of the Wealth of Nations', 'Smith, Adam', NULL, NULL, 'history', '169.00', '3', 'assets/images/books/history/An_Inquiry_into_the_Nature_and_Causes_of_the_Wealth_of_Nations.jpg', '1941', 'new', '0', '0', '2026-02-15 14:38:15', '2026-02-16 20:31:20'),
('25', 'Society in America, Volume 1 (of 2)', 'Martineau, Harriet', NULL, NULL, 'history', '627.00', '1', 'assets/images/books/history/Society_in_America__Volume_1__of_2_.jpg', '1984', 'new', '0', '0', '2026-02-15 14:38:23', '2026-02-15 14:38:23'),
('26', 'Common Sense', 'Paine, Thomas', NULL, NULL, 'history', '879.00', '4', 'assets/images/books/history/Common_Sense.jpg', '1969', 'new', '0', '0', '2026-02-15 14:38:27', '2026-02-15 14:38:27'),
('27', 'Rip Van Winkle', 'Irving, Washington', NULL, NULL, 'history', '136.00', '4', 'assets/images/books/history/Rip_Van_Winkle.jpg', '1944', 'new', '0', '0', '2026-02-15 14:38:32', '2026-02-15 14:38:32'),
('28', 'On War', 'Clausewitz, Carl von', NULL, NULL, 'history', '224.00', '1', 'assets/images/books/history/On_War.jpg', '2010', 'new', '0', '0', '2026-02-15 14:38:36', '2026-02-15 14:38:36'),
('29', 'The evolution of the steam locomotive (1803 to 1898)', 'Nokes, George Augustus', NULL, NULL, 'history', '487.00', '4', 'assets/images/books/history/The_evolution_of_the_steam_locomotive__1803_to_1898_.jpg', '1941', 'new', '0', '0', '2026-02-15 14:38:44', '2026-02-15 14:38:44'),
('30', 'Ek Chihan', 'Hridaya Chandra Singh Pradhan', NULL, 'Ek Chihan नेपाली साहित्यका प्रख्यात लेखक Hridaya Chandra Singh Pradhan द्वारा लिखित एक सामाजिक उपन्यास हो। यस कृतिमा नेपाली समाजका परम्परा, अन्धविश्वास, गरिबी र सामाजिक विभेदका यथार्थ पक्षलाई चित्रण गरिएको छ। उपन्यासले मानव जीवनका संघर्ष, पीडा र परिवर्तनको आवश्यकतालाई मार्मिक ढंगले प्रस्तुत गर्छ।', 'Nepali', '170.00', '0', 'assets/images/books/1771158175_ek-chihan-perfect.png', '1956', 'new', '0', '0', '2026-02-15 18:07:55', '2026-02-15 20:15:36');

DROP TABLE IF EXISTS `cart`;

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  KEY `idx_user_cart` (`user_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `order_items`;

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `book_id` (`book_id`),
  KEY `idx_order_items` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `order_items` VALUES
('1', '1', '21', '1', '457.65'),
('2', '1', '30', '1', '192.10'),
('3', '1', '13', '1', '803.43'),
('4', '2', '30', '2', '192.10'),
('5', '3', '10', '1', '224.87'),
('6', '4', '3', '1', '231.65'),
('7', '4', '24', '1', '190.97'),
('8', '5', '21', '1', '457.65');

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ship_address` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `address_id` (`ship_address`),
  KEY `idx_user_orders` (`user_id`),
  KEY `idx_order_status` (`status`),
  CONSTRAINT `fk_orders_ship` FOREIGN KEY (`ship_address`) REFERENCES `users` (`ship_address`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `orders` VALUES
('1', '2', NULL, '1453.18', 'delivered', 'cash_on_delivery', '2026-02-15 19:45:03', '2026-02-15 20:11:56'),
('2', '2', NULL, '384.20', 'delivered', 'cash_on_delivery', '2026-02-15 20:15:36', '2026-02-15 20:15:55'),
('3', '2', NULL, '224.87', 'shipped', 'card', '2026-02-15 20:49:16', '2026-02-19 19:51:37'),
('4', '2', NULL, '422.62', 'shipped', 'cash_on_delivery', '2026-02-16 20:31:20', '2026-02-19 19:51:35'),
('5', '2', 'Naikap', '457.65', 'shipped', 'cash_on_delivery', '2026-02-18 20:08:25', '2026-02-19 19:51:33');

DROP TABLE IF EXISTS `reviews`;

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT 'Rating from 1 to 5 stars',
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_user_book_review` (`book_id`,`user_id`),
  KEY `book_id` (`book_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_book_fk` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reviews` VALUES
('1', '3', '2', '3', 'Good book. Suggest to read.', '2026-02-16 20:05:43', '2026-02-16 20:05:43');

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `full_name` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `ship_address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `ship_address` (`ship_address`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES
('1', 'admin', 'admin@example.com', '$2y$10$iiiLnWVE6vDlTXQ9tMXwL.R8rX5DOp1xdlzS7DPsxzpzgPLtBLdTq', 'admin', 'Administrator', NULL, NULL, NULL, NULL, NULL, '2026-02-15 18:02:57', '2026-02-18 20:10:50'),
('2', 'sanish', 'sanish@gmail.com', '$2y$10$IOeQlLyTt1bkBoQOY9mVuepEw/0z9tfrWLlBfEpqZGDirxIoShiXy', 'user', 'Sanish Tamang', 'assets/images/profiles/1771158460_wp1961174-fast-furious-6-wallpapers.jpg', '', 'Naikap', '9749410499', 'I am a passionate book enthusiast dedicated to sharing meaningful and inspiring reads. Through my web store, I aim to connect readers with books that educate, motivate, and entertain.', '2026-02-15 18:11:03', '2026-02-18 19:29:55');

DROP TABLE IF EXISTS `wishlist`;

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `unique_wishlist_item` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  KEY `idx_user_wishlist` (`user_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `wishlist` VALUES
('1', '2', '28', '2026-02-15 19:54:02');

SET FOREIGN_KEY_CHECKS=1;

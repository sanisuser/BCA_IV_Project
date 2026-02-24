-- BookHub Database Schema
-- Created: 2026-02-24

CREATE DATABASE IF NOT EXISTS bookhub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE bookhub;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if rebuilding
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

-- USERS TABLE
CREATE TABLE users (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') DEFAULT 'user',
  full_name VARCHAR(100) DEFAULT NULL,
  profile_image VARCHAR(255) DEFAULT NULL,
  location VARCHAR(100) DEFAULT NULL,
  ship_address VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  password_reset_token VARCHAR(64) DEFAULT NULL,
  password_reset_expires DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email (email),
  KEY idx_username (username),
  KEY idx_email (email),
  KEY idx_reset_token (password_reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- BOOKS TABLE
CREATE TABLE books (
  book_id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(100) NOT NULL,
  isbn VARCHAR(20) DEFAULT NULL,
  genre VARCHAR(50) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock INT(11) DEFAULT 0,
  cover_image VARCHAR(255) DEFAULT NULL,
  published_year INT(11) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  condition_status ENUM('new','used') DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (book_id),
  UNIQUE KEY uq_isbn (isbn),
  KEY idx_title (title),
  KEY idx_author (author),
  KEY idx_genre (genre),
  KEY idx_price (price),
  KEY idx_published_year (published_year),
  KEY idx_condition_status (condition_status),
  FULLTEXT KEY idx_search (title, author, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CART TABLE
CREATE TABLE cart (
  cart_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  book_id INT(11) NOT NULL,
  quantity INT(11) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_id),
  UNIQUE KEY unique_cart_item (user_id, book_id),
  KEY idx_user_cart (user_id),
  KEY idx_book_cart (book_id),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_book FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- WISHLIST TABLE
CREATE TABLE wishlist (
  wishlist_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  book_id INT(11) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (wishlist_id),
  UNIQUE KEY unique_wishlist_item (user_id, book_id),
  KEY idx_user_wishlist (user_id),
  KEY idx_book_wishlist (book_id),
  CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_book FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- REVIEWS TABLE
CREATE TABLE reviews (
  review_id INT(11) NOT NULL AUTO_INCREMENT,
  book_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  rating TINYINT(1) NOT NULL COMMENT 'Rating from 1 to 5 stars',
  comment TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  UNIQUE KEY unique_user_book_review (book_id, user_id),
  KEY idx_review_book (book_id),
  KEY idx_review_user (user_id),
  CONSTRAINT fk_reviews_book FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ORDERS TABLE (using ship_address directly - no address_id/user_addresses needed)
CREATE TABLE orders (
  order_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  ship_address TEXT DEFAULT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','shipped','delivered','cancelled') DEFAULT 'pending',
  payment_method VARCHAR(50) DEFAULT NULL,
  admin_remark TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (order_id),
  KEY idx_user_orders (user_id),
  KEY idx_order_status (status),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ORDER ITEMS TABLE
CREATE TABLE order_items (
  item_id INT(11) NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  book_id INT(11) NOT NULL,
  quantity INT(11) NOT NULL,
  price_at_time DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (item_id),
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_book (book_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_book FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

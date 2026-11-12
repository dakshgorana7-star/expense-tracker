-- Expense Tracker with Spending Insights : database schema
CREATE DATABASE IF NOT EXISTS expense_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expense_tracker;

CREATE TABLE IF NOT EXISTS users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(80)  NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  category_id  INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  name         VARCHAR(40) NOT NULL,
  budget_limit DECIMAL(10,2) NULL,              -- optional monthly budget
  UNIQUE KEY uq_user_cat (user_id, name),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
  expense_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  category_id  INT NOT NULL,
  amount       DECIMAL(10,2) NOT NULL CHECK (amount > 0),
  expense_date DATE NOT NULL,
  note         VARCHAR(255) NULL,
  INDEX idx_user_date (user_id, expense_date),
  FOREIGN KEY (user_id)     REFERENCES users(user_id)           ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(category_id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

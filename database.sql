CREATE DATABASE IF NOT EXISTS barangay_kusina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barangay_kusina;

-- Exactly six application tables. Import this file in phpMyAdmin or with the mysql client.
-- Store member accounts; a unique email prevents duplicate registrations.
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(254) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Keep reusable recipe categories and prevent duplicate category names.
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Store recipe details and connect each post to its author and category.
CREATE TABLE IF NOT EXISTS recipes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(140) NOT NULL,
  description VARCHAR(500) NOT NULL,
  instructions TEXT NOT NULL,
  servings TINYINT UNSIGNED NOT NULL DEFAULT 4,
  cook_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  edited_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_recipes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_recipes_category FOREIGN KEY (category_id) REFERENCES categories(id),
  INDEX idx_recipes_latest (created_at, id),
  INDEX idx_recipes_category (category_id)
) ENGINE=InnoDB;

-- Store one ordered ingredient per row so recipes can have different list lengths.
CREATE TABLE IF NOT EXISTS ingredients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT UNSIGNED NOT NULL,
  position SMALLINT UNSIGNED NOT NULL,
  ingredient VARCHAR(180) NOT NULL,
  CONSTRAINT fk_ingredients_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
  INDEX idx_ingredients_recipe (recipe_id, position)
) ENGINE=InnoDB;

-- Attach member feedback to recipes and retain edit timestamps.
CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(1000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  edited_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_comments_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_comments_recipe (recipe_id, created_at)
) ENGINE=InnoDB;

-- Join members to saved recipes; the composite key prevents duplicate saves.
CREATE TABLE IF NOT EXISTS favorites (
  user_id INT UNSIGNED NOT NULL,
  recipe_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, recipe_id),
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
  INDEX idx_favorites_recipe (recipe_id)
) ENGINE=InnoDB;

-- Seed Filipino home-cooking categories without duplicating existing rows.
INSERT IGNORE INTO categories (name) VALUES
('Ulam'), ('Merienda'), ('Meryenda at Kakanin'), ('Panghimagas'), ('Sabaw'), ('Pang-agahan'), ('Inumin');

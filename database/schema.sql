DROP TABLE IF EXISTS user_interest;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS advertisements;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS followers;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;


CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  university VARCHAR(100) DEFAULT NULL,
  year VARCHAR(20) DEFAULT NULL,
  department VARCHAR(100) DEFAULT NULL,
  token_balance INT DEFAULT 0,
  referral_code VARCHAR(20) UNIQUE,
  referred_by INT DEFAULT NULL,
  reset_token VARCHAR(255) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  CONSTRAINT fk_users_referred_by
    FOREIGN KEY (referred_by)
    REFERENCES users(user_id)
    ON DELETE SET NULL
);


CREATE TABLE categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT DEFAULT NULL
);


CREATE TABLE posts (
  post_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  title VARCHAR(200) NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  status TINYINT(1) DEFAULT 1,
  category_id INT DEFAULT NULL,
  CONSTRAINT fk_posts_user
    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_posts_category
    FOREIGN KEY (category_id)
    REFERENCES categories(category_id)
    ON DELETE SET NULL
);


CREATE TABLE comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  post_id INT NOT NULL,
  comment_content TEXT NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_user
    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_comments_post
    FOREIGN KEY (post_id)
    REFERENCES posts(post_id)
    ON DELETE CASCADE
);


CREATE TABLE followers (
  follower_id INT NOT NULL,
  followed_id INT NOT NULL,
  status TINYINT(1) DEFAULT 1,
  PRIMARY KEY (follower_id, followed_id),
  CONSTRAINT fk_followers_follower
    FOREIGN KEY (follower_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_followers_followed
    FOREIGN KEY (followed_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE
);


CREATE TABLE advertisements (
  advertise_id INT AUTO_INCREMENT PRIMARY KEY,
  time_duration DOUBLE DEFAULT NULL,
  last_time_used DATETIME DEFAULT NULL
);


CREATE TABLE attachments (
  attachment_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  file_name VARCHAR(255) DEFAULT NULL,
  file_size INT DEFAULT NULL,
  file_type VARCHAR(100) DEFAULT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attachments_post
    FOREIGN KEY (post_id)
    REFERENCES posts(post_id)
    ON DELETE CASCADE
);


CREATE TABLE transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_charge INT NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_transactions_user
    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE
);


CREATE TABLE user_interest (
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (user_id, category_id),
  CONSTRAINT fk_user_interest_user
    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_user_interest_category
    FOREIGN KEY (category_id)
    REFERENCES categories(category_id)
    ON DELETE CASCADE
);

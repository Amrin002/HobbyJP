#!/usr/bin/env php
<?php
// Seeder script for HobbyJP
// Usage: php scripts/seed_db.php --force

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from CLI.\n";
    exit(1);
}

$args = $argv;
array_shift($args); // remove script name
$force = in_array('--force', $args, true);

if (!$force) {
    echo "WARNING: This will DROP existing tables and recreate sample data.\n";
    echo "Run with --force to proceed:\n    php scripts/seed_db.php --force\n";
    exit(1);
}

// Load database connection
$koneksiPath = __DIR__ . '/../include/koneksi.php';
if (!file_exists($koneksiPath)) {
    echo "Cannot find include/koneksi.php. Please run this script from project root.\n";
    exit(1);
}
include $koneksiPath;

echo "Seeding database...\n";

$sql = <<<'SQL'
-- Hobby App Database Schema
-- Drop tables if exists (untuk development)
DROP TABLE IF EXISTS content_stats;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS history;
DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS episodes;
DROP TABLE IF EXISTS contents;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id_user INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create categories table
CREATE TABLE categories (
    id_category INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    INDEX idx_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create contents table
CREATE TABLE contents (
    id_content INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_category INT(11) NOT NULL,
    title VARCHAR(150) NOT NULL,
    cover VARCHAR(255),
    synopsis TEXT,
    status ENUM('ONGOING', 'COMPLETED') DEFAULT 'ONGOING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_category) REFERENCES categories(id_category) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_title (title),
    INDEX idx_status (status),
    INDEX idx_category (id_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create episodes table
CREATE TABLE episodes (
    id_episode INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_content INT(11) NOT NULL,
    episode_number VARCHAR(20) NOT NULL,
    title VARCHAR(150) NOT NULL,
    content LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_content) REFERENCES contents(id_content) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_content (id_content),
    INDEX idx_episode_number (episode_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create bookmarks table
CREATE TABLE bookmarks (
    id_bookmark INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_user INT(11) NOT NULL,
    id_content INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_content) REFERENCES contents(id_content) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_bookmark (id_user, id_content),
    INDEX idx_user (id_user),
    INDEX idx_content (id_content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create history table
CREATE TABLE history (
    id_history INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_user INT(11) NOT NULL,
    id_episode INT(11) NOT NULL,
    last_read TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_episode) REFERENCES episodes(id_episode) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_history (id_user, id_episode),
    INDEX idx_user (id_user),
    INDEX idx_episode (id_episode),
    INDEX idx_last_read (last_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create ratings table
CREATE TABLE ratings (
    id_rating INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_user INT(11) NOT NULL,
    id_content INT(11) NOT NULL,
    rating INT(11) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_content) REFERENCES contents(id_content) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_rating (id_user, id_content),
    INDEX idx_user (id_user),
    INDEX idx_content (id_content),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create content_stats table
CREATE TABLE content_stats (
    id_stat INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_content INT(11) NOT NULL UNIQUE,
    total_episode INT(11) DEFAULT 0,
    total_bookmark INT(11) DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    view_count INT(11) DEFAULT 0,
    FOREIGN KEY (id_content) REFERENCES contents(id_content) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_content (id_content),
    INDEX idx_average_rating (average_rating),
    INDEX idx_view_count (view_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for categories
INSERT INTO categories (category_name) VALUES
('Manga'),
('Manhwa'),
('Manhua'),
('Novel'),
('Webtoon');

-- Insert sample users
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- password: password
('user', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'); -- password: password

-- Insert sample content
INSERT INTO contents (id_category, title, cover, synopsis, status) VALUES
(1, 'One Piece', 'onepiece.jpg', 'Petualangan Monkey D. Luffy mencari harta karun legendaris One Piece', 'ONGOING'),
(2, 'Solo Leveling', 'sololeveling.jpg', 'Sung Jin-Woo berubah dari hunter terlemah menjadi terkuat', 'COMPLETED');

-- Insert sample episodes
INSERT INTO episodes (id_content, episode_number, title, content) VALUES
(1, '1', 'Romance Dawn', 'Awal petualangan Luffy...'),
(1, '2', 'Enter Zoro', 'Luffy bertemu Roronoa Zoro...'),
(2, '1', 'The Weakest Hunter', 'Jin-Woo masuk dungeon rank E...');

-- Insert sample bookmark
INSERT INTO bookmarks (id_user, id_content) VALUES
(1, 1),
(1, 2);

-- Insert sample history
INSERT INTO history (id_user, id_episode) VALUES
(1, 1),
(1, 3);

-- Insert sample rating
INSERT INTO ratings (id_user, id_content, rating) VALUES
(1, 1, 5),
(1, 2, 5);

-- Initialize content_stats
INSERT INTO content_stats (id_content, total_episode, total_bookmark, average_rating, view_count)
SELECT 
    c.id_content,
    COUNT(DISTINCT e.id_episode) as total_episode,
    COUNT(DISTINCT b.id_bookmark) as total_bookmark,
    COALESCE(AVG(r.rating), 0) as average_rating,
    0 as view_count
FROM contents c
LEFT JOIN episodes e ON c.id_content = e.id_content
LEFT JOIN bookmarks b ON c.id_content = b.id_content
LEFT JOIN ratings r ON c.id_content = r.id_content
GROUP BY c.id_content;
SQL;

// Execute multi query
if (mysqli_multi_query($conn, $sql)) {
    // flush multi queries
    do {
        if ($res = mysqli_store_result($conn)) {
            mysqli_free_result($res);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));

    echo "Database seeded successfully.\n";
    exit(0);
} else {
    echo "Error seeding database: " . mysqli_error($conn) . "\n";
    exit(1);
}

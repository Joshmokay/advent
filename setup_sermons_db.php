<?php
// setup_sermons_db.php — Script to set up the sermons tables
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    
    echo "Creating sermon_categories table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS sermon_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $db->query("SELECT COUNT(*) FROM sermon_categories");
    if ($stmt->fetchColumn() == 0) {
        $categories = [
            'Sabbath Sermons', 'Prophecy', 'Revival', 'Youth Sermons', 
            'Family Life', 'Health Message', 'End Time Messages', 
            'Evangelism', 'Faith & Prayer', 'Bible Study'
        ];
        $insert = $db->prepare("INSERT INTO sermon_categories (name) VALUES (?)");
        foreach ($categories as $cat) {
            $insert->execute([$cat]);
        }
        echo "Inserted default sermon categories.\n";
    }

    echo "Creating written_sermons table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS written_sermons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NULL,
            title VARCHAR(255) NOT NULL,
            preacher VARCHAR(100) NOT NULL,
            description TEXT,
            content LONGTEXT,
            cover_image VARCHAR(255),
            is_featured BOOLEAN DEFAULT FALSE,
            is_trending BOOLEAN DEFAULT FALSE,
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES sermon_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Creating video_sermons table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS video_sermons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NULL,
            title VARCHAR(255) NOT NULL,
            preacher VARCHAR(100) NOT NULL,
            youtube_id VARCHAR(50) NOT NULL,
            duration VARCHAR(20),
            is_featured BOOLEAN DEFAULT FALSE,
            is_trending BOOLEAN DEFAULT FALSE,
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES sermon_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Ensure upload directories exist
    $dirs = [
        __DIR__ . '/uploads/sermons',
        __DIR__ . '/uploads/sermons/covers',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    echo "Sermons database setup completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

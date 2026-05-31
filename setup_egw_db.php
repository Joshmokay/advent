<?php
// setup_egw_db.php — Script to set up the database and all necessary tables
declare(strict_types=1);

try {
    // Connect without DB name to create it first
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "Creating database advent_library if it doesn't exist...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS advent_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE advent_library;");
    
    // Create main tables for index.php so it doesn't break
    echo "Creating main tables...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            author VARCHAR(255),
            category VARCHAR(100),
            description TEXT,
            cover_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS devotions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            verse VARCHAR(255),
            content TEXT,
            devotion_date DATE
        );
        CREATE TABLE IF NOT EXISTS sermons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            preacher VARCHAR(255),
            topic VARCHAR(255),
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    echo "Creating egw_categories table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS egw_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL,
            description TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "Creating egw_books table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS egw_books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            cover_image VARCHAR(255),
            pdf_file VARCHAR(255),
            author VARCHAR(100) DEFAULT 'Ellen G. White',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES egw_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Insert default categories if none exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM egw_categories");
    if ($stmt->fetchColumn() == 0) {
        echo "Inserting default categories...\n";
        $categories = [
            'Christian Living', 'Prophecy', 'Health and Wellness', 'Education',
            'Family and Marriage', 'Prayer and Devotion', 'Faith and Salvation',
            'Last Day Events', 'Spiritual Growth', 'Leadership', 'Mission and Evangelism',
            'Youth Guidance', 'Bible Commentary', 'Church History', 'Character Development'
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO egw_categories (category_name) VALUES (:name)");
        foreach ($categories as $cat) {
            $insertStmt->execute([':name' => $cat]);
        }
    }
    
    // Create necessary directories
    $dirs = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/egw',
        __DIR__ . '/uploads/egw/books',
        __DIR__ . '/uploads/egw/covers',
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "Created directory: $dir\n";
        }
    }

    echo "Database setup completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

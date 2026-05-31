<?php
// setup_admin_db.php — Script to set up the admin dashboard tables and alter existing ones
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    
    echo "Creating admins table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert default admin if not exists
    $stmt = $db->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO admins (username, email, password_hash) VALUES ('admin', 'admin@adventlibrary.org', '$hash')");
        echo "Created default admin (admin / admin123)\n";
    }
    
    echo "Creating users table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user', 'editor') DEFAULT 'user',
            status ENUM('active', 'suspended') DEFAULT 'active',
            joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "Creating categories table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Creating downloads table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS downloads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            book_id INT NOT NULL,
            download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Creating announcements table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Creating quotes table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_text TEXT NOT NULL,
            author VARCHAR(100) NOT NULL,
            source VARCHAR(255),
            is_featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "Creating site_settings table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert default site settings
    $settings = [
        ['hero_title', 'Welcome to Advent Library'],
        ['hero_subtitle', 'A sacred space for spiritual growth — explore over 1,000 Christian books, daily devotions, and written sermons grounded in Adventist faith.'],
        ['contact_email', 'info@adventlibrary.org'],
        ['contact_phone', '+254 700 000 000'],
        ['contact_address', 'Adventist Church Campus, Nairobi, Kenya']
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    echo "Altering books table...\n";
    // Check if columns exist before adding them to avoid errors on multiple runs
    try {
        $db->exec("ALTER TABLE books ADD COLUMN category_id INT NULL");
        $db->exec("ALTER TABLE books ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
    } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE books ADD COLUMN pdf_file VARCHAR(255)"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE books ADD COLUMN is_featured BOOLEAN DEFAULT FALSE"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE books ADD COLUMN download_count INT DEFAULT 0"); } catch (Exception $e) {}
    
    // Create admin directory if it doesn't exist
    $adminDir = __DIR__ . '/admin';
    $adminIncludesDir = __DIR__ . '/admin/includes';
    if (!is_dir($adminDir)) mkdir($adminDir, 0755, true);
    if (!is_dir($adminIncludesDir)) mkdir($adminIncludesDir, 0755, true);
    
    // Ensure all upload directories exist
    $dirs = [
        __DIR__ . '/uploads/books',
        __DIR__ . '/uploads/covers',
        __DIR__ . '/uploads/banners',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    echo "Admin database setup completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

// ==========================
// CHANGE THESE
// ==========================
$username = 'SHARON';
$password = 'MD01*jklRE%12';
// ==========================

try {

    $db = getDB();

    // Check if username already exists
    $check = $db->prepare("SELECT id FROM admins WHERE username = :username");
    $check->execute([
        ':username' => $username
    ]);

    if ($check->fetch()) {
        exit('Username already exists.');
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert admin
    $stmt = $db->prepare("
        INSERT INTO admins (username, password_hash)
        VALUES (:username, :password_hash)
    ");

    $stmt->execute([
        ':username'      => $username,
        ':password_hash' => $passwordHash
    ]);

    echo "Admin created successfully.";

} catch (Exception $e) {

    echo "Error: " . $e->getMessage();
}
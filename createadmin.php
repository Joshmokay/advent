<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';


$username = 'SHARON';
$password = 'MD01*jklRE%12';

try {

    $db = getDB();

    $check = $db->prepare("SELECT id FROM admins WHERE username = :username");
    $check->execute([
        ':username' => $username
    ]);

    if ($check->fetch()) {
        exit('Username already exists.');
    }


    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

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

<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/config.php';

$pdo = db();

$email = 'admin@example.com';
$password = 'ChangeMe123!';

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$existing = $stmt->fetchColumn();

if ($existing) {
    echo "Admin user already exists.\n";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => PASSWORD_COST]);

$stmt = $pdo->prepare('INSERT INTO users (full_name, email, role, password_hash) VALUES (:full_name, :email, :role, :password_hash)');
$stmt->execute([
    'full_name' => 'System Admin',
    'email' => $email,
    'role' => 'Admin',
    'password_hash' => $hash,
]);

echo "Admin user created. Email: {$email}, Password: {$password}\n";

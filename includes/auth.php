<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Ensures the session is active.
 */
function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Attempts to authenticate the user with the provided credentials.
 */
function authenticate(string $email, string $password): bool
{
    $sql = 'SELECT id, client_id, role, password_hash, full_name FROM users WHERE email = :email LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    ensure_session_started();
    $_SESSION['user'] = [
        'id'        => (int)$user['id'],
        'client_id' => $user['client_id'] !== null ? (int)$user['client_id'] : null,
        'role'      => $user['role'],
        'full_name' => $user['full_name'],
    ];

    return true;
}

/**
 * Fetches the authenticated user or null.
 */
function current_user(): ?array
{
    ensure_session_started();
    return $_SESSION['user'] ?? null;
}

/**
 * Throws a 302 redirect to login if the request is unauthenticated.
 */
function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: /index.php');
        exit();
    }

    return $user;
}

/**
 * Removes the current session.
 */
function logout(): void
{
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Simple role guard helper.
 */
function ensure_role(array $allowedRoles): void
{
    $user = require_auth();
    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        echo 'Access denied';
        exit();
    }
}

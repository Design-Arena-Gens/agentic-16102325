<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Retrieve client ID scoped to current session.
 */
function current_client_id(): ?int
{
    $user = current_user();
    if ($user === null) {
        return null;
    }

    if ($user['client_id'] !== null) {
        return (int)$user['client_id'];
    }

    ensure_session_started();
    return isset($_SESSION['active_client_id']) ? (int)$_SESSION['active_client_id'] : null;
}

/**
 * Determines if current user is platform admin.
 */
function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'Admin';
}

/**
 * Safely fetches an integer from request data.
 */
function request_int(string $key, array $source = []): ?int
{
    $src = $source ?: $_REQUEST;
    if (!isset($src[$key]) || !is_numeric($src[$key])) {
        return null;
    }
    return (int)$src[$key];
}

/**
 * Recursively escape output for HTML contexts.
 */
function esc(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ensures directory exists prior to file writes.
 */
function ensure_directory(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

/**
 * Compresses an uploaded image to the DPR store.
 */
function compress_and_store_photo(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('File too large. Max 5MB allowed.');
    }

    $mimeType = mime_content_type($file['tmp_name']);
    $supported = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $supported, true)) {
        throw new RuntimeException('Unsupported image format.');
    }

    ensure_directory(DPR_PHOTO_PATH);

    $imageResource = match ($mimeType) {
        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/webp' => imagecreatefromwebp($file['tmp_name']),
        default      => null,
    };

    if (!$imageResource) {
        throw new RuntimeException('Unable to process uploaded image.');
    }

    $width  = imagesx($imageResource);
    $height = imagesy($imageResource);
    $maxDimension = 1600;

    if ($width > $maxDimension || $height > $maxDimension) {
        $ratio = min($maxDimension / $width, $maxDimension / $height);
        $newWidth  = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($imageResource);
        $imageResource = $resized;
    }

    $fileName = uniqid('dpr_', true) . '.jpg';
    $destination = DPR_PHOTO_PATH . '/' . $fileName;

    if (!imagejpeg($imageResource, $destination, COMPRESSED_IMAGE_QUALITY)) {
        imagedestroy($imageResource);
        throw new RuntimeException('Failed to store image.');
    }

    imagedestroy($imageResource);
    return 'dpr_photos/' . $fileName;
}

/**
 * Sends JSON response with proper headers.
 */
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
}

/**
 * Persists an activity record.
 */
function log_activity(?int $clientId, string $module, string $description, int $userId): void
{
    $stmt = db()->prepare('INSERT INTO activity_log (client_id, module, description, user_id) VALUES (:client_id, :module, :description, :user_id)');
    $stmt->execute([
        'client_id'   => $clientId,
        'module'      => $module,
        'description' => $description,
        'user_id'     => $userId,
    ]);
}

/**
 * Ensures module exists and role is allowed.
 */
function assert_module_access(string $moduleKey, array $moduleDefinition): void
{
    $user = require_auth();
    if (!in_array($user['role'], $moduleDefinition['roles'], true)) {
        json_response(['error' => 'Access denied.'], 403);
    }
}

/**
 * Updates active client workspace for platform admins.
 */
function set_active_client(?int $clientId): void
{
    ensure_session_started();
    if ($clientId === null) {
        unset($_SESSION['active_client_id']);
        return;
    }
    $_SESSION['active_client_id'] = $clientId;
}

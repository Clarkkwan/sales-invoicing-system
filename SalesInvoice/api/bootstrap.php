<?php
// Shared setup for all API files: JSON headers, login check and database connection

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only accept JSON POSTs (blocks forged form posts from other sites)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0
) {
    http_response_code(415);
    echo json_encode(['success' => false, 'error' => 'Content-Type must be application/json']);
    exit;
}

require_once __DIR__ . '/../db_config.php';

// Admins only (role is re-checked in the database)
function requireAdmin(PDO $pdo): void {
    $stmt = $pdo->prepare("SELECT role, active FROM users WHERE id = ?");
    $stmt->execute([(int)($_SESSION['user_id'] ?? 0)]);
    $user = $stmt->fetch();

    if (!$user || !$user['active'] || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
}

// Log the real error; only show details when APP_DEBUG is on
function publicError(string $message, Throwable $e): string {
    error_log($message . ': ' . $e->getMessage());
    return APP_DEBUG ? $message . ': ' . $e->getMessage() : $message;
}

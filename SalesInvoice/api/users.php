<?php
// User management API (admins only)

require_once __DIR__ . '/bootstrap.php';

requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("
        SELECT id, name, username, email, phone, role, active, created_at
        FROM users
        ORDER BY name ASC
    ")->fetchAll();
    foreach ($rows as &$r) {
        $r['id']     = (int)$r['id'];
        $r['active'] = (int)$r['active'];
    }
    echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$action = $body['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    if ($id === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
        exit;
    }

    // Prevent deleting the last admin
    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1")->fetchColumn();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['role'] === 'admin' && $adminCount <= 1) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete the last admin user']);
        exit;
    }

    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'save') {
    $id       = (int)($body['id'] ?? 0);
    $name     = trim((string)($body['name']     ?? ''));
    $username = trim((string)($body['username'] ?? ''));
    $email    = trim((string)($body['email']    ?? ''));
    $phone    = trim((string)($body['phone']    ?? ''));
    $role     = in_array($body['role'] ?? '', ['admin', 'cashier']) ? $body['role'] : 'cashier';
    $password = (string)($body['password'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Name is required']);
        exit;
    }
    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => 'Username is required']);
        exit;
    }

    $usernameCheck = $pdo->prepare("SELECT id FROM users WHERE username=? AND id!=?");
    $usernameCheck->execute([$username, $id]);
    if ($usernameCheck->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Username already taken']);
        exit;
    }

    if ($id > 0) {
        // Don't allow demoting the last admin
        $stmt = $pdo->prepare("SELECT role, active FROM users WHERE id=?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        if ($current && $current['role'] === 'admin' && $current['active'] && $role !== 'admin') {
            $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1")->fetchColumn();
            if ($adminCount <= 1) {
                echo json_encode(['success' => false, 'error' => 'Cannot remove the admin role from the last admin user']);
                exit;
            }
        }

        // Blank password = keep the current one
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("
                UPDATE users SET name = ?, username = ?, email = ?, phone = ?, role = ?, password_hash = ?
                WHERE id = ?
            ")->execute([$name, $username, $email, $phone, $role, $hash, $id]);
        } else {
            $pdo->prepare("
                UPDATE users SET name = ?, username = ?, email = ?, phone = ?, role = ?
                WHERE id = ?
            ")->execute([$name, $username, $email, $phone, $role, $id]);
        }

        // Update the session when users edit their own account
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['username']  = $username;
            $_SESSION['user_name'] = $name;
            $_SESSION['role']      = $role;
        }
    } else {
        if (empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Password is required for new users']);
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (name, username, email, phone, role, password_hash)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$name, $username, $email, $phone, $role, $hash]);
        $id = (int)$pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action']);

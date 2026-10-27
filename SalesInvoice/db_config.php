<?php
// Settings and database connection. Values come from .env (see .env.example) or XAMPP's defaults.

// Read a setting: environment variable first, then .env, then the default
function env(string $key, string $default = ''): string {
    static $fileVars = null;

    if ($fileVars === null) {
        $fileVars = [];
        $envFile  = __DIR__ . '/.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                // Remove surrounding quotes
                $quoted = strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'");
                if ($quoted && $value[-1] === $value[0]) {
                    $value = substr($value, 1, -1);
                }
                $fileVars[$name] = $value;
            }
        }
    }

    $value = getenv($key);
    if ($value !== false) return $value;
    return $fileVars[$key] ?? $default;
}

define('DB_HOST',    env('DB_HOST', 'localhost'));
define('DB_PORT',    env('DB_PORT', '3306'));
define('DB_NAME',    env('DB_NAME', 'salesinvoice'));
define('DB_USER',    env('DB_USER', 'root'));
define('DB_PASS',    env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');
define('APP_DEBUG',  filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    // Only show the real error when APP_DEBUG is on
    error_log('Database connection failed: ' . $e->getMessage());
    $detail = APP_DEBUG ? $e->getMessage() : 'Unable to connect to the database.';

    // JSON for the command line and API calls, HTML for pages
    if (
        php_sapi_name() === 'cli'
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    ) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Database connection failed: ' . $detail]));
    }
    http_response_code(500);
    die('<div style="font-family:monospace;padding:24px;background:#0a0a0c;color:#ef4444;border:2px solid #ef4444;margin:24px;">
        <strong>DB CONNECTION ERROR</strong><br>' . htmlspecialchars($detail) . '
        <br><br><small>Check the database settings in <code>.env</code> and ensure MySQL is running.</small>
    </div>');
}

// Next POS invoice number, e.g. SBC-2026-0042
function getNextInvoiceNo(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT invoice_no FROM sales WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["SBC-{$year}-%"]);
    $last = $stmt->fetchColumn();

    if ($last && preg_match('/(\d{4})$/', $last, $m)) {
        $seq = (int)$m[1] + 1;
    } else {
        $seq = 1;
    }

    return 'SBC-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

<?php
// ---- Database settings (XAMPP defaults) ----
const DB_HOST = 'localhost';
const DB_NAME = 'expense_tracker';
const DB_USER = 'root';
const DB_PASS = '';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

/** Single shared PDO connection. Prepared statements only (prevents SQL injection). */
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $ex) {   // friendly message instead of a raw stack trace
            $msg = 'Cannot connect to the database. Start MySQL, import database.sql and check config.php.';
            if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) json_out(['error' => $msg], 500);
            http_response_code(500); die('<p style="font-family:sans-serif;padding:2rem">' . $msg . '</p>');
        }
    }
    return $pdo;
}

/** String length that works with or without the mbstring extension. */
function len(string $s): int { return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s); }

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** CSRF token stored in the session; sent by forms (field) and fetch calls (header). */
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_ok(): bool {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return hash_equals(csrf(), (string)$t);
}

/** Page guard: redirect visitors who are not logged in. */
function require_login(): void {
    if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/** API guard: returns the logged-in user id, checks CSRF on write requests. */
function api_user(): int {
    if (empty($_SESSION['user_id'])) json_out(['error' => 'Please log in again.'], 401);
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !csrf_ok()) json_out(['error' => 'Security token expired. Refresh the page.'], 419);
    return (int)$_SESSION['user_id'];
}

function body_json(): array {
    $d = json_decode(file_get_contents('php://input'), true);
    return is_array($d) ? $d : [];
}

function default_categories(int $uid): void {
    $st = db()->prepare('INSERT IGNORE INTO categories (user_id, name, budget_limit) VALUES (?,?,?)');
    foreach ([['Food', 6000], ['Transport', 2000], ['Shopping', 3000], ['Bills', 4000], ['Entertainment', 1500], ['Other', null]] as [$n, $b]) $st->execute([$uid, $n, $b]);
}

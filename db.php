<?php
/**
 * Mero Sahayata - Dual Database Adapter (MySQL + SQLite Fallback)
 * BCA 6th Semester Project
 */

// Report errors during development
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set default driver: 'auto' (tries MySQL, falls back to SQLite), 'mysql', or 'sqlite'
define('DB_DRIVER', 'auto'); 

// MySQL Configuration (Standard XAMPP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mero_sahayata');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// SQLite Configuration (Zero configuration fallback)
define('SQLITE_FILE', __DIR__ . '/../database/mero_sahayata.sqlite');

class Database {
    private static ?PDO $instance = null;
    private static string $activeDriver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            // 1. If explicit SQLite
            if (DB_DRIVER === 'sqlite') {
                self::connectSqlite($options);
                return self::$instance;
            }

            // 2. Try MySQL first
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$activeDriver = 'mysql';
            } catch (PDOException $e) {
                // If auto-mode or socket error / missing db, fall back to SQLite
                if (DB_DRIVER === 'auto' || DB_DRIVER === 'sqlite') {
                    self::connectSqlite($options);
                } else {
                    die(json_encode([
                        'success' => false,
                        'error' => 'MySQL Connection failed: ' . $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE));
                }
            }
        }
        return self::$instance;
    }

    private static function connectSqlite(array $options): void {
        $sqlitePath = SQLITE_FILE;
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, $options);
        self::$instance->exec("PRAGMA foreign_keys = ON;");
        self::$activeDriver = 'sqlite';
    }

    public static function getDriver(): string {
        return self::$activeDriver;
    }
}

/**
 * Start session safely ensuring writable session storage
 */
function startSessionSafely(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $sessDir = __DIR__ . '/../sessions';
        if (!is_dir($sessDir)) {
            @mkdir($sessDir, 0777, true);
        }
        if (is_dir($sessDir) && is_writable($sessDir)) {
            @session_save_path($sessDir);
        }
        @session_start();
    }
}

/**
 * Helper to safely sanitize HTML strings
 */
function sanitize(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Return JSON response and terminate
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

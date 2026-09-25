<?php
/**
 * Database configuration and PDO singleton.
 * Copy config/database.php.example to config/database.php and fill in your credentials.
 */

require_once __DIR__ . '/app.php';

// Server-only credentials (e.g. Hostinger) live in database.local.php, which is never uploaded from local.
if (is_file(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
}

// Local XAMPP defaults, used when database.local.php does not define a value.
defined('DB_HOST')    || define('DB_HOST', 'localhost');
defined('DB_PORT')    || define('DB_PORT', '3306');
defined('DB_NAME')    || define('DB_NAME', 'agrilink');
defined('DB_USER')    || define('DB_USER', 'root');
defined('DB_PASS')    || define('DB_PASS', '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Never expose credentials in production
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                exit('Service temporarily unavailable.');
            }
        }

        return self::$instance;
    }

    private function __construct()
    {
    }
    private function __clone()
    {
    }
}
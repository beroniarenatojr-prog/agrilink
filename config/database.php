<?php
/**
 * Database configuration and PDO singleton.
 * Copy config/database.php.example to config/database.php and fill in your credentials.
 */

require_once __DIR__ . '/app.php';

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'agrilink');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

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
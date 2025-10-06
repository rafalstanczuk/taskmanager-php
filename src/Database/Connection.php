<?php declare(strict_types=1);

namespace App\Database;

use PDO;
use function App\env;

/**
 * Creates a singleton PDO connection to PostgreSQL using environment variables.
 * Expected env vars: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
 */
final class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = env('DB_HOST');
        $port = (int)env('DB_PORT');
        $db   = env('DB_NAME');
        $user = env('DB_USER');
        $pass = env('DB_PASSWORD');
        
        if (!$host || !$port || !$db || !$user || $pass === null) {
            throw new \RuntimeException(
                'Missing required environment variables: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD. ' .
                'Please ensure .env file exists and contains all required values.'
            );
        }

        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $db);

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        self::$pdo = $pdo;
        return $pdo;
    }
}



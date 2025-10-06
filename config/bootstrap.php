<?php declare(strict_types=1);

namespace App;

error_reporting(E_ALL);
ini_set('display_errors', '1');
if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json');
}
mb_internal_encoding('UTF-8');

define('APP_ROOT', dirname(__DIR__));

/**
 * Load environment variables from .env (simple, dependency-free parser).
 * Precedence: values in process env override .env file values.
 */
function env(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $envPath = APP_ROOT . '/.env';
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    continue;
                }
                [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                $k = trim($k);
                $v = trim($v);
                $v = trim($v, "\"' ");
                $cache[$k] = $v;
            }
        }
        // Merge in process env and server env (these take precedence)
        $cache = array_merge($cache, $_ENV, $_SERVER);
    }
    return $cache[$key] ?? $default;
}

/** Read and validate JSON request body into an associative array. */
function read_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body']);
        exit;
    }
    return $decoded;
}

/** Emit a JSON response with status code. */
function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
}



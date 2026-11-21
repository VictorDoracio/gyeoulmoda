<?php
declare(strict_types=1);

/**
 * config/env.php
 * Parser próprio de .env (sem Composer).
 */

function env_load(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(500);
        exit('Arquivo .env não encontrado. Copie .env.example para .env e preencha.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last  = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

env_load(dirname(__DIR__) . '/.env');

define('APP_ENV',         getenv('APP_ENV')         ?: 'production');
define('APP_URL',         rtrim(getenv('APP_URL')   ?: 'http://localhost/gyeoulmoda', '/'));
define('APP_TIMEZONE',    getenv('APP_TIMEZONE')    ?: 'America/Sao_Paulo');
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 1800));

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');

date_default_timezone_set(APP_TIMEZONE);
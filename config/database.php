<?php
declare(strict_types=1);

/**
 * config/database.php
 * Conexão PDO singleton com MySQL/MariaDB.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS');
    if ($pass === false) {
        $pass = '';
    }

    if ($name === '' || $user === '') {
        http_response_code(500);
        exit('Configuração de banco ausente. Verifique DB_NAME e DB_USER no .env.');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        if (APP_ENV === 'development') {
            http_response_code(500);
            exit('Erro de conexão: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
        http_response_code(500);
        exit('Erro interno de conexão com o banco.');
    }

    return $pdo;
}
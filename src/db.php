<?php
declare(strict_types=1);

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? 'db',
        $_ENV['DB_NAME'] ?? 'panineria'
    );

    try {
        $pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'panineria_user', $_ENV['DB_PASS'] ?? 'panineria_pass', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException) {
        http_response_code(500);
        die(json_encode(['error' => 'Connessione al database fallita']));
    }

    return $pdo;
}

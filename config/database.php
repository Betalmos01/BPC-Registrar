<?php
require_once __DIR__ . '/config.php';

date_default_timezone_set(APP_TIMEZONE);

function ensure_schema(PDO $pdo): void
{
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table');
    $check->execute(['schema' => DB_NAME, 'table' => 'users']);
    $exists = (int)$check->fetchColumn();
    if ($exists > 0) {
        return;
    }

    $schemaPath = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaPath)) {
        return;
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        return;
    }

    // Split on semicolons to execute statements individually.
    $statements = preg_split('/;\s*\n/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if ((int)$e->getCode() !== 1049) {
            throw $e;
        }

        // Database missing: attempt to create it, then reconnect.
        $bootstrapDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
        $bootstrap = new PDO($bootstrapDsn, DB_USER, DB_PASS, $options);
        $bootstrap->exec(
            'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    ensure_schema($pdo);
    return $pdo;
}

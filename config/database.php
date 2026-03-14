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

function ensure_seed_data(PDO $pdo): void
{
    try {
        $roleCount = (int)$pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
        if ($roleCount === 0) {
            $pdo->exec("INSERT INTO roles (name) VALUES ('Administrator'), ('Registrar Staff')");
        }

        $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($userCount === 0) {
            $adminId = (int)$pdo->query("SELECT id FROM roles WHERE name = 'Administrator'")->fetchColumn();
            $staffId = (int)$pdo->query("SELECT id FROM roles WHERE name = 'Registrar Staff'")->fetchColumn();
            if ($adminId && $staffId) {
                $hash = '$2y$10$tB00KeauThyVvcbvDRqHoeA3BO8aPPCMm.B/WxnqzOhKtns1uHl7O';
                $stmt = $pdo->prepare('INSERT INTO users (role_id, username, password_hash, first_name, last_name, is_active, created_at) VALUES (:role_id, :username, :password_hash, :first_name, :last_name, 1, NOW())');
                $stmt->execute([
                    'role_id' => $adminId,
                    'username' => 'adminaccount@gmail.com',
                    'password_hash' => $hash,
                    'first_name' => 'Admin',
                    'last_name' => 'Account',
                ]);
                $stmt->execute([
                    'role_id' => $staffId,
                    'username' => 'staffaccount@gmail.com',
                    'password_hash' => $hash,
                    'first_name' => 'Staff',
                    'last_name' => 'Account',
                ]);
            }
        }
    } catch (Throwable $e) {
        return;
    }

    try {
        $needsSeed = false;
        foreach (['students', 'instructors', 'classes'] as $table) {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            if ($count === 0) {
                $needsSeed = true;
                break;
            }
        }

        if ($needsSeed) {
            require_once __DIR__ . '/../database/seed_demo.php';
            seed_demo_data($pdo);
        }
    } catch (Throwable $e) {
        // Non-blocking: skip seeding if any query fails.
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
    ensure_seed_data($pdo);
    return $pdo;
}

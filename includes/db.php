<?php

declare(strict_types=1);

function db_failed(bool $set = false): bool
{
    static $failed = false;
    if ($set) {
        $failed = true;
    }

    return $failed;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!defined('DB_NAME') || DB_NAME === '' || !defined('DB_USER') || !defined('DB_PASS')) {
        db_failed(true);
        throw new RuntimeException('Configurarea bazei de date lipsește.');
    }

    $host = defined('DB_HOST') && DB_HOST !== '' ? DB_HOST : 'localhost';
    $dsn = 'mysql:host=' . $host . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $connection = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        db_install($connection);
        $pdo = $connection;
    } catch (Throwable $e) {
        db_failed(true);
        throw new RuntimeException('Conexiunea la baza de date a eșuat.', 0, $e);
    }

    return $pdo;
}

function db_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column));
    if ($stmt !== false && $stmt->fetch() !== false) {
        return;
    }
    $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
}

function db_install(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS clienti (
            id CHAR(16) NOT NULL,
            nume VARCHAR(80) NOT NULL,
            email VARCHAR(120) NOT NULL,
            telefon VARCHAR(20) NOT NULL,
            parola VARCHAR(255) NOT NULL,
            adresa VARCHAR(300) NOT NULL DEFAULT \'\',
            rechizite VARCHAR(2000) NOT NULL DEFAULT \'\',
            creat DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email_unic (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS comenzi (
            id VARCHAR(20) NOT NULL,
            user_id CHAR(16) NOT NULL,
            la DATETIME NOT NULL,
            status VARCHAR(32) NOT NULL,
            adresa VARCHAR(300) NOT NULL,
            nota TEXT NOT NULL,
            rechizite VARCHAR(2000) NOT NULL DEFAULT \'\',
            PRIMARY KEY (id),
            KEY user_la (user_id, la)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS comenzi_linii (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            comanda_id VARCHAR(20) NOT NULL,
            slug VARCHAR(40) NOT NULL,
            titlu VARCHAR(120) NOT NULL,
            cantitate SMALLINT UNSIGNED NOT NULL,
            pret DECIMAL(10,2) NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY comanda (comanda_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS mesaje (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            la DATETIME NOT NULL,
            nume VARCHAR(80) NOT NULL,
            email VARCHAR(120) NOT NULL,
            telefon VARCHAR(20) NOT NULL DEFAULT \'\',
            subiect VARCHAR(80) NOT NULL,
            mesaj TEXT NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stoc_miscari (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            la DATETIME NOT NULL,
            slug VARCHAR(40) NOT NULL,
            optiune VARCHAR(20) NOT NULL DEFAULT \'\',
            cantitate INT UNSIGNED NOT NULL,
            nota VARCHAR(300) NOT NULL DEFAULT \'\',
            PRIMARY KEY (id),
            KEY marfa (slug, optiune)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS preturi (
            slug VARCHAR(40) NOT NULL,
            optiune VARCHAR(20) NOT NULL DEFAULT \'\',
            pret DECIMAL(10,2) NULL DEFAULT NULL,
            PRIMARY KEY (slug, optiune)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db_add_column($pdo, 'comenzi_linii', 'pret', 'DECIMAL(10,2) NULL DEFAULT NULL');
    db_add_column($pdo, 'clienti', 'rechizite', "VARCHAR(2000) NOT NULL DEFAULT ''");
    db_add_column($pdo, 'comenzi', 'rechizite', "VARCHAR(2000) NOT NULL DEFAULT ''");

    $columns = $pdo->query("SHOW COLUMNS FROM clienti LIKE 'google_sub'");
    if ($columns === false || $columns->fetch() === false) {
        $pdo->exec('ALTER TABLE clienti ADD COLUMN google_sub VARCHAR(255) NULL DEFAULT NULL');
        try {
            $pdo->exec('ALTER TABLE clienti ADD UNIQUE KEY google_sub_unic (google_sub)');
        } catch (PDOException $e) {
            if ((string) $e->getCode() !== '42000') {
                throw $e;
            }
        }
    }
}

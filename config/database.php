<?php
// config/database.php

$mysql_host = '127.0.0.1';
$mysql_port = '3306';
$mysql_db   = 'db_rplbaru';
$mysql_user = 'root';
$mysql_pass = '';

$pdo = null;
$db_driver = 'mysql';

try {
    // Attempt MySQL connection
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_db};charset=utf8mb4";
    $pdo = new PDO($dsn, $mysql_user, $mysql_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 2,
    ]);
} catch (Exception $e) {
    // Fallback to SQLite so the project runs immediately without manual MySQL setup
    $db_driver = 'sqlite';
    $sqlite_dir = __DIR__ . '/../database';
    if (!is_dir($sqlite_dir)) {
        mkdir($sqlite_dir, 0777, true);
    }
    $sqlite_file = $sqlite_dir . '/database.sqlite';
    $is_new = !file_exists($sqlite_file);

    $pdo = new PDO("sqlite:" . $sqlite_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($is_new) {
        require_once __DIR__ . '/../database/seed_sqlite.php';
        seed_sqlite_database($pdo);
    }
}

function getDB() {
    global $pdo;
    return $pdo;
}

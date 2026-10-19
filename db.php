<?php
if (session_status() === PHP_SESSION_NONE) {
    if (is_dir('/tmp') && is_writable('/tmp')) {
        session_save_path('/tmp');
    }
    session_start();
}

$host = getenv('DB_HOST') ?: 'aws-1-eu-central-1.pooler.supabase.com';
$port = getenv('DB_PORT') ?: '6543';
$db   = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres.wvvbyfbeywtqnqpmsnkf';
$pass = getenv('DB_PASS') ?: '621695545@ETPC#';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    die("Erro de ligação à base de dados: " . $e->getMessage());
}
?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'aws-1-eu-central-1.pooler.supabase.com';
$port = '6543';
$db   = 'postgres';
$user = 'postgres.wvvbyfbeywtqnqpmsnkf';
$pass = '621695545@ETPC#';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    die("Erro de ligação: " . $e->getMessage());
}
?>
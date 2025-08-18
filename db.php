<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); 

$host    = $_ENV['DB_HOST']    ?? getenv('DB_HOST')    ?? '127.0.0.1';
$port    = $_ENV['DB_PORT']    ?? getenv('DB_PORT')    ?? '3306';
$dbname  = $_ENV['DB_NAME']    ?? getenv('DB_NAME')    ?? '';
$user    = $_ENV['DB_USER']    ?? getenv('DB_USER')    ?? '';
$pass    = $_ENV['DB_PASS']    ?? getenv('DB_PASS')    ?? '';
$charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?? 'utf8mb4';

if ($dbname === '' || $user === '') {
    die("<div style='color:red'><strong>DB ERROR:</strong> Missing DB_NAME or DB_USER in .env</div>");
}

$dsn = "mysql:host={$host};dbname={$dbname};port={$port};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("<div style='color:red'><strong>DB ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>");
}

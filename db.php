<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Composer autoload (optional)
$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

// Load .env only if the library exists AND a .env file is present
if (class_exists('Dotenv\\Dotenv') && is_file(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

// helper: env > getenv > default
$env = function (string $k, $default = null) {
    if (isset($_ENV[$k])) return $_ENV[$k];
    $v = getenv($k);
    return $v !== false ? $v : $default;
};

$host    = $env('DB_HOST', '127.0.0.1');
$port    = (string)$env('DB_PORT', '3306');
$dbname  = $env('DB_NAME', '');
$user    = $env('DB_USER', '');
$pass    = $env('DB_PASSWORD', $env('DB_PASS', '')); // support both
$charset = $env('DB_CHARSET', 'utf8mb4');

if ($dbname === '' || $user === '') {
    http_response_code(500);
    die("<div style='color:red'><strong>DB ERROR:</strong> Missing DB_NAME or DB_USER in environment.</div>");
}

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("<div style='color:red'><strong>DB ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>");
}

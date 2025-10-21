<?php
$config = require __DIR__ . '/config.php';
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
  $config['DB_HOST'], $config['DB_PORT'], $config['DB_NAME']);
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_LOCAL_INFILE => true,
];
try {
  $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
} catch (Throwable $e) {
  http_response_code(500);
  echo "<pre>DB connection failed: {$e->getMessage()}</pre>";
  exit;
}


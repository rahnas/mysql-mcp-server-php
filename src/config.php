<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$config = [
    'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
    'DB_PORT' => $_ENV['DB_PORT'] ?? '3306',
    'DB_USER' => $_ENV['DB_USER'] ?? 'readonly_user',
    'DB_PASSWORD' => $_ENV['DB_PASSWORD'] ?? '',
    'DB_NAME' => $_ENV['DB_NAME'] ?? '',
    'API_KEY' => $_ENV['API_KEY'] ?? ''
];
